@extends('admin.layout')

@section('title', '收款方式')

@section('content')
    <div class="card">
        <div class="card__head">
            <div class="card__title">会员店铺认证与收款方式</div>
        </div>

        <div class="card__body">
            <form class="filter-bar" method="get">
                <div class="field">
                    <label>关键词</label>
                    <input type="text" name="kw" value="{{ request('kw') }}" placeholder="手机号 / 姓名">
                </div>
                <div class="field">
                    <label>认证状态</label>
                    <select name="iscert">
                        <option value="">全部</option>
                        <option value="1" @selected(request('iscert') === '1')>已认证</option>
                        <option value="0" @selected(request('iscert') === '0')>未认证</option>
                    </select>
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <div class="btn-row">
                        <button class="btn btn--primary" type="submit">筛选</button>
                        <a class="btn" href="{{ route('admin.certs.index') }}">重置</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card__body card__body--flush">
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>ID</th><th>会员</th><th>账号</th><th>认证</th><th>营业执照</th>
                            <th>支付宝账号</th><th>网商银行卡号</th><th>收款人</th><th>收款码</th><th class="text-center">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($members as $m)
                            @php
                                $c = $certs[$m->id] ?? null;
                                $pay = $c && is_array($c->payinfo) ? $c->payinfo : [];
                                $qr = 0;
                                foreach ($pay as $p) {
                                    if (!empty($p['image']) || !empty($p['url'])) { $qr++; }
                                }
                            @endphp
                            <tr>
                                <td class="mono">{{ $m->id }}</td>
                                <td>
                                    <a href="{{ route('admin.members.show', $m->id) }}">{{ $m->userreal ?: $m->nickname }}</a>
                                </td>
                                <td class="mono">{{ $m->username }}</td>
                                <td>
                                    <span class="badge {{ $m->iscert ? 'badge--green' : 'badge--gray' }}">{{ $m->iscert ? '已认证' : '未认证' }}</span>
                                </td>
                                <td>
                                    @if ($c && $c->license)
                                        <img class="proof-img" style="width:48px;height:48px;" src="{{ $c->license }}" onclick="zoomImage(this.src)">
                                    @else
                                        <span class="muted small">-</span>
                                    @endif
                                </td>
                                <td class="mono small">{{ $pay[2]['account'] ?? '-' }}</td>
                                <td class="mono small">{{ $pay[0]['cardno'] ?? '-' }}</td>
                                <td class="small">{{ $pay[0]['carduser'] ?? '-' }}</td>
                                <td>{{ $qr }} 个</td>
                                <td>
                                    <div class="btn-row" style="justify-content:center;">
                                        <a class="btn btn--sm btn--primary" href="{{ route('admin.certs.edit', $m->id) }}">编辑收款方式</a>
                                        <a class="btn btn--sm" href="{{ route('admin.members.show', $m->id) }}?tab=cert">查看</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="empty">暂无会员</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination">{{ $members->links('admin.pagination') }}</div>
    </div>
@endsection
