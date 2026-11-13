<?php

namespace App\Support;

/**
 * 表单入参清理
 *
 * Laravel 默认启用了 ConvertEmptyStringsToNull：表单里留空的输入会变成 null，
 * 直接 fill() 进数据库会触发 NOT NULL 约束（SQLite 报
 * "NOT NULL constraint failed"，线上就是 500）。
 * 统一用这里的方法把 null 还原成空字符串，避免把「用户没填」变成系统错误。
 */
class Inputs
{
    /**
     * 把指定键的 null 值转成空字符串（键不存在时补空字符串）
     *
     * @param  array<int, string>  $keys
     */
    public static function strings(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            $data[$key] = (string) ($data[$key] ?? '');
        }

        return $data;
    }

    /**
     * 只处理已存在的键，不补新键
     *
     * @param  array<int, string>  $keys
     */
    public static function nullToEmpty(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] === null) {
                $data[$key] = '';
            }
        }

        return $data;
    }

    /** 空字符串按默认值处理 */
    public static function orDefault(?string $value, string $default): string
    {
        $value = (string) $value;

        return $value === '' ? $default : $value;
    }
}
