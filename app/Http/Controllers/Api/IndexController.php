<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Order;
use App\Models\Setting;
use App\Support\Api;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    /** index/setting —— 店铺名称与 Logo */
    public function setting()
    {
        return Api::ok([
            'index_title' => Setting::get('index_title', '锦金醇馆'),
            'index_logo' => Setting::get('index_logo', ''),
        ]);
    }

    /** Index/indexdata —— 首页数据（轮播、导航、公告） */
    public function indexdata(Request $request)
    {
        $member = $request->attributes->get('member');

        $banner = Setting::json('index_banner');
        $nav = Setting::json('index_nav');
        $news = Setting::json('index_news');

        $orders = [];
        if ($member) {
            $orders = [
                Order::query()->where('memberid', $member->id)->where('status', 0)->count(),
                Order::query()->where('memberid', $member->id)->where('status', 1)->count(),
                Order::query()->where('memberid', $member->id)->where('status', 3)->count(),
            ];
        }

        return Api::ok([
            'index_title' => Setting::get('index_title', '锦金醇馆'),
            'index_logo' => Setting::get('index_logo', ''),
            'index_banner' => $banner,
            'index_nav' => $nav,
            'index_news' => array_map(fn ($t) => ['title' => is_array($t) ? ($t['title'] ?? '') : (string) $t], $news),
            'iscert' => $member ? (int) $member->iscert : 0,
            'order' => $orders,
        ]);
    }
}
