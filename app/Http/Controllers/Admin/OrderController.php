<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Cert;
use App\Models\Member;
use App\Models\Order;
use App\Services\FlowService;
use App\Support\Inputs;
use Illuminate\Http\Request;

/**
 * 采购清单（后台发布的所有采购任务）
 *
 * 每一条任务都是「针对某个用户」发布的，会员端只能看到分配给自己的任务。
 */
class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query();

        if ($kw = trim((string) $request->input('kw'))) {
            $query->where(function ($q) use ($kw) {
                $q->where('orderno', 'like', "%{$kw}%")
                    ->orWhere('name', 'like', "%{$kw}%")
                    ->orWhere('remark', 'like', "%{$kw}%");
            });
        }

        if ($request->filled('memberid')) {
            $query->where('memberid', (int) $request->input('memberid'));
        }

        if ($request->filled('sellerid')) {
            $query->where('sellerid', (int) $request->input('sellerid'));
        }

        if ($request->filled('status') && $request->input('status') !== '') {
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
        foreach ($orders as $o) {
            $members[$o->memberid] = Member::find($o->memberid);
            $members[$o->sellerid] = Member::find($o->sellerid);
        }

        $stat = [
            'total' => Order::count(),
            'amount' => (float) Order::sum('amount'),
            'pending' => Order::whereIn('status', [0, 1, 2])->count(),
            'arrived' => Order::where('status', 3)->count(),
        ];

        return view('admin.orders.index', compact('orders', 'members', 'stat'));
    }

    /** 发布任务表单（?member= 可预选目标用户） */
    public function create(Request $request)
    {
        $members = Member::query()->orderByDesc('id')->get(['id', 'userreal', 'username', 'telephone', 'gradename', 'fid']);
        $preset = $request->input('member') ? Member::find((int) $request->input('member')) : null;

        return view('admin.orders.form', [
            'order' => null,
            'members' => $members,
            'preset' => $preset,
        ]);
    }

    public function store(Request $request, FlowService $flow)
    {
        $data = $this->validateData($request);

        $order = new Order();
        $order->fill($data);
        $order->orderno = ($data['orderno'] ?? '') !== '' ? $data['orderno'] : $this->makeOrderNo();
        $order->memberid = (int) $data['memberid'];
        $order->sellerid = (int) $data['sellerid'];
        $order->created_by = (int) (session('admin.id') ?? 0);
        $order->admin_name = (string) (session('admin.username') ?? '');
        $order->calcdate = (int) date('Ymd', strtotime($data['addtime'] ?? 'now') ?: time());
        $order->addtime = ($data['addtime'] ?? '') !== '' ? $data['addtime'] : now();
        $order->status = (int) $data['status'];

        // 冻结当前收益率与应得收益，便于事后对账
        $buyer = Member::find($order->memberid);
        $order->rate = $flow->rateFor($buyer);
        $order->profit = round((float) $order->amount * (float) $order->rate, 2);

        $order->save();

        AdminLog::write('order.store', '发布采购任务 ' . $order->orderno, 'order', $order->id, $order->toArray());

        return redirect()->route('admin.orders.index')->with('status', '采购任务已发布：' . $order->orderno . '（仅该用户可见）');
    }

    public function edit(int $id)
    {
        $order = Order::findOrFail($id);
        $members = Member::query()->orderByDesc('id')->get(['id', 'userreal', 'username', 'telephone', 'gradename', 'fid']);

        return view('admin.orders.form', [
            'order' => $order,
            'members' => $members,
            'preset' => null,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $order = Order::findOrFail($id);
        $data = $this->validateData($request);

        $before = $order->toArray();

        $order->fill($data);
        $order->orderno = ($data['orderno'] ?? '') !== '' ? $data['orderno'] : $order->orderno;
        $order->memberid = (int) $data['memberid'];
        $order->sellerid = (int) $data['sellerid'];
        $order->calcdate = (int) date('Ymd', strtotime($data['addtime'] ?? 'now') ?: time());
        $order->addtime = ($data['addtime'] ?? '') !== '' ? $data['addtime'] : $order->addtime;
        $order->status = (int) $data['status'];
        $order->save();

        AdminLog::write('order.update', '修改采购任务 ' . $order->orderno, 'order', $order->id, [
            'before' => $before,
            'after' => $order->toArray(),
        ]);

        return redirect()->route('admin.orders.index')->with('status', '采购任务已保存');
    }

    public function destroy(int $id)
    {
        $order = Order::findOrFail($id);

        if ((int) $order->status === 3) {
            return back()->withErrors(['order' => '该任务已到账并发放过奖励，请先「取消到账」再删除']);
        }

        AdminLog::write('order.destroy', '删除采购任务 ' . $order->orderno, 'order', $id, $order->toArray());
        $order->delete();

        return back()->with('status', '采购任务已删除');
    }

    /** 批量发布（按用户列表勾选） */
    public function batch(Request $request, FlowService $flow)
    {
        $data = $request->validate([
            'memberids' => ['required', 'array'],
            'memberids.*' => ['integer'],
            'amount' => ['required', 'numeric', 'min:1'],
            'sellerid' => ['required', 'integer'],
            'name' => ['nullable', 'string', 'max:128'],
        ], [], [
            'memberids' => '目标用户',
            'amount' => '采购金额',
            'sellerid' => '收款人',
        ]);

        $count = 0;
        foreach ($data['memberids'] as $mid) {
            $buyer = Member::find((int) $mid);
            if (! $buyer) {
                continue;
            }

            Order::create([
                'orderno' => $this->makeOrderNo(),
                'memberid' => $buyer->id,
                'sellerid' => (int) $data['sellerid'],
                'name' => $data['name'] ?: '采购货款',
                'indexpic' => '/static/img/no_img.png',
                'amount' => (float) $data['amount'],
                'status' => 0,
                'created_by' => (int) (session('admin.id') ?? 0),
                'admin_name' => (string) (session('admin.username') ?? ''),
                'calcdate' => (int) date('Ymd'),
                'addtime' => now(),
            ]);
            $count++;
        }

        AdminLog::write('order.batch', '批量发布采购任务 ' . $count . ' 条', 'order', '', $data);

        return back()->with('status', '已为 ' . $count . ' 位用户发布采购任务');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'memberid' => ['required', 'integer', 'exists:members,id'],
            'sellerid' => ['required', 'integer', 'exists:members,id'],
            'name' => ['required', 'string', 'max:128'],
            'indexpic' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'orderno' => ['nullable', 'string', 'max:32'],
            'addtime' => ['nullable', 'date'],
            'remark' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'integer', 'in:0,1,2,3'],
            'payphoto' => ['nullable', 'string', 'max:255'],
        ], [
            'memberid.required' => '请先选择「目标用户（买家）」，这一步不能留空',
            'memberid.exists' => '选择的「目标用户」不存在，请重新选择',
            'sellerid.required' => '请先选择「任务对象（收款人）」，这一步不能留空',
            'sellerid.exists' => '选择的「任务对象」不存在，请重新选择',
            'name.required' => '请填写采购商品名称',
            'amount.required' => '请填写采购货款金额',
            'amount.numeric' => '采购货款金额只能是数字，请不要输入文字',
            'amount.min' => '采购货款金额必须大于 0',
            'addtime.date' => '下单时间格式不正确，请重新选择时间',
            'status.required' => '请选择任务状态',
            'status.in' => '任务状态不正确，请重新选择',
            'indexpic.max' => '商品图片地址过长，请重新上传',
            'payphoto.max' => '支付凭证地址过长，请重新上传',
        ], [
            'memberid' => '目标用户（买家）',
            'sellerid' => '任务对象（收款人）',
            'name' => '采购商品名称',
            'amount' => '采购货款金额',
            'orderno' => '订单编号',
            'addtime' => '下单时间',
            'status' => '任务状态',
        ]);

        // 空值统一成空字符串：留空的字段不能变成 null，否则会触发 NOT NULL 约束
        $data = Inputs::strings($data, ['orderno', 'indexpic', 'payphoto', 'remark', 'addtime']);

        if ($data['orderno'] === '') {
            unset($data['orderno']);
        }
        if ($data['addtime'] === '') {
            unset($data['addtime']);
        }

        // 商品图片留空时用占位图，避免会员端出现破图
        $data['indexpic'] = Inputs::orDefault($data['indexpic'], '/static/img/no_img.png');

        if ((int) $data['memberid'] === (int) $data['sellerid']) {
            $data['sellerid'] = $this->pickSeller((int) $data['memberid']);
        }

        return $data;
    }

    /** 自动挑选一个收款人（同店优先） */
    public function pickSeller(int $buyerId): int
    {
        $buyer = Member::find($buyerId);

        $seller = Member::query()
            ->where('id', '!=', $buyerId)
            ->where('iscert', 1)
            ->where('status', 1)
            ->when($buyer && $buyer->fid, fn ($q) => $q->where('fid', $buyer->fid))
            ->inRandomOrder()
            ->first();

        if (! $seller) {
            $seller = Member::query()->where('id', '!=', $buyerId)->inRandomOrder()->first();
        }

        return $seller ? (int) $seller->id : $buyerId;
    }

    /** 生成订单号 */
    public function makeOrderNo(): string
    {
        do {
            $no = 'XS' . date('Ymd') . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Order::query()->where('orderno', $no)->exists());

        return $no;
    }

    /**
     * 派单推荐（AJAX）
     *
     * 客户规则：管理员给新用户派任务时，系统按「派单金额」与候选人
     * 「上一笔任务本金 + 收益（回款额）」的差额，由近到远排列候选人。
     *   例：A 2000（回款 2030）、B 3000（3045）、C 4000（4060）、D 4500（4567.5）
     *       派 2300 → A B C D；派 2800 → B A C D
     * 同时标注「已被选为任务对象」，供后台用另一种颜色提醒。
     */
    public function candidates(Request $request, FlowService $flow)
    {
        $amount = (float) $request->input('amount', 0);
        $excludeId = (int) $request->input('exclude', 0);
        $today = (int) date('Ymd');
        $limit = max(1, min(50, (int) $request->input('limit', 20)));

        $members = Member::query()
            ->where('status', 1)
            ->when($excludeId > 0, fn ($q) => $q->where('id', '!=', $excludeId))
            ->get(['id', 'username', 'userreal', 'nickname', 'iscert', 'rate', 'telephone']);

        $rows = [];
        foreach ($members as $m) {
            $last = Order::query()->where('memberid', $m->id)->orderByDesc('id')->first(['id', 'amount', 'profit', 'rate', 'addtime']);

            $base = $last ? (float) $last->amount : 0.0;
            $profit = 0.0;
            if ($last) {
                $profit = (float) $last->profit > 0
                    ? (float) $last->profit
                    : round($base * ((float) $last->rate > 0 ? (float) $last->rate : $flow->rateFor($m)), 2);
            }
            $returnAmount = round($base + $profit, 2);

            $assignedToday = Order::query()->where('sellerid', $m->id)->where('calcdate', $today)->count();
            $pending = Order::query()->where('sellerid', $m->id)->whereIn('status', [0, 1, 2])->count();

            $buyerToday = Order::query()->where('memberid', $m->id)->where('calcdate', $today)->count();
            $buyerPending = Order::query()->where('memberid', $m->id)->whereIn('status', [0, 1, 2])->count();

            $rows[] = [
                'id' => $m->id,
                'name' => $m->userreal ?: $m->nickname,
                'nickname' => $m->nickname,
                'username' => $m->username,
                'iscert' => (int) $m->iscert,
                'rate' => $flow->rateFor($m),
                'has_order' => (bool) $last,
                'last_amount' => $base,
                'last_profit' => $profit,
                'return_amount' => $returnAmount,
                'distance' => ($amount > 0 && $returnAmount > 0) ? round(abs($amount - $returnAmount), 2) : null,
                // 作为「卖方 / 任务对象」的安排情况
                'assigned_today' => $assignedToday,
                'pending' => $pending,
                // 作为「买方 / 任务方」的安排情况
                'buyer_today' => $buyerToday,
                'buyer_pending' => $buyerPending,
            ];
        }

        usort($rows, function ($a, $b) {
            $ad = $a['distance'] === null ? PHP_INT_MAX : $a['distance'];
            $bd = $b['distance'] === null ? PHP_INT_MAX : $b['distance'];
            if ($ad != $bd) {
                return $ad < $bd ? -1 : 1;
            }
            // 差额相同：已认证优先，其次按 ID
            if ($a['iscert'] !== $b['iscert']) {
                return $b['iscert'] <=> $a['iscert'];
            }

            return $a['id'] <=> $b['id'];
        });

        return response()->json([
            'code' => 0,
            'data' => array_slice($rows, 0, $limit),
            'total' => count($rows),
            'amount' => $amount,
        ]);
    }

    /**
     * 任务对象的收款方式（AJAX）
     *
     * 按 payinfo 数组顺序返回，序号 1 为默认优选平台，其余依次陈列。
     */
    public function sellerPay(int $id)
    {
        $member = Member::find($id);

        if (! $member) {
            return response()->json(['code' => 1, 'msg' => '该会员不存在', 'data' => []]);
        }

        $cert = Cert::query()->where('memberid', $id)->first();
        $payinfo = $cert && is_array($cert->payinfo) ? $cert->payinfo : Cert::defaultPayinfo();

        $list = [];
        foreach ($payinfo as $item) {
            if (! is_array($item)) {
                continue;
            }
            $type = (string) ($item['type'] ?? 'url');
            $hasValue = $type === 'bank'
                ? (! empty($item['cardno']) || ! empty($item['carduser']))
                : (! empty($item['image']) || ! empty($item['account']) || ! empty($item['url']));

            if (! $hasValue) {
                continue;
            }

            $list[] = [
                'sort' => count($list) + 1,
                'type' => $type,
                'name' => (string) ($item['name'] ?? '收款方式'),
                'image' => (string) ($item['image'] ?? ''),
                'url' => (string) ($item['url'] ?? ''),
                'account' => (string) ($item['account'] ?? ''),
                'cardno' => (string) ($item['cardno'] ?? ''),
                'carduser' => (string) ($item['carduser'] ?? ''),
                'cardbank' => (string) ($item['cardbank'] ?? ''),
            ];
        }

        return response()->json([
            'code' => 0,
            'data' => [
                'id' => $member->id,
                'name' => $member->userreal ?: $member->nickname,
                'username' => $member->username,
                'iscert' => (int) $member->iscert,
                'pays' => $list,
            ],
        ]);
    }

    /** 收款人下拉数据（AJAX） */
    public function sellers(Request $request)
    {
        $buyerId = (int) $request->input('buyerid');
        $buyer = $buyerId ? Member::find($buyerId) : null;

        $list = Member::query()
            ->where('status', 1)
            ->where('id', '!=', $buyerId)
            ->when($buyer && $buyer->fid, fn ($q) => $q->where('fid', $buyer->fid))
            ->orderByDesc('iscert')
            ->orderByDesc('id')
            ->limit(300)
            ->get(['id', 'userreal', 'username', 'iscert', 'telephone']);

        return response()->json([
            'code' => 0,
            'data' => $list->map(fn ($m) => [
                'id' => $m->id,
                'text' => ($m->userreal ?: $m->nickname ?: '会员') . '（' . $m->username . '）' . ($m->iscert ? ' · 已认证' : ''),
            ]),
        ]);
    }
}
