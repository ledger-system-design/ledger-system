<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;

/**
 * 超级管理员校验
 *
 * 客户要求：可以有 5 个管理员账号，其中只有 1 个是超级管理员；
 * 普通管理员登录后台后只能给用户安排任务（采购清单 / 采购记录），
 * 其余功能（用户数据、资金、提现、设置、日志、管理员账号等）仅超级管理员可用。
 */
class SuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $admin = $request->attributes->get('admin');

        if (! $admin instanceof Admin) {
            $admin = Admin::find((int) (session('admin.id') ?? 0));
        }

        if (! $admin || ! $admin->isSuper()) {
            $msg = '该功能仅超级管理员可用，普通管理员只能给用户安排任务';

            if ($request->expectsJson()) {
                return response()->json(['code' => 1, 'msg' => $msg, 'data' => ''], 200);
            }

            return response()->view('admin.error', [
                'message' => $msg,
                'hint' => '如需开通权限，请让超级管理员在「管理员账号」中调整。',
                'backUrl' => route('admin.orders.index'),
            ], 200);
        }

        return $next($request);
    }
}
