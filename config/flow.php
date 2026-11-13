<?php

/**
 * 流水业务参数（依据需求文档"支付宝网商银行助贷说明"）
 */
return [
    // 参与服务费（一次性，一个月内可通过任务赚回）
    'service_fee' => 998,

    // 服务费分配：店长 700 / 直推人 200 / 间推人 98
    'fee_shop' => 700,
    'fee_direct' => 200,
    'fee_indirect' => 98,

    // 每日流水额度区间（由店长按店铺情况调整）
    'daily_min' => 2000,
    'daily_max' => 5000,
    'daily_default' => 3000,

    // 流水 4% 溢价分配
    'order_rate' => 0.015,     // 参与者（订单收益）
    'shop_rate' => 0.015,      // 店长
    'direct_rate' => 0.005,    // 直推人
    'platform_rate' => 0.005,  // 平台

    // 明细类型
    'type_order' => 7,         // 订单收益
    'type_direct' => 8,        // 直推收益
    'type_shop' => 11,         // 店长收益
    'type_platform' => 12,     // 平台收益
    'type_cash' => 21,         // 提现
    'type_fee' => 31,          // 服务费
    'type_recharge' => 1,      // 充值
];
