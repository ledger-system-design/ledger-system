<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\FlowService;
use Illuminate\Console\Command;

/**
 * 手工推进订单结算（相当于店长在后台确认收款）
 *
 * 用法：
 *   php artisan flow:settle            # 推进全部可结算订单
 *   php artisan flow:settle --seconds=0 --force
 */
class FlowSettle extends Command
{
    protected $signature = 'flow:settle
                            {--seconds=30 : 支付/确认后多少秒自动流转}
                            {--force : 忽略时间限制，立即推进}';

    protected $description = '确认收款并结算订单收益';

    public function handle(FlowService $flow): int
    {
        $seconds = $this->option('force') ? PHP_INT_MAX : (int) $this->option('seconds');
        $now = now();

        $confirmed = 0;
        foreach (Order::query()->where('status', 1)->get() as $order) {
            if ($order->paytime && $order->paytime->diffInSeconds($now) >= $seconds) {
                if ($flow->confirmOrder($order)) {
                    $confirmed++;
                }
            }
        }

        $settled = 0;
        foreach (Order::query()->where('status', 2)->get() as $order) {
            if ($order->confirmtime && $order->confirmtime->diffInSeconds($now) >= $seconds) {
                $flow->settleOrder($order);
                $settled++;
            }
        }

        $this->info("确认收款 {$confirmed} 笔，结算完成 {$settled} 笔。");
        $this->line('平台累计收益：' . \App\Models\Setting::get('platform_profit', 0));

        return self::SUCCESS;
    }
}
