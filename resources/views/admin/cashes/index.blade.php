@extends('admin.layout')

@section('title', '提现审核')

@section('content')
    <div class="stats">
        <div class="stat stat--orange">
            <div class="stat__label">待审核</div>
            <div class="stat__value">{{ $stat['pending'] }}</div>
            <div class="stat__sub">金额 ¥{{ number_format($stat['pending_amount'], 2) }}</div>
        </div>
        <div class="stat stat--green">
            <div class="stat__label">已到账</div>
            <div class="stat__value">{{ $stat['done'] }}</div>
            <div class="stat__sub">金额 ¥{{ number_format($stat['done_amount'], 2) }}</div>
        </div>
        <div class="stat">
            <div class="stat__label">已驳回</div>
            <div class="stat__value">{{ $stat['rejected'] }}</div>
        </div>
    </div>

    <div class="card">
        <div class="card__head">
            <div class="card__title">提现申请列表</div>
        </div>

        <div class="card__body">
            <form class="filter-bar" method="get">
                <div class="field">
                    <label>用户 ID / 备注</label>
                    <input type="text" name="kw" value="{{ request('kw') }}">
                </div>
                <div class="field">
                    <label>状态</label>
                    <select name="status">
                        <option value="">全部</option>
                        <option value="0" @selected(request('status') === '0')>待审核</option>
                        <option value="1" @selected(request('status') === '1')>已到账</option>
                        <option value="2" @selected(request('status') === '2')>已驳回</option>
                    </select>
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <div class="btn-row">
                        <button class="btn btn--primary" type="submit">筛选</button>
                        <a class="btn" href="{{ route('admin.cashes.index') }}">重置</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card__body card__body--flush">
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>ID</th><th>会员</th><th class="text-right">提现金额</th><th>到账账户</th>
                            <th>状态</th><th>备注</th><th>申请时间</th><th class="text-center">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            @php $m = $members[$r->memberid] ?? null; @endphp
                            <tr>
                                <td class="mono">{{ $r->id }}</td>
                                <td>
                                    @if ($m)
                                        <a href="{{ route('admin.members.show', $m->id) }}">{{ $m->userreal ?: $m->nickname }}</a>
                                        <div class="muted small mono">{{ $m->username }} · 余额 ¥{{ number_format($m->balance1, 2) }}</div>
                                    @else
                                        <span class="muted">#{{ $r->memberid }}</span>
                                    @endif
                                </td>
                                <td class="text-right num" style="color:#c82f23;">¥{{ number_format($r->amount, 2) }}</td>
                                <td>{{ $r->account === 'account2' ? '积分' : '支付宝/银行卡' }}</td>
                                <td>
                                    @php $cls = [0=>'badge--orange',1=>'badge--green',2=>'badge--red'][$r->status] ?? 'badge--gray'; @endphp
                                    <span class="badge {{ $cls }}">{{ [0=>'待审核',1=>'已到账',2=>'已驳回'][$r->status] ?? '-' }}</span>
                                </td>
                                <td class="small">{{ $r->remark }}</td>
                                <td class="small">{{ optional($r->addtime)->format('Y-m-d H:i:s') }}</td>
                                <td>
                                    <div class="btn-row" style="justify-content:center;">
                                        @if ($r->status == 0)
                                            <form method="post" action="{{ route('admin.cashes.approve', $r->id) }}"
                                                data-confirm="确认该笔提现已打款到账？">
                                                @csrf
                                                <button class="btn btn--sm btn--green" type="submit">确认到账</button>
                                            </form>
                                            <form method="post" action="{{ route('admin.cashes.reject', $r->id) }}"
                                                data-confirm="驳回后金额会退回会员余额，确定吗？">
                                                @csrf
                                                <button class="btn btn--sm btn--danger" type="submit">驳回退回</button>
                                            </form>
                                        @else
                                            <span class="muted small">已处理</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="empty">暂无提现申请</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination">{{ $rows->links('admin.pagination') }}</div>
    </div>
@endsection
