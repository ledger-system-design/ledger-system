<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminLog;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index()
    {
        $admins = Admin::query()->orderByDesc('id')->get();

        return view('admin.admins.index', compact('admins'));
    }

    public function create()
    {
        return view('admin.admins.form', ['admin' => null]);
    }

    public function store(Request $request)
    {
        // 客户要求：后台最多 5 个管理员账号
        if (Admin::query()->count() >= 5) {
            return back()->withErrors(['username' => '管理员账号最多 5 个，如需新增请先删除不用的账号'])->withInput();
        }

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:64', 'unique:admins,username'],
            'name' => ['nullable', 'string', 'max:64'],
            'password' => ['required', 'string', 'min:6', 'max:128', 'confirmed'],
            'is_super' => ['nullable', 'integer', 'in:0,1'],
            'status' => ['nullable', 'integer', 'in:0,1'],
        ], [], [
            'username' => '用户名',
            'password' => '密码',
        ]);

        $admin = Admin::create([
            'username' => $data['username'],
            'name' => $data['name'] ?? '',
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'is_super' => (int) ($data['is_super'] ?? 0),
            'status' => (int) ($data['status'] ?? 1),
        ]);

        AdminLog::write('admin.store', '新增管理员 ' . $admin->username, 'admin', $admin->id);

        return redirect()->route('admin.admins.index')->with('status', '管理员已创建');
    }

    public function edit(int $id)
    {
        return view('admin.admins.form', ['admin' => Admin::findOrFail($id)]);
    }

    public function update(Request $request, int $id)
    {
        $admin = Admin::findOrFail($id);

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:64'],
            'name' => ['nullable', 'string', 'max:64'],
            'password' => ['nullable', 'string', 'min:6', 'max:128', 'confirmed'],
            'is_super' => ['nullable', 'integer', 'in:0,1'],
            'status' => ['nullable', 'integer', 'in:0,1'],
        ], [], [
            'username' => '用户名',
            'password' => '密码',
        ]);

        $exists = Admin::query()->where('username', $data['username'])->where('id', '!=', $id)->exists();
        if ($exists) {
            return back()->withErrors(['username' => '该用户名已被占用'])->withInput();
        }

        $admin->username = $data['username'];
        $admin->name = $data['name'] ?? '';
        $admin->is_super = (int) ($data['is_super'] ?? 0);
        $admin->status = (int) ($data['status'] ?? 1);
        if (! empty($data['password'])) {
            $admin->password = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $admin->save();

        AdminLog::write('admin.update', '修改管理员 ' . $admin->username, 'admin', $admin->id);

        return redirect()->route('admin.admins.index')->with('status', '管理员已保存');
    }

    public function destroy(int $id)
    {
        $admin = Admin::findOrFail($id);

        if ((int) $admin->id === (int) (session('admin.id') ?? 0)) {
            return back()->withErrors(['admin' => '不能删除当前登录的账号']);
        }

        if (Admin::count() <= 1) {
            return back()->withErrors(['admin' => '至少保留一个管理员账号']);
        }

        AdminLog::write('admin.destroy', '删除管理员 ' . $admin->username, 'admin', $id);
        $admin->delete();

        return back()->with('status', '管理员已删除');
    }
}
