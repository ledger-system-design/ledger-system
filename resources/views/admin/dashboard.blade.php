@extends('admin.layout')

@section('title', '数据概览')

@section('content')
    <div class="stats">
        <div class="stat">
            <div class="stat__label">用户总数</div>
            <div class="stat__value">{{ number_format($stats['member_total']) }}</div>
            <div class="stat__sub">今日新增 {{ $stats['member_today'] }} 人</div>
        </div>
        <div class="stat stat--blue">
            <div class="stat__label">采购任务总数</div>
            <div class="stat__value">{{ number_format($stats['order_total']) }}</div>
            <div class="stat__sub">今日发布/完成 {{ $stats['order_today'] }} 笔</div>
        </div>
        <div class="stat stat--orange">
            <div class="stat__label">待处理采购记录</div>
            <div class="stat__value">{{ number_format($stats['pending_arrive']) }}</div>
            <div class="stat__sub">待付款 {{ $stats['pending_pay'] }} · 待确认 {{ $stats['pending_confirm'] }}</div>
        </div>
        <div class="stat stat--green">
            <div class="stat__label">已到账任务</div>
            <div class="stat__value">{{ number_format($stats['arrived_total']) }}</div>
            <div class="stat__sub">累计发放奖励 ¥{{ number_format($stats['bonus_order'] + $stats['bonus_direct'] + $stats['bonus_shop'], 2) }}</div>
        </div>
        <div class="stat stat--blue">
            <div class="stat__label">累计流水总额</div>
            <div class="stat__value">¥{{ number_format($stats['flow_total'], 2) }}</div>
            <div class="stat__sub">今日 ¥{{ number_format($stats['flow_today'], 2) }}</div>
        </div>
        <div class="stat stat--orange">
            <div class="stat__label">待审核提现</div>
            <div class="stat__value">{{ number_format($stats['cash_pending']) }}</div>
            <div class="stat__sub">金额 ¥{{ number_format($stats['cash_pending_amount'], 2) }}</div>
        </div>
        <div class="stat stat--green">
            <div class="stat__label">平台收益（0.5%）</div>
            <div class="stat__value">¥{{ number_format($stats['platform_profit'], 2) }}</div>
            <div class="stat__sub">按已到账流水计</div>
        </div>
    </div>

    <div class="card">
        <div class="card__head">
            <div class="card__title">收益发放结构</div>
            <a class="btn btn--sm" href="{{ route('admin.balances.index') }}">查看全部资金明细</a>
        </div>
        <div class="card__body">
            <div class="stats" style="margin-bottom:0;">
                <div class="stat">
                    <div class="stat__label">订单收益（参与者 1.5%）</div>
                    <div class="stat__value">¥{{ number_format($stats['bonus_order'], 2) }}</div>
                </div>
                <div class="stat stat--blue">
                    <div class="stat__label">直推收益（第一代 0.5%）</div>
                    <div class="stat__value">¥{{ number_format($stats['bonus_direct'], 2) }}</div>
                </div>
                <div class="stat stat--green">
                    <div class="stat__label">店长收益（1.5%）</div>
                    <div class="stat__value">¥{{ number_format($stats['bonus_shop'], 2) }}</div>
                </div>
                <div class="stat stat--orange">
                    <div class="stat__label">平台收益（0.5%）</div>
                    <div class="stat__value">¥{{ number_format($stats['platform_profit'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card__head">
            <div class="card__title">近 7 天流水走势</div>
        </div>
        <div class="card__body">
            @php $max = max(array_column($trend, 'amount')) ?: 1; @endphp
            <div class="bar-chart">
                @foreach ($trend as $t)
                    <div class="bar-item">
                        <div class="small muted">¥{{ number_format($t['amount'], 0) }}</div>
                        <div class="bar" style="height: {{ max(3, round($t['amount'] / $max * 100)) }}%"></div>
                        <div class="bar-label">{{ $t['date'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card__head">
            <div class="card__title">最近发布的采购任务</div>
            <a class="btn btn--primary btn--sm" href="{{ route('admin.orders.create') }}">+ 发布采购任务</a>
        </div>
        <div class="card__body card__body--flush">
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>订单编号</th>
                            <th>目标用户</th>
                            <th>收款人</th>
                            <th class="text-right">采购金额</th>
                            <th>状态</th>
                            <th>发布人</th>
                            <th>下单时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOrders as $o)
                            @php
                                $buyer = \App\Models\Member::find($o->memberid);
                                $seller = \App\Models\Member::find($o->sellerid);
                            @endphp
                            <tr>
                                <td class="mono">{{ $o->orderno }}</td>
                                <td>{{ $buyer->userreal ?? '-' }}<span class="muted small">（{{ $buyer->username ?? '' }}）</span></td>
                                <td>{{ $seller->userreal ?? '-' }}</td>
                                <td class="text-right num">¥{{ number_format($o->amount, 2) }}</td>
                                <td>
                                    @php
                                        $cls = [0 => 'badge--gray', 1 => 'badge--orange', 2 => 'badge--blue', 3 => 'badge--green'][$o->status] ?? 'badge--gray';
                                    @endphp
                                    <span class="badge {{ $cls }}">{{ $o->statusText() }}</span>
                                </td>
                                <td class="small muted">{{ $o->admin_name ?: '系统' }}</td>
                                <td class="small">{{ optional($o->addtime)->format('Y-m-d H:i') }}</td>
                                <td><a class="btn btn--sm" href="{{ route('admin.orders.edit', $o->id) }}">编辑</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="empty">暂无采购任务</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card__head">
            <div class="card__title">直推收益排行（第一代）</div>
        </div>
        <div class="card__body card__body--flush">
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>排名</th>
                            <th>会员</th>
                            <th>账号</th>
                            <th class="text-right">累计直推收益</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topDirect as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td class="mono">{{ $row['username'] }}</td>
                                <td class="text-right num">¥{{ number_format($row['total'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty">暂无数据</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
