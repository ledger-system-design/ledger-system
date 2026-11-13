@extends('admin.layout')

@section('title', '账号设置')

@section('content')
    <form class="card" method="post" action="{{ route('admin.profile') }}">
        @csrf

        <div class="card__head">
            <div class="card__title">修改我的用户名与密码</div>
            <button class="btn btn--primary btn--sm" type="submit">保存</button>
        </div>

        <div class="card__body">
            <div class="form-grid">
                <div class="field">
                    <label>登录用户名 <span style="color:#c82f23;">*</span></label>
                    <input type="text" name="username" value="{{ old('username', $admin->username) }}" required minlength="3">
                    <span class="hint">建议改成只有你自己知道的名字</span>
                </div>
                <div class="field">
                    <label>显示名称</label>
                    <input type="text" name="name" value="{{ old('name', $admin->name) }}">
                </div>
                <div class="field">
                    <label>当前密码</label>
                    <input type="password" name="old_password" autocomplete="current-password">
                    <span class="hint">修改密码时必须填写</span>
                </div>
                <div class="field">
                    <label>新密码</label>
                    <input type="password" name="password" minlength="6" autocomplete="new-password">
                    <span class="hint">留空表示不修改密码</span>
                </div>
                <div class="field">
                    <label>确认新密码</label>
                    <input type="password" name="password_confirmation" autocomplete="new-password">
                </div>
            </div>
        </div>

        <div class="card__body" style="border-top:1px solid var(--border);">
            <dl class="kv">
                <dt>账号 ID</dt><dd>{{ $admin->id }}</dd>
                <dt>类型</dt><dd>{{ $admin->is_super ? '超级管理员' : '普通管理员' }}</dd>
                <dt>登录次数</dt><dd>{{ $admin->logintimes }}</dd>
                <dt>最近登录</dt><dd>{{ optional($admin->last_login_at)->format('Y-m-d H:i:s') ?: '-' }}</dd>
                <dt>最近 IP</dt><dd class="mono">{{ $admin->last_login_ip ?: '-' }}</dd>
            </dl>
        </div>

        <div class="card__body" style="border-top:1px solid var(--border);">
            <button class="btn btn--primary" type="submit">保存修改</button>
        </div>
    </form>
@endsection
