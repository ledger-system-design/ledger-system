<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cert extends Model
{
    protected $table = 'certs';

    protected $guarded = [];

    protected $casts = [
        'shopinfo' => 'array',
        'payinfo' => 'array',
        'addtime' => 'datetime',
    ];

    /**
     * 收款方式的默认结构（与前端"店铺认证"页面一一对应）
     *  0 => 网商银行卡
     *  1 => 微信商家收款码
     *  2 => 支付宝（账号 + 商家收款码）
     *  3 => 支付宝远程收款码
     *  4 => 银联收款码
     */
    public static function defaultPayinfo(): array
    {
        return [
            ['type' => 'bank', 'name' => '浙江网商银行', 'cardbank' => '浙江支付宝网商银行', 'cardno' => '', 'carduser' => '', 'image' => ''],
            ['type' => 'wechat', 'name' => '微信商家收款码', 'image' => ''],
            ['type' => 'alipay', 'name' => '支付宝', 'account' => '', 'image' => ''],
            ['type' => 'url', 'name' => '支付宝远程收款码', 'url' => '', 'image' => ''],
            ['type' => 'url', 'name' => '银联收款码', 'url' => '', 'image' => ''],
        ];
    }
}
