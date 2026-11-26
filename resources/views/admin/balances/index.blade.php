@extends('admin.layout')

@section('title', '资金明细')

@section('content')
    <div class="stats">
        <div class="stat stat--green">
            <div class="stat__label">累计收入</div>
            <div class="stat__value">¥{{ number_format($stat['in'], 2) }}</div>
        </div>
        <div class="stat stat--orange">
            <div class="stat__label">累计支出</div>
            <div class="stat__value">¥{{ number_format($stat['out'], 2) }}</div>
        </div>
        <div class="stat">
            <div class="stat__label">订单收益（1.5%）</div>
            <div class="stat__value">¥{{ number_format($stat['order'], 2) }}</div>
        </div>
        <div class="stat stat--blue">
            <div class="stat__label">直推收益（0.5%）</div>
            <div class="stat__value">¥{{ number_format($stat['direct'], 2) }}</div>
        </div>
        <div class="stat stat--blue">
            <div class="stat__label">店长收益（1.5%）</div>
            <div class="stat__value">¥{{ number_format($stat['shop'], 2) }}</div>
        </div>
        <div class="stat">
            <div class="stat__label">明细条数</div>
            <div class="stat__value">{{ number_format($stat['total']) }}</div>
        </div>
    </div>

    <div class="card">
        <div class="card__head">
            <div class="card__title">全部资金明细</div>
        </div>

        <div class="card__body">
            <form class="filter-bar" method="get">
                <div class="field">
                    <label>关键词</label>
                    <input type="text" name="kw" value="{{ request('kw') }}" placeholder="单号 / 说明 / 用户ID">
                </div>
                <div class="field">
                    <label>用户 ID</label>
                    <input type="text" name="memberid" value="{{ request('memberid') }}">
                </div>
                <div class="field">
                    <label>类型</label>
                    <select name="typeid">
                        <option value="">全部</option>
                        <option value="1" @selected(request('typeid') === '1')>1 充值</option>
                        <option value="7" @selected(request('typeid') === '7')>7 订单收益</option>
                        <option value="8" @selected(request('typeid') === '8')>8 直推收益</option>
                        <option value="11" @selected(request('typeid') === '11')>11 店长收益</option>
                        <option value="12" @selected(request('typeid') === '12')>12 平台收益</option>
                        <option value="21" @selected(request('typeid') === '21')>21 提现</option>
                        <option value="31" @selected(request('typeid') === '31')>31 服务费分成</option>
                    </select>
                </div>
                <div class="field">
                    <label>收/支</label>
                    <select name="type">
                        <option value="">全部</option>
                        <option value="1" @selected(request('type') === '1')>收入</option>
                        <option value="0" @selected(request('type') === '0')>支出</option>
                    </select>
                </div>
                <div class="field">
                    <label>账户</label>
                    <select name="account">
                        <option value="">全部</option>
                        <option value="account1" @selected(request('account') === 'account1')>余额</option>
                        <option value="account2" @selected(request('account') === 'account2')>积分</option>
                    </select>
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <div class="btn-row">
                        <button class="btn btn--primary" type="submit">筛选</button>
                        <a class="btn" href="{{ route('admin.balances.index') }}">重置</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card__body card__body--flush">
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>ID</th><th>会员</th><th>账户</th><th>类型</th><th class="text-right">金额</th>
                            <th class="text-right">变动前</th><th class="text-right">变动后</th>
                            <th>说明</th><th>关联单号</th><th>结算日</th><th>时间</th><th>操作</th>
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
                                        <div class="muted small mono">{{ $m->username }}</div>
                                    @else
                                        <span class="muted">#{{ $r->memberid }}</span>
                                    @endif
                                </td>
                                <td>{{ $r->account === 'account2' ? '积分' : '余额' }}</td>
                                <td>
                                    <span class="badge {{ $r->type == 1 ? 'badge--green' : 'badge--gray' }}">{{ $r->type == 1 ? '收入' : '支出' }}</span>
                                    <span class="muted small">#{{ $r->typeid }}</span>
                                </td>
                                <td class="text-right num" style="color:{{ $r->type == 1 ? '#16a34a' : '#b3261e' }}">
                                    {{ $r->type == 1 ? '+' : '-' }}{{ number_format($r->amount, 2) }}
                                </td>
                                <td class="text-right num muted">{{ number_format($r->prebalance, 2) }}</td>
                                <td class="text-right num">{{ number_format($r->balance, 2) }}</td>
                                <td>{{ $r->remark1 }}</td>
                                <td class="mono small">{{ $r->remark ?: '-' }}</td>
                                <td class="small">{{ $r->calcdate ?: '-' }}</td>
                                <td class="small">{{ optional($r->addtime)->format('Y-m-d H:i:s') }}</td>
                                <td>
                                    <form method="post" action="{{ route('admin.balances.destroy', $r->id) }}"
                                        data-confirm="删除这条明细只是移除记录，不会自动回滚会员余额，确定删除吗？">
                                        @csrf
                                        <button class="btn btn--sm btn--danger" type="submit">删除</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="empty">暂无资金明细</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination">{{ $rows->links('admin.pagination') }}</div>
    </div>
@endsection
