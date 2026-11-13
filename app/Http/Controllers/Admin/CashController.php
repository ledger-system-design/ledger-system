<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Cash;
use App\Models\Member;
use App\Services\FlowService;
use Illuminate\Http\Request;

class CashController extends Controller
{
    public function index(Request $request)
    {
        $query = Cash::query();

        if ($kw = trim((string) $request->input('kw'))) {
            $query->where(function ($q) use ($kw) {
                $q->where('memberid', $kw)->orWhere('remark', 'like', "%{$kw}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== '') {
            $query->where('status', (int) $request->input('status'));
        }

        $rows = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $members = [];
        foreach ($rows as $r) {
            $members[$r->memberid] = Member::find($r->memberid);
        }

        $stat = [
            'pending' => Cash::where('status', 0)->count(),
            'pending_amount' => (float) Cash::where('status', 0)->sum('amount'),
            'done' => Cash::where('status', 1)->count(),
            'done_amount' => (float) Cash::where('status', 1)->sum('amount'),
            'rejected' => Cash::where('status', 2)->count(),
        ];

        return view('admin.cashes.index', compact('rows', 'members', 'stat'));
    }

    /** 审核通过：确认已打款 */
    public function approve(int $id)
    {
        $cash = Cash::findOrFail($id);
        if ((int) $cash->status !== 0) {
            return back()->withErrors(['cash' => '该申请已处理']);
        }

        $cash->status = 1;
        $cash->remark = '提现已完成';
        $cash->save();

        AdminLog::write('cash.approve', '通过提现申请 #' . $id, 'cash', $id, $cash->toArray());

        return back()->with('status', '提现已标记为到账');
    }

    /** 驳回：把金额退回会员余额 */
    public function reject(int $id, FlowService $flow)
    {
        $cash = Cash::findOrFail($id);
        if ((int) $cash->status !== 0) {
            return back()->withErrors(['cash' => '该申请已处理']);
        }

        $member = Member::find($cash->memberid);

        if ($member) {
            $flow->credit(
                $member,
                (float) $cash->amount,
                (int) config('flow.type_recharge'),
                'CASH' . $cash->id,
                '提现驳回退回',
                1,
                $cash->account ?: 'account1'
            );
        }

        $cash->status = 2;
        $cash->remark = '提现已驳回，金额已退回余额';
        $cash->save();

        AdminLog::write('cash.reject', '驳回提现申请 #' . $id, 'cash', $id, $cash->toArray());

        return back()->with('status', '提现已驳回，金额已退回会员余额');
    }
}
