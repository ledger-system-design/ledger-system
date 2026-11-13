<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Member;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'index_title' => Setting::get('index_title', ''),
            'index_logo' => Setting::get('index_logo', ''),
            'index_banner' => Setting::json('index_banner'),
            'index_nav' => Setting::json('index_nav'),
            'index_news' => Setting::json('index_news'),
            'member_tools' => Setting::json('member_tools'),
            'auto_settle' => (int) Setting::get('auto_settle', 0),
            'platform_profit' => (float) Setting::get('platform_profit', 0),
            'order_rate' => (float) Setting::get('order_rate', config('flow.order_rate')),
            'register_open' => (int) Setting::get('register_open', 0),
        ];

        $flow = config('flow');

        return view('admin.settings.index', compact('settings', 'flow'));
    }

    public function update(Request $request)
    {
        $before = Setting::all()->pluck('value', 'key')->toArray();

        Setting::put('index_title', (string) $request->input('index_title', ''));
        Setting::put('index_logo', (string) $request->input('index_logo', ''));
        Setting::put('auto_settle', (int) $request->input('auto_settle', 0));
        Setting::put('register_open', (int) $request->input('register_open', 0));

        // 全局任务收益率：后台按百分比录入（1 ~ 100），存库为小数（0.01 ~ 1）
        if ($request->filled('order_rate_percent')) {
            $pct = (float) $request->input('order_rate_percent');
            $pct = max(1, min(100, $pct));
            Setting::put('order_rate', round($pct / 100, 4));
        }

        // 轮播
        $banner = [];
        foreach ((array) $request->input('banner', []) as $i => $item) {
            if (empty($item['src'])) {
                continue;
            }
            $banner[] = [
                'src' => (string) $item['src'],
                'url' => (string) ($item['url'] ?? ''),
                'color' => '',
                'remark' => (string) ($item['remark'] ?? ''),
                'sort' => (string) ($i + 1),
                'status' => '1',
            ];
        }
        Setting::put('index_banner', $banner);

        // 导航宫格
        $nav = [];
        foreach ((array) $request->input('nav', []) as $i => $item) {
            if (empty($item['src']) && empty($item['remark'])) {
                continue;
            }
            $nav[] = [
                'src' => (string) ($item['src'] ?? ''),
                'url' => (string) ($item['url'] ?? ''),
                'color' => '',
                'remark' => (string) ($item['remark'] ?? ''),
                'sort' => (string) ($i + 1),
                'status' => '1',
            ];
        }
        Setting::put('index_nav', $nav);

        // 公告
        $news = [];
        foreach ((array) $request->input('news', []) as $item) {
            $title = is_array($item) ? ($item['title'] ?? '') : (string) $item;
            if (trim($title) === '') {
                continue;
            }
            $news[] = ['title' => trim($title)];
        }
        Setting::put('index_news', $news);

        // 我的服务入口
        $tools = [];
        foreach ((array) $request->input('tools', []) as $i => $item) {
            if (empty($item['remark'])) {
                continue;
            }
            $tools[] = [
                'src' => (string) ($item['src'] ?? ''),
                'url' => (string) ($item['url'] ?? ''),
                'color' => '',
                'remark' => (string) $item['remark'],
                'sort' => (string) ($i + 1),
                'status' => (string) ($item['status'] ?? '1'),
            ];
        }
        Setting::put('member_tools', $tools);

        Setting::put('platform_profit', (float) $request->input('platform_profit', 0));

        AdminLog::write('setting.update', '修改系统设置', 'setting', '', [
            'before' => $before,
            'after' => Setting::all()->pluck('value', 'key')->toArray(),
        ]);

        return back()->with('status', '系统设置已保存');
    }

    /** 运营数据总览（各会员汇总） */
    public function report(Request $request)
    {
        $rows = Member::query()->orderByDesc('id')->paginate(30);

        $data = [];
        foreach ($rows as $m) {
            $data[$m->id] = [
                'orders' => Order::where('memberid', $m->id)->count(),
                'flow' => (float) Order::where('memberid', $m->id)->whereIn('status', [1, 2, 3])->sum('amount'),
                'arrived' => (float) Order::where('memberid', $m->id)->where('status', 3)->sum('amount'),
            ];
        }

        return view('admin.settings.report', ['rows' => $rows, 'data' => $data]);
    }
}
