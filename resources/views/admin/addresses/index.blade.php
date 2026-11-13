@extends('admin.layout')

@section('title', '收货地址')

@section('content')
    <div class="card">
        <div class="card__head">
            <div class="card__title">会员收货地址</div>
        </div>

        <div class="card__body">
            <form class="filter-bar" method="get">
                <div class="field">
                    <label>关键词</label>
                    <input type="text" name="kw" value="{{ request('kw') }}" placeholder="收货人 / 电话 / 地址 / 用户ID">
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <div class="btn-row">
                        <button class="btn btn--primary" type="submit">筛选</button>
                        <a class="btn" href="{{ route('admin.addresses.index') }}">重置</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card__body card__body--flush">
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr><th>ID</th><th>会员</th><th>收货人</th><th>电话</th><th>地区</th><th>详细地址</th><th>默认</th><th>时间</th><th>操作</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            @php $m = $members[$r->memberid] ?? null; @endphp
                            <tr>
                                <td class="mono">{{ $r->id }}</td>
                                <td>
                                    @if ($m)
                                        <a href="{{ route('admin.members.show', $m->id) }}">{{ $m->userreal ?: $m->nickname }}</a>
                                        <div class="muted small mono">{{ $m->username }}</div>
                                    @else
                                        <span class="muted">#{{ $r->memberid }}</span>
                                    @endif
                                </td>
                                <td>{{ $r->userreal }}</td>
                                <td class="mono">{{ $r->telephone }}</td>
                                <td>{{ str_replace(',', ' ', $r->region) }}</td>
                                <td class="wrap">{{ $r->address }}</td>
                                <td>{{ $r->isdefault ? '是' : '否' }}</td>
                                <td class="small">{{ optional($r->addtime)->format('Y-m-d H:i') }}</td>
                                <td>
                                    <form method="post" action="{{ route('admin.addresses.destroy', $r->id) }}"
                                        data-confirm="确定删除该地址吗？">
                                        @csrf
                                        <button class="btn btn--sm btn--danger" type="submit">删除</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="empty">暂无收货地址</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination">{{ $rows->links('admin.pagination') }}</div>
    </div>
@endsection
