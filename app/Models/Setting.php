<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public static function get(string $key, $default = null)
    {
        $row = static::query()->find($key);

        return $row ? $row->value : $default;
    }

    public static function put(string $key, $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE)]);
    }

    public static function json(string $key, $default = [])
    {
        $v = static::get($key);
        if ($v === null || $v === '') {
            return $default;
        }
        $d = json_decode($v, true);

        return is_array($d) ? $d : $default;
    }
}
