<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Support\Api;
use App\Support\Jwt;
use Illuminate\Http\Request;

/**
 * API 分发器
 *
 * 参考站（uni-app H5）的接口是「模块/方法」形式且大小写混用
 * （例如 Login/login、index/setting、Index/indexdata、member/balance），
 * 这里统一按「不区分大小写」的模块/方法进行路由分发。
 */
class RouteController extends Controller
{
    /** 无需登录即可访问的接口 */
    private const PUBLIC_ROUTES = [
        'login.login',
        'login.register',
        'index.setting',
        'tool.pcdjson',
        'account.smscode',
    ];

    /** 允许匿名访问，但已登录时注入会员信息的接口 */
    private const OPTIONAL_AUTH = [
        'index.indexdata',
        'file.upload',
    ];

    public function dispatch(Request $request, string $module, string $action)
    {
        $mod = strtolower($module);
        $act = strtolower($action);
        $key = $mod . '.' . $act;

        $map = [
            'login' => [
                'login' => [LoginController::class, 'login'],
                'register' => [LoginController::class, 'register'],
            ],
            'index' => [
                'setting' => [IndexController::class, 'setting'],
                'indexdata' => [IndexController::class, 'indexdata'],
            ],
            'member' => [
                'usercenter' => [MemberController::class, 'usercenter'],
                'balance' => [MemberController::class, 'balance'],
                'cash' => [MemberController::class, 'cash'],
                'shopincome' => [MemberController::class, 'shopincome'],
                'profile' => [MemberController::class, 'profile'],
                'order1' => [MemberController::class, 'order1'],
                'cert' => [MemberController::class, 'cert'],
                'children' => [MemberController::class, 'children'],
                'address' => [MemberController::class, 'address'],
            ],
            'tool' => [
                'pcdjson' => [ToolController::class, 'pcdjson'],
            ],
            'account' => [
                'smscode' => [AccountController::class, 'smscode'],
            ],
            'file' => [
                'upload' => [FileController::class, 'upload'],
            ],
        ];

        if (! isset($map[$mod][$act])) {
            return Api::noMethod();
        }

        $authRequired = ! in_array($key, self::PUBLIC_ROUTES, true) && ! in_array($key, self::OPTIONAL_AUTH, true);
        $optional = in_array($key, self::OPTIONAL_AUTH, true);

        if ($authRequired || $optional) {
            $member = $this->resolveMember($request);

            if (! $member && $authRequired) {
                return Api::notLogin();
            }

            if ($member) {
                $request->attributes->set('member', $member);
                $request->setUserResolver(fn () => $member);
                app()->instance('currentMember', $member);
            }
        }

        [$class, $method] = $map[$mod][$act];

        return app()->call([app($class), $method], ['request' => $request]);
    }

    private function resolveMember(Request $request): ?Member
    {
        $token = $request->bearerToken() ?: $request->input('skey') ?: $request->header('skey');
        $payload = Jwt::decode(is_string($token) ? $token : null);

        if (! $payload || empty($payload['extend']['id'])) {
            return null;
        }

        $member = Member::find($payload['extend']['id']);

        return ($member && (int) $member->status === 1) ? $member : null;
    }
}
