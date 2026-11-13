<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;

/**
 * 后台登录校验（Session 方式）
 */
class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        $sessionAdmin = $request->session()->get('admin');

        if (! $sessionAdmin || empty($sessionAdmin['id'])) {
            if ($request->expectsJson()) {
                return response()->json(['code' => 4000, 'msg' => '请先登录管理后台'], 401);
            }

            return redirect()->route('admin.login');
        }

        $admin = Admin::find($sessionAdmin['id']);

        if (! $admin || (int) $admin->status !== 1) {
            $request->session()->forget('admin');

            return redirect()->route('admin.login')->withErrors(['username' => '账号已被禁用，请重新登录']);
        }

        $request->attributes->set('admin', $admin);
        app()->instance('currentAdmin', $admin);
        view()->share('currentAdmin', $admin);

        // 侧边栏待处理数量
        try {
            view()->share('pendingRecords', \App\Models\Order::whereIn('status', [1, 2])->count());
            view()->share('pendingCashes', \App\Models\Cash::where('status', 0)->count());
        } catch (\Throwable $e) {
            view()->share('pendingRecords', 0);
            view()->share('pendingCashes', 0);
        }

        return $next($request);
    }
}
