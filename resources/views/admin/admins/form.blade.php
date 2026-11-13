@extends('admin.layout')

@section('title', $admin ? '编辑管理员' : '新增管理员')

@section('content')
    <form class="card" method="post" action="{{ $admin ? route('admin.admins.update', $admin->id) : route('admin.admins.store') }}">
        @csrf

        <div class="card__head">
            <div class="card__title">{{ $admin ? '编辑管理员 · ' . $admin->username : '新增管理员' }}</div>
            <div class="btn-row">
                <a class="btn btn--sm" href="{{ route('admin.admins.index') }}">返回列表</a>
                <button class="btn btn--primary btn--sm" type="submit">保存</button>
            </div>
        </div>

        <div class="card__body">
            <div class="form-grid">
                <div class="field">
                    <label>登录用户名 <span style="color:#c82f23;">*</span></label>
                    <input type="text" name="username" value="{{ old('username', $admin->username ?? '') }}" required minlength="3">
                </div>
                <div class="field">
                    <label>显示名称</label>
                    <input type="text" name="name" value="{{ old('name', $admin->name ?? '') }}">
                </div>
                <div class="field">
                    <label>{{ $admin ? '新密码（留空不修改）' : '登录密码' }} @if (!$admin)<span style="color:#c82f23;">*</span>@endif</label>
                    <input type="password" name="password" {{ $admin ? '' : 'required' }} minlength="6" autocomplete="new-password">
                </div>
                <div class="field">
                    <label>确认密码</label>
                    <input type="password" name="password_confirmation" autocomplete="new-password">
                </div>
                <div class="field">
                    <label>类型</label>
                    <select name="is_super">
                        <option value="0" @selected(!($admin->is_super ?? 0))>普通管理员</option>
                        <option value="1" @selected(($admin->is_super ?? 0))>超级管理员</option>
                    </select>
                </div>
                <div class="field">
                    <label>状态</label>
                    <select name="status">
                        <option value="1" @selected(($admin->status ?? 1))>正常</option>
                        <option value="0" @selected(!($admin->status ?? 1))>禁用</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card__body" style="border-top:1px solid var(--border);">
            <div class="btn-row">
                <button class="btn btn--primary" type="submit">保存</button>
                <a class="btn" href="{{ route('admin.admins.index') }}">取消</a>
            </div>
        </div>
    </form>
@endsection
