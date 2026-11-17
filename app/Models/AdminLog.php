<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminLog extends Model
{
    protected $table = 'admin_logs';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /** 记录一条后台操作日志 */
    public static function write(string $action, string $description, string $targetType = '', $targetId = '', array $detail = []): void
    {
        $admin = session('admin');

        static::create([
            'admin_id' => $admin['id'] ?? 0,
            'admin_name' => $admin['username'] ?? '',
            'action' => $action,
            'description' => $description,
            'target_type' => $targetType,
            'target_id' => (string) $targetId,
            'detail' => $detail ? json_encode($detail, JSON_UNESCAPED_UNICODE) : null,
            'ip' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
