<?php

/**
 * 演示 / 测试数据（默认不执行）
 *
 * 仅在需要造测试数据时手动执行：
 *     php artisan db:seed --class=DemoSeeder
 */
namespace Database\Seeders;

use App\Models\Address;
use App\Models\Balance;
use App\Models\Cert;
use App\Models\Member;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminSeeder::class);
        $this->settings();
        $members = $this->members();
        $this->certs($members);
        $this->orders($members);
        $this->addresses();
    }

    /* ------------------------------------------------------------------ */

    private function settings(): void
    {
        Setting::put('index_title', '锦金醇馆');
        Setting::put('index_logo', '/static/img/logo.png');

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

        Setting::put('platform_profit', 0);
    }

    /**
     * 会员结构：
     *   店长 郑军（平台 VIP）
     *   演示账号 19183083570 郑军（第一代）
     *   第二代直推：王显友、夏莉华、杨萍、秦礼蓉、李纪丰、王小红、杨霜、胡凤均、唐龙兵
     *   第三代间推：由第二代发展（只给第二代带来收益，不给第一代）
     */
    private function members(): array
    {
        $now = now();

        $shop = Member::create([
            'pid' => 0,
            'fid' => 0,
            'unionid' => '1780500001',
            'username' => '13800000001',
            'userpwd' => md5('Ac123456'),
            'userreal' => '郑军',
            'nickname' => 'VIP店长',
            'telephone' => '13800000001',
            'invitecode' => 'VIP00001',
            'gradename' => 'VIP',
            'balance1' => 128680.00,
            'balance2' => 8660.00,
            'iscert' => 1,
            'is_shop' => 1,
            'status' => 1,
            'logintimes' => 12,
            'addtime' => $now->copy()->subDays(220),
        ]);

        $me = Member::create([
            'pid' => $shop->id,
            'fid' => $shop->id,
            'unionid' => '1780501795',
            'username' => '19183083570',
            'userpwd' => md5('Ac123456'),
            'userreal' => '郑军',
            'nickname' => 'user_FeTPAg',
            'telephone' => '19183083570',
            'invitecode' => 'XK4HJ7Y2',
            'gradename' => 'VIP',
            'balance1' => 0,
            'balance2' => 11576.65,
            'iscert' => 1,
            'status' => 1,
            'logintimes' => 135,
            'addtime' => $now->copy()->subDays(180),
        ]);

        $direct = [
            ['15555282872', '王显友', 0],
            ['13558820716', '夏莉华', 0],
            ['19965881817', '杨萍', 0],
            ['18113937266', '秦礼蓉', 7],
            ['18881018719', '李纪丰', 1],
            ['18783985001', '王小红', 4],
            ['13658081229', '杨霜', 0],
            ['18090590060', '胡凤均', 3],
            ['13890705052', '唐龙兵', 0],
        ];

        $all = [$shop, $me];

        foreach ($direct as $i => [$phone, $name, $grand]) {
            $m = Member::create([
                'pid' => $me->id,
                'fid' => $shop->id,
                'unionid' => (string) (1780501800 + $i),
                'username' => $phone,
                'userpwd' => md5('Ac123456'),
                'userreal' => $name,
                'nickname' => 'user_' . strtoupper(substr(md5($phone), 0, 6)),
                'telephone' => $phone,
                'invitecode' => 'INV' . str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                'gradename' => 'VIP',
                'balance1' => round(120 + $i * 63.7, 2),
                'balance2' => round(1200 + $i * 380, 2),
                'iscert' => 1,
                'status' => 1,
                'logintimes' => random_int(20, 180),
                'addtime' => $now->copy()->subDays(max(5, 170 - $i * 8)),
            ]);
            $all[] = $m;

            // 间推（第三代）
            for ($g = 0; $g < $grand; $g++) {
                $gphone = '15' . str_pad((string) (100000000 + $i * 10 + $g), 9, '0', STR_PAD_LEFT);
                $all[] = Member::create([
                    'pid' => $m->id,
                    'fid' => $shop->id,
                    'unionid' => (string) (1780600000 + $i * 10 + $g),
                    'username' => $gphone,
                    'userpwd' => md5('Ac123456'),
                    'userreal' => $name . '团队' . ($g + 1),
                    'nickname' => 'user_' . strtoupper(substr(md5($gphone), 0, 6)),
                    'telephone' => $gphone,
                    'invitecode' => 'INV' . str_pad((string) (100 + $i * 10 + $g), 5, '0', STR_PAD_LEFT),
                    'gradename' => 'VIP',
                    'balance1' => round(80 + $g * 41.5, 2),
                    'balance2' => 0,
                    'iscert' => 1,
                    'status' => 1,
                    'logintimes' => random_int(5, 60),
                    'addtime' => $now->copy()->subDays(max(3, 120 - $i * 5 - $g)),
                ]);
            }
        }

        return $all;
    }

    private function certs(array $members): void
    {
        foreach ($members as $m) {
            $payinfo = [
                [
                    'type' => 'bank',
                    'name' => '浙江网商银行',
                    'cardbank' => '浙江支付宝网商银行',
                    'cardno' => '6225' . str_pad((string) (1000000000000 + $m->id * 137), 12, '0', STR_PAD_LEFT),
                    'carduser' => $m->userreal,
                    'image' => '',
                ],
                ['type' => 'wechat', 'name' => '微信商家收款码', 'image' => $this->qr('wechat-' . $m->id, $m->userreal, '#07C160')],
                ['type' => 'alipay', 'name' => '支付宝', 'account' => $m->telephone, 'image' => $this->qr('alipay-' . $m->id, $m->userreal, '#1677FF')],
                ['type' => 'url', 'name' => '支付宝远程收款码', 'url' => $this->qr('remote-' . $m->id, $m->userreal, '#1677FF'), 'image' => $this->qr('remote-' . $m->id, $m->userreal, '#1677FF')],
                ['type' => 'url', 'name' => '银联收款码', 'url' => $this->qr('union-' . $m->id, $m->userreal, '#C82F23'), 'image' => $this->qr('union-' . $m->id, $m->userreal, '#C82F23')],
            ];

            Cert::create([
                'memberid' => $m->id,
                'license' => '/static/img/no_img.png',
                'shopinfo' => ['shopname' => $m->userreal . '的店铺', 'telephone' => $m->telephone],
                'payinfo' => $payinfo,
                'iscert' => 1,
                'addtime' => $m->addtime,
            ]);
        }
    }

    /** 生成收款二维码图片（QR 风格图，无需外部依赖） */
    private function qr(string $seed, string $name, string $color): string
    {
        $dir = public_path('uploads/qrcode');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = md5($seed) . '.png';
        $path = $dir . '/' . $file;

        if (is_file($path)) {
            return '/uploads/qrcode/' . $file;
        }

        $size = 25;
        $scale = 12;
        $imgSize = $size * $scale;
        $img = imagecreatetruecolor($imgSize, $imgSize);
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 0, 0, $imgSize, $imgSize, $white);

        $rgb = sscanf($color, '#%02x%02x%02x');
        $dark = imagecolorallocate($img, (int) $rgb[0], (int) $rgb[1], (int) $rgb[2]);

        mt_srand(crc32($seed)); // 稳定可复现的伪随机
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $on = mt_rand(0, 100) > 48;

                $inFinder = ($x < 7 && $y < 7) || ($x >= $size - 7 && $y < 7) || ($x < 7 && $y >= $size - 7);
                if ($inFinder) {
                    $lx = $x < 7 ? $x : $x - ($size - 7);
                    $ly = $y < 7 ? $y : $y - ($size - 7);
                    $on = ($lx === 0 || $lx === 6 || $ly === 0 || $ly === 6)
                        || ($lx >= 2 && $lx <= 4 && $ly >= 2 && $ly <= 4);
                }

                if ($on) {
                    imagefilledrectangle(
                        $img,
                        $x * $scale,
                        $y * $scale,
                        ($x + 1) * $scale - 1,
                        ($y + 1) * $scale - 1,
                        $dark
                    );
                }
            }
        }
        mt_srand();

        // 中央留白并写入标识文字
        $boxW = (int) ($imgSize * 0.42);
        $boxH = (int) ($imgSize * 0.16);
        $x0 = (int) (($imgSize - $boxW) / 2);
        $y0 = (int) (($imgSize - $boxH) / 2);
        imagefilledrectangle($img, $x0, $y0, $x0 + $boxW, $y0 + $boxH, $white);
        $text = $name !== '' ? mb_substr($name, 0, 2) : '收款';
        imagestring($img, 5, (int) ($imgSize / 2 - 12), (int) ($imgSize / 2 - 8), $text, $dark);

        imagepng($img, $path);
        imagedestroy($img);

        return '/uploads/qrcode/' . $file;
    }

    /**
     * 采购流水订单：演示账号保留全部四种状态，
     * 历史订单用于支撑订单收益、直推收益和团队采购统计。
     */
    private function orders(array $members): void
    {
        $me = Member::where('username', '19183083570')->first();
        $others = array_values(array_filter($members, fn ($m) => (int) $m->id !== (int) $me->id));

        $seq = 1;
        $makeNo = function () use (&$seq) {
            return 'XS' . date('Ymd') . str_pad((string) ($seq++), 6, '0', STR_PAD_LEFT);
        };

        // 待付款 3 笔
        foreach ([[3000, 0], [2000, 1], [4500, 2]] as [$amount, $days]) {
            Order::create([
                'orderno' => $makeNo(),
                'memberid' => $me->id,
                'sellerid' => $others[array_rand($others)]->id,
                'name' => '采购货款',
                'indexpic' => '/static/img/no_img.png',
                'amount' => $amount,
                'status' => 0,
                'calcdate' => (int) date('Ymd', strtotime("-{$days} day")),
                'addtime' => now()->subDays($days)->setTime(9, 30),
            ]);
        }

        // 待确认 1 笔
        Order::create([
            'orderno' => $makeNo(),
            'memberid' => $me->id,
            'sellerid' => $others[0]->id,
            'name' => '采购货款',
            'indexpic' => '/static/img/no_img.png',
            'amount' => 3500,
            'status' => 1,
            'payphoto' => '/static/img/no_img.png',
            'paytime' => now()->subMinutes(2),
            'calcdate' => (int) date('Ymd'),
            'addtime' => now()->subHours(3),
        ]);

        // 已确认 1 笔
        Order::create([
            'orderno' => $makeNo(),
            'memberid' => $me->id,
            'sellerid' => $others[1]->id,
            'name' => '采购货款',
            'indexpic' => '/static/img/no_img.png',
            'amount' => 4000,
            'status' => 2,
            'payphoto' => '/static/img/no_img.png',
            'paytime' => now()->subMinutes(40),
            'confirmtime' => now()->subMinutes(2),
            'calcdate' => (int) date('Ymd'),
            'addtime' => now()->subHours(5),
        ]);

        // 历史已完成订单 + 收益明细
        $balanceRows = [];
        $bonus1 = 0.0;
        $bonus2 = 0.0;

        for ($d = 66; $d >= 1; $d--) {
            $day = now()->subDays($d);
            $calcdate = (int) $day->format('Ymd');
            $calcmonth = (int) $day->format('Ym');

            for ($k = 0, $myCount = random_int(1, 2); $k < $myCount; $k++) {
                $amount = random_int(4, 10) * 500;

                Order::create([
                    'orderno' => $makeNo(),
                    'memberid' => $me->id,
                    'sellerid' => $others[array_rand($others)]->id,
                    'name' => '采购货款',
                    'indexpic' => '/static/img/no_img.png',
                    'amount' => $amount,
                    'status' => 3,
                    'payphoto' => '/static/img/no_img.png',
                    'paytime' => $day->copy()->setTime(10, random_int(0, 59)),
                    'confirmtime' => $day->copy()->setTime(11, random_int(0, 59)),
                    'calcdate' => $calcdate,
                    'addtime' => $day->copy()->setTime(9, random_int(0, 59)),
                ]);

                $income = round($amount * 0.015, 2);
                $bonus1 += $income;
                $balanceRows[] = [$me->id, $income, 1, 7, $calcmonth, $calcdate, $day->copy()->setTime(11, 30), '订单收益'];
            }

            foreach ($others as $child) {
                if (random_int(1, 100) > 45) {
                    continue;
                }
                $amount = random_int(4, 10) * 500;

                Order::create([
                    'orderno' => $makeNo(),
                    'memberid' => $child->id,
                    'sellerid' => $me->id,
                    'name' => '采购货款',
                    'indexpic' => '/static/img/no_img.png',
                    'amount' => $amount,
                    'status' => 3,
                    'payphoto' => '/static/img/no_img.png',
                    'paytime' => $day->copy()->setTime(10, random_int(0, 59)),
                    'confirmtime' => $day->copy()->setTime(11, random_int(0, 59)),
                    'calcdate' => $calcdate,
                    'addtime' => $day->copy()->setTime(9, random_int(0, 59)),
                ]);

                $income = round($amount * 0.005, 2);
                $bonus2 += $income;
                $balanceRows[] = [$me->id, $income, 1, 8, $calcmonth, $calcdate, $day->copy()->setTime(11, 40), '直推佣金'];
            }
        }

        // 提现记录
        $withdrawed = 0.0;
        foreach ([[1200, 1], [800, 1], [600, 1], [500, 0]] as $i => [$amount, $status]) {
            $at = now()->subDays(max(1, 40 - $i * 9));
            \App\Models\Cash::create([
                'memberid' => $me->id,
                'amount' => $amount,
                'account' => 'account1',
                'type' => 0,
                'status' => $status,
                'remark' => $status === 1 ? '提现已到账' : '提现申请',
                'addtime' => $at,
            ]);

            if ($status === 1) {
                $withdrawed += $amount;
                $balanceRows[] = [$me->id, $amount, 0, 21, (int) $at->format('Ym'), (int) $at->format('Ymd'), $at, '提现'];
            }
        }

        // 按时间正序累计余额
        usort($balanceRows, fn ($a, $b) => $a[6] <=> $b[6]);

        $running = 0.0;
        $insert = [];
        foreach ($balanceRows as [$memberid, $amount, $type, $typeid, $calcmonth, $calcdate, $addtime, $remark1]) {
            $pre = $running;
            $running = $type === 1 ? $pre + $amount : $pre - $amount;
            $insert[] = [
                'memberid' => $memberid,
                'amount' => $amount,
                'type' => $type,
                'typeid' => $typeid,
                'account' => 'account1',
                'prebalance' => $pre,
                'balance' => $running,
                'remark' => '',
                'remark1' => $remark1,
                'calcmonth' => $calcmonth,
                'calcdate' => $calcdate,
                'addtime' => $addtime,
                'created_at' => $addtime,
                'updated_at' => $addtime,
            ];
        }

        foreach (array_chunk($insert, 200) as $chunk) {
            Balance::insert($chunk);
        }

        $me->balance1 = round($running, 2);
        $me->bonus1 = round($bonus1, 2);
        $me->bonus2 = round($bonus2, 2);
        $me->withdrawed = $withdrawed;
        $me->save();
    }

    private function addresses(): void
    {
        $me = Member::where('username', '19183083570')->first();

        Address::create([
            'memberid' => $me->id,
            'userreal' => '郑军',
            'telephone' => '19183083570',
            'region' => '四川省,成都市,武侯区',
            'address' => '天府大道北段 1480 号 3 栋 1 单元 1802',
            'isdefault' => 1,
            'addtime' => now()->subDays(60),
        ]);

        Address::create([
            'memberid' => $me->id,
            'userreal' => '郑军',
            'telephone' => '19183083570',
            'region' => '四川省,成都市,锦江区',
            'address' => '红星路三段 1 号 IFS 国际金融中心 2808',
            'isdefault' => 0,
            'addtime' => now()->subDays(30),
        ]);
    }
}
