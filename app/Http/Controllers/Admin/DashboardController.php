<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\Cash;
use App\Models\Member;
use App\Models\Order;
use App\Models\Setting;
use App\Services\FlowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(FlowService $flow)
    {
        $today = date('Ymd');

        $stats = [
            'member_total' => Member::count(),
            'member_today' => Member::whereDate('addtime', today())->count(),
            'order_total' => Order::count(),
            'order_today' => Order::where('calcdate', (int) $today)->count(),
            'pending_pay' => Order::where('status', 0)->count(),
            'pending_confirm' => Order::where('status', 1)->count(),
            'pending_arrive' => Order::whereIn('status', [1, 2])->count(),
            'arrived_total' => Order::where('status', 3)->count(),
            'flow_total' => (float) Order::whereIn('status', [0, 1, 2, 3])->sum('amount'),
            'flow_today' => (float) Order::where('calcdate', (int) $today)->sum('amount'),
            'bonus_order' => (float) Balance::where('typeid', config('flow.type_order'))->sum('amount'),
            'bonus_direct' => (float) Balance::where('typeid', config('flow.type_direct'))->sum('amount'),
            'bonus_shop' => (float) Balance::where('typeid', config('flow.type_shop'))->sum('amount'),
            'cash_pending' => Cash::where('status', 0)->count(),
            'cash_pending_amount' => (float) Cash::where('status', 0)->sum('amount'),
            'platform_profit' => (float) Setting::get('platform_profit', 0),
        ];

        // 最近发布的任务
        $recentOrders = Order::query()->orderByDesc('id')->limit(10)->get();

        // 近 7 天流水走势
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = (int) date('Ymd', strtotime("-{$i} day"));
            $trend[] = [
                'date' => date('m-d', strtotime("-{$i} day")),
                'amount' => (float) Order::where('calcdate', $d)->sum('amount'),
                'count' => Order::where('calcdate', $d)->count(),
            ];
        }

        // 直推排行（第一代收益 Top 10）
        $topDirect = Balance::query()
            ->select('memberid', DB::raw('SUM(amount) as total'))
            ->where('typeid', config('flow.type_direct'))
            ->groupBy('memberid')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $m = Member::find($row->memberid);

                return [
                    'name' => $m ? ($m->userreal ?: $m->nickname) : ('#' . $row->memberid),
                    'username' => $m->username ?? '',
                    'total' => (float) $row->total,
                ];
            });

        return view('admin.dashboard', compact('stats', 'recentOrders', 'trend', 'topDirect'));
    }
}
