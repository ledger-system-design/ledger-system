<?php

namespace App\Services;

use App\Models\Balance;
use App\Models\Member;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * 流水收益结算服务
 *
 * 流水溢价 4% 的分配（见需求文档）：
 *   参与者 1.5%（订单收益） + 店长 1.5% + 直推人 0.5% + 平台 0.5%
 *
 * 推荐关系：
 *   第一代（我） ← 第二代（我的直推） ← 第三代（我的间推）
 *   第二代给第一代带来收益；第三代只给第二代带来收益，不给第一代带来收益。
 */
class FlowService
{
    /** 全局任务收益率（管理员可改，1% ~ 100%） */
    public function globalRate(): float
    {
        $v = Setting::get('order_rate');

        if ($v === null || $v === '') {
            return (float) config('flow.order_rate');
        }

        return max(0.0001, (float) $v);
    }

    /** 会员收益率：专属优先，未设置则跟随全局 */
    public function rateFor(?Member $member): float
    {
        if ($member && $member->rate !== null && (float) $member->rate > 0) {
            return (float) $member->rate;
        }

        return $this->globalRate();
    }

    /**
     * 入账：写资金明细并更新会员余额
     *
     * @param  bool  $toBalance  false 时只累计收益统计（bonus1/bonus2）与明细，不改变可提现余额
     */
    public function credit(Member $member, float $amount, int $typeid, string $remark = '', string $remark1 = '', int $type = 1, string $account = 'account1', bool $toBalance = true): ?Balance
    {
        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($member, $amount, $typeid, $remark, $remark1, $type, $account, $toBalance) {
            $member->refresh();
            $field = $account === 'account2' ? 'balance2' : 'balance1';
            $pre = (float) $member->{$field};
            $after = $type === 1 ? $pre + $amount : $pre - $amount;

            if ($toBalance) {
                $member->{$field} = $after;
            } else {
                // 不进余额：明细上的变动前后都记为当前余额，避免账面对不上
                $after = $pre;
            }

            if ($type === 1) {
                if ($typeid === (int) config('flow.type_order')) {
                    $member->bonus1 = (float) $member->bonus1 + $amount;
                } elseif ($typeid === (int) config('flow.type_direct')) {
                    $member->bonus2 = (float) $member->bonus2 + $amount;
                }
            } elseif ($toBalance) {
                $member->withdrawed = (float) $member->withdrawed + $amount;
            }
            $member->save();

            return Balance::create([
                'memberid' => $member->id,
                'amount' => $amount,
                'type' => $type,
                'typeid' => $typeid,
                'account' => $account,
                'prebalance' => $pre,
                'balance' => $after,
                'remark' => $remark,
                'remark1' => $remark1,
                'calcmonth' => (int) date('Ym'),
                'calcdate' => (int) date('Ymd'),
                'addtime' => now(),
            ]);
        });
    }

    /**
     * 计算一笔订单到账后各层级应得的奖励（不写库，用于后台预览）
     *
     *  @return array{rows: array<int, array>, total: float, platform: float}
     */
    public function previewRewards(Order $order): array
    {
        $amount = (float) $order->amount;
        $buyer = Member::find($order->memberid);

        $rows = [];
        if (! $buyer) {
            return ['rows' => [], 'total' => 0.0, 'platform' => 0.0, 'rate' => 0.0, 'profit' => 0.0];
        }

        // 收益率：优先用下单时冻结的值，其次取会员专属 / 全局收益率
        $rate = (float) $order->rate > 0 ? (float) $order->rate : $this->rateFor($buyer);

        $direct = $buyer->pid ? Member::find($buyer->pid) : null;
        $shopId = $buyer->fid ?: $buyer->pid;
        $shop = $shopId ? Member::find($shopId) : null;

        $rows[] = [
            'level' => '参与者（本人参与收益）',
            'memberid' => $buyer->id,
            'name' => $buyer->userreal ?: $buyer->nickname,
            'username' => $buyer->username,
            'rate' => $rate,
            'typeid' => (int) config('flow.type_order'),
            'amount' => round($amount * $rate, 2),
        ];

        if ($direct) {
            $rows[] = [
                'level' => '第一代 / 直推人',
                'memberid' => $direct->id,
                'name' => $direct->userreal ?: $direct->nickname,
                'username' => $direct->username,
                'rate' => (float) config('flow.direct_rate'),
                'typeid' => (int) config('flow.type_direct'),
                'amount' => round($amount * (float) config('flow.direct_rate'), 2),
            ];
        }

        if ($shop && (int) $shop->id !== (int) $buyer->id) {
            // 同一个人同时担任「店长」和「直推人」时，两个角色的分成叠加发放
            $rows[] = [
                'level' => '店长（店铺收益）',
                'memberid' => $shop->id,
                'name' => $shop->userreal ?: $shop->nickname,
                'username' => $shop->username,
                'rate' => (float) config('flow.shop_rate'),
                'typeid' => (int) config('flow.type_shop'),
                'amount' => round($amount * (float) config('flow.shop_rate'), 2),
            ];
        }

        $total = array_sum(array_column($rows, 'amount'));

        return [
            'rows' => $rows,
            'total' => round($total, 2),
            'platform' => round($amount * (float) config('flow.platform_rate'), 2),
            'rate' => $rate,
            'profit' => round($amount * $rate, 2),
            'return' => round($amount + $amount * $rate, 2),
        ];
    }

    /**
     * 确认到账并结算一笔采购订单：
     *  - 参与者（买家）获得「本金 × 收益率」的本人参与收益（只累计收益，不进可提现余额）
     *  - 第一代 / 直推人获得 0.5% 直推佣金（进余额）
     *  - 店长获得 1.5% 店铺收益（进余额）
     *  - 平台获得 0.5%
     *
     * 客户的余额口径：余额只显示「直推的人参与进来的奖励收益」，
     * 因此本人参与收益不写入 balance1，只累加到 bonus1 与资金明细里。
     *
     *  @return array 各层级实发明细
     */
    public function settleOrder(Order $order, int $adminId = 0): array
    {
        if ((int) $order->status === 3) {
            return [];
        }

        $preview = $this->previewRewards($order);
        $orderno = $order->orderno;
        $paid = [];

        $typeOrder = (int) config('flow.type_order');
        $typeDirect = (int) config('flow.type_direct');

        foreach ($preview['rows'] as $row) {
            $member = Member::find($row['memberid']);
            if (! $member) {
                continue;
            }

            $isBuyerProfit = $row['typeid'] === $typeOrder;

            $label = $isBuyerProfit
                ? '本人参与收益'
                : ($row['typeid'] === $typeDirect ? '直推佣金' : '店长收益');

            $this->credit(
                $member,
                $row['amount'],
                $row['typeid'],
                $orderno,
                "【{$orderno}】{$label}",
                1,
                'account1',
                ! $isBuyerProfit   // 本人参与收益不进可提现余额
            );

            $row['label'] = $label;
            $row['in_balance'] = ! $isBuyerProfit;
            $paid[] = $row;
        }

        // 平台收益记入系统配置，便于后台对账
        Setting::put('platform_profit', round((float) Setting::get('platform_profit', 0) + $preview['platform'], 2));

        $order->rate = $preview['rate'];
        $order->profit = $preview['profit'];
        $order->status = 3;
        $order->confirmtime = $order->confirmtime ?: now();
        $order->arrived_at = now();
        $order->settled_by = $adminId;
        $order->settle_amount = $preview['total'];
        $order->calcdate = (int) date('Ymd');
        $order->save();

        return $paid;
    }

    /**
     * 取消到账：把该订单已经发放的各层级奖励全额冲回
     */
    public function revertOrder(Order $order, bool $keepStatus = false): array
    {
        if ((int) $order->status !== 3) {
            return [];
        }

        $rows = Balance::query()->where('remark', $order->orderno)->get();
        $reverted = [];

        $typeOrder = (int) config('flow.type_order');

        foreach ($rows as $row) {
            $member = Member::find($row->memberid);
            if (! $member) {
                continue;
            }

            $this->credit(
                $member,
                (float) $row->amount,
                (int) $row->typeid,
                $order->orderno,
                '【' . $order->orderno . '】冲回（取消到账）',
                0,
                'account1',
                (int) $row->typeid !== $typeOrder   // 本人参与收益本来就没进余额，冲回也不动余额
            );

            $reverted[] = [
                'memberid' => $row->memberid,
                'name' => $member->userreal ?: $member->nickname,
                'amount' => (float) $row->amount,
                'typeid' => $row->typeid,
            ];
        }

        // 同步扣减累计收益统计
        $orderRate = (int) config('flow.type_order');
        $directRate = (int) config('flow.type_direct');
        foreach ($reverted as $r) {
            $member = Member::find($r['memberid']);
            if (! $member) {
                continue;
            }
            if ((int) $r['typeid'] === $orderRate) {
                $member->bonus1 = max(0, round((float) $member->bonus1 - $r['amount'], 2));
            } elseif ((int) $r['typeid'] === $directRate) {
                $member->bonus2 = max(0, round((float) $member->bonus2 - $r['amount'], 2));
            }
            $member->save();
        }

        // 冲回平台收益
        $platform = round((float) $order->amount * (float) config('flow.platform_rate'), 2);
        Setting::put('platform_profit', max(0, round((float) Setting::get('platform_profit', 0) - $platform, 2)));

        if (! $keepStatus) {
            $order->status = 2;
        }
        $order->arrived_at = null;
        $order->settled_by = 0;
        $order->settle_amount = 0;
        $order->save();

        return $reverted;
    }

    /** 卖家确认收款 */
    public function confirmOrder(Order $order): bool
    {
        if ((int) $order->status !== 1) {
            return false;
        }

        $order->status = 2;
        $order->confirmtime = now();
        $order->save();

        return true;
    }

    /**
     * 为会员生成一条当日采购任务。
     * 卖家从同店成员中挑选（互付流水，形成真实的收付数据）。
     */
    public function createDailyOrder(Member $buyer, ?float $amount = null, ?Member $seller = null): Order
    {
        $amount = $amount ?: (float) config('flow.daily_default');

        if (! $seller) {
            $seller = Member::query()
                ->where('id', '!=', $buyer->id)
                ->where('iscert', 1)
                ->where('status', 1)
                ->when($buyer->fid, fn ($q) => $q->where('fid', $buyer->fid))
                ->inRandomOrder()
                ->first();
        }

        if (! $seller) {
            $seller = Member::query()->where('id', '!=', $buyer->id)->inRandomOrder()->first();
        }

        $orderno = 'XS' . date('Ymd') . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        $rate = $this->rateFor($buyer);

        return Order::create([
            'orderno' => $orderno,
            'memberid' => $buyer->id,
            'sellerid' => $seller ? $seller->id : $buyer->id,
            'name' => '采购货款',
            'indexpic' => '/static/img/no_img.png',
            'amount' => $amount,
            'rate' => $rate,
            'profit' => round($amount * $rate, 2),
            'status' => 0,
            'calcdate' => (int) date('Ymd'),
            'addtime' => now(),
        ]);
    }

    /** 生成 8 位推荐码 */
    public function makeInviteCode(): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 8));
        } while (Member::query()->where('invitecode', $code)->exists());

        return $code;
    }
}
