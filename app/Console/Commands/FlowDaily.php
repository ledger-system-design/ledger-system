<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\Order;
use App\Services\FlowService;
use Illuminate\Console\Command;

/**
 * 生成每日采购流水任务
 *
 * 业务规则（见需求文档）：
 *   - 由店长根据店铺情况调整每位参与者的每日流水额度（2000 ~ 5000 元）；
 *   - 店铺成员之间互相收付款，形成支付宝网商银行所需的真实流水数据；
 *   - 流水产生的 4% 溢价按 参与者 1.5% / 店长 1.5% / 直推人 0.5% / 平台 0.5% 分配。
 *
 * 用法：
 *   php artisan flow:daily            # 为全部已认证会员生成当日任务
 *   php artisan flow:daily --amount=3000
 *   php artisan flow:daily --member=2
 */
class FlowDaily extends Command
{
    protected $signature = 'flow:daily
                            {--member= : 仅给指定会员生成}
                            {--amount= : 指定流水额度，缺省使用配置默认值}
                            {--force : 当日已有任务时仍然生成}';

    protected $description = '生成每日采购流水任务';

    public function handle(FlowService $flow): int
    {
        $query = Member::query()->where('status', 1)->where('iscert', 1);

        if ($memberId = $this->option('member')) {
            $query->where('id', (int) $memberId);
        }

        $amount = $this->option('amount') ? (float) $this->option('amount') : null;

        $created = 0;
        $skipped = 0;

        foreach ($query->get() as $member) {
            if (! $this->option('force')) {
                $exists = Order::query()
                    ->where('memberid', $member->id)
                    ->where('calcdate', (int) date('Ymd'))
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }
            }

            $flow->createDailyOrder($member, $amount);
            $created++;
        }

        $this->info("已生成 {$created} 条采购任务，跳过 {$skipped} 条（当日已存在）。");
        $this->line('提示：参与者完成支付后，订单会在「待确认 → 已确认 → 已完成」之间自动流转，并在完成时结算 4% 流水溢价。');

        return self::SUCCESS;
    }
}
