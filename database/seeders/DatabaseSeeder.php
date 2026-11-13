<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * 初始化数据（系统配置 + 管理员账号）
 *
 * 不含任何测试业务数据；如需造测试数据请执行：
 *     php artisan db:seed --class=DemoSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminSeeder::class);
        $this->settings();
    }

    /** 会员端站点配置 */
    private function settings(): void
    {
        Setting::put('index_title', '账本管理系统');
        Setting::put('index_logo', '');

        Setting::put('index_banner', [
            ['src' => '/static/system/icons/b1.png', 'url' => '', 'color' => '', 'remark' => '', 'sort' => '1', 'status' => '1'],
            ['src' => '/static/system/icons/b2.jpg', 'url' => '', 'color' => '', 'remark' => '', 'sort' => '2', 'status' => '1'],
        ]);

        Setting::put('index_nav', [
            ['src' => '/static/system/icons/n1.png', 'url' => '', 'color' => '', 'remark' => '平台规则', 'sort' => '1', 'status' => '1'],
            ['src' => '/static/system/icons/n2.png', 'url' => '', 'color' => '', 'remark' => '我要开店', 'sort' => '2', 'status' => '1'],
            ['src' => '/static/system/icons/n3.png', 'url' => '', 'color' => '', 'remark' => '赠送权益', 'sort' => '3', 'status' => '1'],
            ['src' => '/static/system/icons/n4.png', 'url' => '', 'color' => '', 'remark' => '积分商城', 'sort' => '4', 'status' => '1'],
            ['src' => '/static/system/icons/n5.png', 'url' => '/pages/index/caigou', 'color' => '', 'remark' => '采购清单', 'sort' => '5', 'status' => '1'],
        ]);

        Setting::put('index_news', [
            ['title' => '新会员注册公告'],
        ]);

        Setting::put('member_tools', [
            ['src' => '/static/system/icons/t1.png', 'url' => '/pages/index/shop', 'color' => '', 'remark' => '编辑店铺', 'sort' => '1', 'status' => '1'],
            ['src' => '/static/system/icons/t2.png', 'url' => '/pages/center/tixian', 'color' => '', 'remark' => '提现收益', 'sort' => '2', 'status' => '1'],
        ]);

        Setting::put('auto_settle', 0);
        Setting::put('platform_profit', 0);
    }
}
