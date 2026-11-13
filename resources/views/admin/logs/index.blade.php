@extends('admin.layout')

@section('title', '操作日志')

@section('content')
    <div class="card">
        <div class="card__head">
            <div class="card__title">后台操作日志</div>
        </div>

        <div class="card__body">
            <form class="filter-bar" method="get">
                <div class="field" style="min-width:240px;">
                    <label>关键词</label>
                    <input type="text" name="kw" value="{{ request('kw') }}" placeholder="操作说明 / 管理员 / 动作">
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <div class="btn-row">
                        <button class="btn btn--primary" type="submit">筛选</button>
                        <a class="btn" href="{{ route('admin.logs.index') }}">重置</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card__body card__body--flush">
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr><th>ID</th><th>管理员</th><th>动作</th><th>说明</th><th>对象</th><th>IP</th><th>时间</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="mono">{{ $log->id }}</td>
                                <td>{{ $log->admin_name ?: '-' }}</td>
                                <td><span class="badge">{{ $log->action }}</span></td>
                                <td class="wrap">{{ $log->description }}</td>
                                <td class="small muted">{{ $log->target_type }}{{ $log->target_id ? ' #' . $log->target_id : '' }}</td>
                                <td class="mono small">{{ $log->ip }}</td>
                                <td class="small">{{ optional($log->created_at)->format('Y-m-d H:i:s') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="empty">暂无操作日志</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination">{{ $logs->links('admin.pagination') }}</div>
    </div>
@endsection
