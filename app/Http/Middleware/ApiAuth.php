<?php

namespace App\Http\Middleware;

use App\Models\Member;
use App\Support\Api;
use App\Support\Jwt;
use Closure;
use Illuminate\Http\Request;

/**
 * 会员接口鉴权：兼容 Authorization: Bearer <access_token> 与 body 中的 skey。
 */
class ApiAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (! $token) {
            $token = $request->input('skey') ?: $request->header('skey');
        }

        $payload = Jwt::decode($token);

        if (! $payload || empty($payload['extend']['id'])) {
            return Api::notLogin();
        }

        $member = Member::find($payload['extend']['id']);

        if (! $member || (int) $member->status !== 1) {
            return Api::notLogin();
        }

        $request->setUserResolver(fn () => $member);
        app()->instance('currentMember', $member);
        $request->attributes->set('member', $member);

        return $next($request);
    }
}
