<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'float',
        'rate' => 'float',
        'profit' => 'float',
        'settle_amount' => 'float',
        'addtime' => 'datetime',
        'paytime' => 'datetime',
        'confirmtime' => 'datetime',
        'arrived_at' => 'datetime',
    ];

    /** 回款总额 = 本金 + 收益（客户口径的「收益加本金」） */
    public function returnAmount(): float
    {
        return round((float) $this->amount + (float) $this->profit, 2);
    }

    public function buyer()
    {
        return $this->belongsTo(Member::class, 'memberid');
    }

    public function seller()
    {
        return $this->belongsTo(Member::class, 'sellerid');
    }

    /** 订单状态文案：0待付款 1待确认 2已确认 3已完成 */
    public function statusText(): string
    {
        return ['待付款', '待确认', '已确认', '已完成'][$this->status] ?? '未知';
    }
}
