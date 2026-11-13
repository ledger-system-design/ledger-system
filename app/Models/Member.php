<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $table = 'members';

    protected $guarded = [];

    protected $hidden = ['userpwd'];

    protected $casts = [
        'balance1' => 'float',
        'balance2' => 'float',
        'bonus1' => 'float',
        'bonus2' => 'float',
        'withdrawed' => 'float',
        'rate' => 'float',
        'addtime' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;

    /** 直推人（第一代上级） */
    public function parent()
    {
        return $this->belongsTo(Member::class, 'pid');
    }

    /** 直接推荐的人（第二代） */
    public function children()
    {
        return $this->hasMany(Member::class, 'pid');
    }

    /** 间推（第三代，仅用于服务费分配，不给第一代产生任务收益） */
    public function grandchildren()
    {
        return $this->hasManyThrough(Member::class, Member::class, 'pid', 'pid', 'id', 'id');
    }

    /** 手机号脱敏：19183083570 => 191****3570 */
    public function maskedUsername(): string
    {
        $u = (string) $this->username;
        if (strlen($u) < 7) {
            return $u;
        }

        return substr($u, 0, 3) . '****' . substr($u, -4);
    }
}
