<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\Member;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Balance::query();

        if ($kw = trim((string) $request->input('kw'))) {
            $query->where(function ($q) use ($kw) {
                $q->where('remark', 'like', "%{$kw}%")
                    ->orWhere('remark1', 'like', "%{$kw}%")
                    ->orWhere('memberid', $kw);
            });
        }

        if ($request->filled('memberid')) {
            $query->where('memberid', (int) $request->input('memberid'));
        }

        if ($request->filled('typeid') && $request->input('typeid') !== '') {
            $query->where('typeid', (int) $request->input('typeid'));
        }

        if ($request->filled('account') && $request->input('account') !== '') {
            $query->where('account', (string) $request->input('account'));
        }

        if ($request->filled('type') && $request->input('type') !== '') {
            $query->where('type', (int) $request->input('type'));
        }

        if ($request->filled('date_start')) {
            $query->where('calcdate', '>=', (int) str_replace('-', '', $request->input('date_start')));
        }
        if ($request->filled('date_end')) {
            $query->where('calcdate', '<=', (int) str_replace('-', '', $request->input('date_end')));
        }

        $rows = $query->orderByDesc('id')->paginate(30)->withQueryString();

        $members = [];
        foreach ($rows as $r) {
            $members[$r->memberid] = Member::find($r->memberid);
        }

        $stat = [
            'in' => (float) Balance::where('type', 1)->sum('amount'),
            'out' => (float) Balance::where('type', 0)->sum('amount'),
            'order' => (float) Balance::where('typeid', config('flow.type_order'))->sum('amount'),
            'direct' => (float) Balance::where('typeid', config('flow.type_direct'))->sum('amount'),
            'shop' => (float) Balance::where('typeid', config('flow.type_shop'))->sum('amount'),
            'total' => Balance::count(),
        ];

        return view('admin.balances.index', compact('rows', 'members', 'stat'));
    }

    public function destroy(int $id)
    {
        $row = Balance::findOrFail($id);
        $member = Member::find($row->memberid);

        \App\Models\AdminLog::write('balance.destroy', '删除资金明细 #' . $id, 'balance', $id, $row->toArray());
        $row->delete();

        return back()->with('status', '明细已删除（注意：不会自动回滚会员余额，请用「调整余额」修正）');
    }
}
