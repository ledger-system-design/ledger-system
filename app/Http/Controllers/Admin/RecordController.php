<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Balance;
use App\Models\Member;
use App\Models\Order;
use App\Services\FlowService;
use App\Support\Inputs;
use Illuminate\Http\Request;

/**
 * 采购记录
 *
 * 查看用户完成采购时上传的支付凭证，并手动修改「是否到账」状态。
 * 一旦标记为已到账，系统会按层级给对应会员发放奖励：
 *   参与者 1.5% + 第一代直推人 0.5% + 店长 1.5% + 平台 0.5%
 */
class RecordController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()->where('status', '>=', 1);

        if ($kw = trim((string) $request->input('kw'))) {
            $query->where(function ($q) use ($kw) {
                $q->where('orderno', 'like', "%{$kw}%")
                    ->orWhere('name', 'like', "%{$kw}%");
            });
        }

        if ($request->filled('memberid')) {
            $query->where('memberid', (int) $request->input('memberid'));
        }

        $filter = (string) $request->input('filter', 'wait');
        if ($filter === 'wait') {
            $query->whereIn('status', [1, 2]);
        } elseif ($filter === 'arrived') {
            $query->where('status', 3);
        } elseif ($request->filled('status') && $request->input('status') !== '') {
            $query->where('status', (int) $request->input('status'));
        }

        if ($request->filled('date_start')) {
            $query->where('calcdate', '>=', (int) str_replace('-', '', $request->input('date_start')));
        }
        if ($request->filled('date_end')) {
            $query->where('calcdate', '<=', (int) str_replace('-', '', $request->input('date_end')));
        }

        $orders = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $members = [];
        $rewards = [];
        foreach ($orders as $o) {
            $members[$o->memberid] = Member::find($o->memberid);
            $members[$o->sellerid] = Member::find($o->sellerid);
        }

        $stat = [
            'wait' => Order::whereIn('status', [1, 2])->count(),
            'arrived' => Order::where('status', 3)->count(),
            'paid_amount' => (float) Order::where('status', '>=', 1)->sum('amount'),
            'settled_amount' => (float) Order::where('status', 3)->sum('settle_amount'),
        ];

        return view('admin.records.index', compact('orders', 'members', 'stat', 'filter'));
    }

    /** 查看单个用户的采购记录 */
    public function member(Request $request, int $id)
    {
        $request->merge(['memberid' => $id, 'filter' => $request->input('filter', 'all')]);

        return $this->index($request);
    }

    /** 查看支付凭证与到账信息（AJAX） */
    public function detail(int $id, FlowService $flow)
    {
        $order = Order::findOrFail($id);
        $buyer = Member::find($order->memberid);
        $seller = Member::find($order->sellerid);

        // 已发放的奖励明细
        $paid = Balance::query()->where('remark', $order->orderno)->get()->map(function ($b) {
            $m = Member::find($b->memberid);

            return [
                'memberid' => $b->memberid,
                'name' => $m ? ($m->userreal ?: $m->nickname) : '',
                'username' => $m->username ?? '',
                'typeid' => $b->typeid,
                'amount' => (float) $b->amount,
                'type' => $b->type,
                'addtime' => optional($b->addtime)->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'code' => 0,
            'data' => [
                'order' => [
                    'id' => $order->id,
                    'orderno' => $order->orderno,
                    'name' => $order->name,
                    'amount' => (float) $order->amount,
                    'status' => (int) $order->status,
                    'status_text' => $order->statusText(),
                    'payphoto' => $order->payphoto,
                    'payname' => $order->payname,
                    'paytype' => $order->paytype,
                    'addtime' => optional($order->addtime)->format('Y-m-d H:i:s'),
                    'paytime' => optional($order->paytime)->format('Y-m-d H:i:s'),
                    'arrived_at' => optional($order->arrived_at)->format('Y-m-d H:i:s'),
                    'settle_amount' => (float) $order->settle_amount,
                    'remark' => $order->remark,
                    'admin_name' => $order->admin_name,
                ],
                'buyer' => $buyer ? ['id' => $buyer->id, 'name' => $buyer->userreal ?: $buyer->nickname, 'username' => $buyer->username] : null,
                'seller' => $seller ? ['id' => $seller->id, 'name' => $seller->userreal ?: $seller->nickname, 'username' => $seller->username] : null,
                'preview' => $flow->previewRewards($order),
                'paid' => $paid,
            ],
        ]);
    }

    /** 标记为已到账 → 发放各层级奖励 */
    public function arrive(int $id, FlowService $flow)
    {
        $order = Order::findOrFail($id);

        if ((int) $order->status === 3) {
            return back()->withErrors(['order' => '该订单已到账，无需重复操作']);
        }

        if ((int) $order->status === 0) {
            return back()->withErrors(['order' => '该订单用户尚未支付，无法确认到账']);
        }

        $admin = session('admin');
        $paid = $flow->settleOrder($order, (int) ($admin['id'] ?? 0));

        AdminLog::write('record.arrive', '确认到账并发放奖励 ' . $order->orderno, 'order', $order->id, $paid);

        $msg = '已确认到账，共发放奖励 ' . number_format((float) $order->settle_amount, 2) . ' 元：';
        $msg .= collect($paid)->map(fn ($p) => $p['name'] . '(' . $p['label'] . ') ' . $p['amount'])->implode('、');

        return back()->with('status', $msg);
    }

    /** 取消到账 → 冲回已发放奖励 */
    public function revert(int $id, FlowService $flow)
    {
        $order = Order::findOrFail($id);

        if ((int) $order->status !== 3) {
            return back()->withErrors(['order' => '该订单尚未到账，无需取消']);
        }

        $reverted = $flow->revertOrder($order);

        AdminLog::write('record.revert', '取消到账并冲回奖励 ' . $order->orderno, 'order', $order->id, $reverted);

        $msg = '已取消到账，共冲回奖励 ' . collect($reverted)->sum('amount') . ' 元';
        if ($reverted) {
            $msg .= '（' . collect($reverted)->map(fn ($r) => $r['name'] . ' ' . $r['amount'])->implode('、') . '）';
        }

        return back()->with('status', $msg);
    }

    /** 修改订单的支付/到账信息 */
    public function update(Request $request, int $id)
    {
        $order = Order::findOrFail($id);

        $data = $request->validate([
            'payphoto' => ['nullable', 'string', 'max:255'],
            'remark' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'integer', 'in:0,1,2,3'],
        ], [
            'status.required' => '请选择任务状态',
            'status.in' => '任务状态不正确，请重新选择',
            'payphoto.max' => '支付凭证地址过长，请重新上传',
            'remark.max' => '备注内容过长，请精简到 255 字以内',
        ], [
            'payphoto' => '支付凭证',
            'remark' => '备注',
            'status' => '状态',
        ]);

        // 留空字段会变成 null，直接写库会触发 NOT NULL 约束
        $data = Inputs::strings($data, ['payphoto', 'remark']);

        $before = $order->toArray();
        $order->fill($data)->save();

        AdminLog::write('record.update', '修改采购记录 ' . $order->orderno, 'order', $order->id, [
            'before' => $before,
            'after' => $order->toArray(),
        ]);

        return back()->with('status', '采购记录已更新');
    }
}
