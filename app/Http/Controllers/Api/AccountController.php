<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Support\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AccountController extends Controller
{
    /** account/smscode —— 发送短信验证码（演示环境直接返回验证码） */
    public function smscode(Request $request)
    {
        $telephone = trim((string) $request->input('telephone', $request->input('username', '')));

        if (! preg_match('/^1[3-9]\d{9}$/', $telephone)) {
            return Api::fail('手机号格式有误');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put('smscode:' . $telephone, $code, now()->addMinutes(10));

        $exists = Member::query()->where('username', $telephone)->exists();

        return Api::ok([
            'telephone' => $telephone,
            'code' => $code,
            'exists' => $exists,
        ], '已成功发送');
    }

    /** 校验验证码 */
    public static function verify(string $telephone, string $code): bool
    {
        $saved = Cache::get('smscode:' . $telephone);

        return $saved !== null && (string) $saved === (string) $code;
    }
}
