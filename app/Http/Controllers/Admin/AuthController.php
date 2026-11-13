<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminLog;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('admin')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:64'],
            'password' => ['required', 'string', 'max:128'],
            'captcha' => ['required', 'string', 'max:8'],
        ], [
            'captcha.required' => '请输入验证码',
        ], [
            'username' => '用户名',
            'password' => '密码',
            'captcha' => '验证码',
        ]);

        // 图形验证码校验（防止暴力破解）
        if (! CaptchaController::verify($request, $data['captcha'])) {
            return redirect()->route('admin.login')->withInput()->withErrors(['captcha' => '验证码错误或已过期']);
        }

        $admin = Admin::query()->where('username', $data['username'])->first();

        if (! $admin || ! password_verify($data['password'], $admin->password)) {
            // 失败一次即作废当前验证码，必须重新获取
            return redirect()->route('admin.login')->withInput()->withErrors(['username' => '用户名或密码错误']);
        }

        if ((int) $admin->status !== 1) {
            return redirect()->route('admin.login')->withInput()->withErrors(['username' => '该管理员账号已被禁用']);
        }

        $admin->logintimes = (int) $admin->logintimes + 1;
        $admin->last_login_at = now();
        $admin->last_login_ip = $request->ip();
        $admin->save();

        $request->session()->regenerate();
        $request->session()->put('admin', [
            'id' => $admin->id,
            'username' => $admin->username,
            'name' => $admin->name,
            'is_super' => $admin->is_super,
        ]);

        AdminLog::write('login', '管理员登录后台');

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        AdminLog::write('logout', '管理员退出后台');
        $request->session()->forget('admin');
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /** 修改自己的用户名 / 密码 */
    public function profile(Request $request)
    {
        $admin = $request->attributes->get('admin');

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'username' => ['required', 'string', 'min:3', 'max:64'],
                'name' => ['nullable', 'string', 'max:64'],
                'old_password' => ['nullable', 'string'],
                'password' => ['nullable', 'string', 'min:6', 'max:128', 'confirmed'],
            ], [], [
                'username' => '用户名',
                'name' => '显示名称',
                'old_password' => '当前密码',
                'password' => '新密码',
            ]);

            if ($data['username'] !== $admin->username) {
                $exists = Admin::query()->where('username', $data['username'])->where('id', '!=', $admin->id)->exists();
                if ($exists) {
                    return back()->withErrors(['username' => '该用户名已被占用'])->withInput();
                }
            }

            if (! empty($data['password'])) {
                if (! password_verify((string) $request->input('old_password'), $admin->password)) {
                    return back()->withErrors(['old_password' => '当前密码不正确'])->withInput();
                }
                $admin->password = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $admin->username = $data['username'];
            $admin->name = $data['name'] ?? '';
            $admin->save();

            $request->session()->put('admin', [
                'id' => $admin->id,
                'username' => $admin->username,
                'name' => $admin->name,
                'is_super' => $admin->is_super,
            ]);

            AdminLog::write('admin.profile', '修改了自己的账号信息', 'admin', $admin->id, ['username' => $admin->username]);

            return redirect()->route('admin.profile')->with('status', '账号信息已更新');
        }

        return view('admin.profile', ['admin' => $admin]);
    }
}
