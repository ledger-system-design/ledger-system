@extends('admin.layout')

@section('title', '管理员账号')

@section('content')
    <div class="card">
        <div class="card__head">
            <div class="card__title">管理员账号（可修改用户名与密码）</div>
            <a class="btn btn--primary btn--sm" href="{{ route('admin.admins.create') }}">+ 新增管理员</a>
        </div>

        <div class="card__body card__body--flush">
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr><th>ID</th><th>用户名</th><th>显示名称</th><th>类型</th><th>状态</th><th>登录次数</th><th>最近登录</th><th>登录 IP</th><th>创建时间</th><th class="text-center">操作</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($admins as $a)
                            <tr>
                                <td class="mono">{{ $a->id }}</td>
                                <td><strong>{{ $a->username }}</strong>
                                    @if ((int) $a->id === (int) session('admin.id'))
                                        <span class="badge badge--green">当前登录</span>
                                    @endif
                                </td>
                                <td>{{ $a->name ?: '-' }}</td>
                                <td>{{ $a->is_super ? '超级管理员' : '普通管理员' }}</td>
                                <td><span class="badge {{ $a->status ? 'badge--green' : 'badge--red' }}">{{ $a->status ? '正常' : '禁用' }}</span></td>
                                <td>{{ $a->logintimes }}</td>
                                <td class="small">{{ optional($a->last_login_at)->format('Y-m-d H:i:s') ?: '-' }}</td>
                                <td class="mono small">{{ $a->last_login_ip ?: '-' }}</td>
                                <td class="small">{{ optional($a->created_at)->format('Y-m-d') }}</td>
                                <td>
                                    <div class="btn-row" style="justify-content:center;">
                                        <a class="btn btn--sm" href="{{ route('admin.admins.edit', $a->id) }}">编辑 / 改密码</a>
                                        @if ((int) $a->id !== (int) session('admin.id'))
                                            <form method="post" action="{{ route('admin.admins.destroy', $a->id) }}"
                                                data-confirm="确定删除管理员 {{ $a->username }} 吗？">
                                                @csrf
                                                <button class="btn btn--sm btn--danger" type="submit">删除</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
