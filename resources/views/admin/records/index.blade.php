@extends('admin.layout')

@section('title', '采购记录')

@section('content')
    <div class="stats">
        <div class="stat stat--orange">
            <div class="stat__label">待处理（待确认 / 已确认）</div>
            <div class="stat__value">{{ number_format($stat['wait']) }}</div>
            <div class="stat__sub">确认到账后系统会自动发放奖励</div>
        </div>
        <div class="stat stat--green">
            <div class="stat__label">已到账</div>
            <div class="stat__value">{{ number_format($stat['arrived']) }}</div>
            <div class="stat__sub">累计发放 ¥{{ number_format($stat['settled_amount'], 2) }}</div>
        </div>
        <div class="stat stat--blue">
            <div class="stat__label">已支付金额合计</div>
            <div class="stat__value">¥{{ number_format($stat['paid_amount'], 2) }}</div>
        </div>
    </div>

    <div class="card">
        <div class="card__head">
            <div class="card__title">用户采购记录与支付凭证</div>
            <div class="btn-row">
                <a class="btn btn--sm {{ $filter === 'wait' ? 'btn--primary' : '' }}" href="?filter=wait">待处理</a>
                <a class="btn btn--sm {{ $filter === 'arrived' ? 'btn--primary' : '' }}" href="?filter=arrived">已到账</a>
                <a class="btn btn--sm {{ $filter === 'all' ? 'btn--primary' : '' }}" href="?filter=all">全部</a>
            </div>
        </div>

        <div class="card__body">
            <form class="filter-bar" method="get">
                <input type="hidden" name="filter" value="{{ $filter }}">
                <div class="field">
                    <label>关键词</label>
                    <input type="text" name="kw" value="{{ request('kw') }}" placeholder="订单编号 / 商品名称">
                </div>
                <div class="field">
                    <label>用户 ID</label>
                    <input type="text" name="memberid" value="{{ request('memberid') }}">
                </div>
                <div class="field">
                    <label>开始日期</label>
                    <input type="date" name="date_start" value="{{ request('date_start') }}">
                </div>
                <div class="field">
                    <label>结束日期</label>
                    <input type="date" name="date_end" value="{{ request('date_end') }}">
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <div class="btn-row">
                        <button class="btn btn--primary" type="submit">筛选</button>
                        <a class="btn" href="{{ route('admin.records.index') }}">重置</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card__body card__body--flush">
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>订单编号</th>
                            <th>用户（买家）</th>
                            <th>收款人</th>
                            <th class="text-right">采购金额</th>
                            <th>支付凭证</th>
                            <th>支付时间</th>
                            <th>是否到账</th>
                            <th class="text-right">发放奖励</th>
                            <th>到账时间</th>
                            <th class="text-center">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $o)
                            @php
                                $buyer = $members[$o->memberid] ?? null;
                                $seller = $members[$o->sellerid] ?? null;
                            @endphp
                            <tr>
                                <td class="mono">{{ $o->id }}</td>
                                <td class="mono">{{ $o->orderno }}</td>
                                <td>
                                    @if ($buyer)
                                        <a href="{{ route('admin.members.show', $buyer->id) }}">{{ $buyer->userreal ?: $buyer->nickname }}</a>
                                        <div class="muted small mono">{{ $buyer->username }}</div>
                                    @endif
                                </td>
                                <td class="small">
                                    {{ $seller->userreal ?? '-' }}
                                    <div class="muted small mono">{{ $seller->username ?? '' }}</div>
                                </td>
                                <td class="text-right num" style="color:#c82f23;">¥{{ number_format($o->amount, 2) }}</td>
                                <td>
                                    @if ($o->payphoto)
                                        <img class="proof-img" src="{{ $o->payphoto }}" onclick="zoomImage(this.src)" title="点击放大">
                                    @else
                                        <span class="badge badge--gray">未上传</span>
                                    @endif
                                </td>
                                <td class="small">{{ optional($o->paytime)->format('Y-m-d H:i') ?: '-' }}</td>
                                <td>
                                    @if ($o->status == 3)
                                        <span class="badge badge--green">已到账</span>
                                    @else
                                        <span class="badge badge--orange">未到账（{{ $o->statusText() }}）</span>
                                    @endif
                                </td>
                                <td class="text-right num">{{ $o->settle_amount > 0 ? '¥' . number_format($o->settle_amount, 2) : '-' }}</td>
                                <td class="small">{{ optional($o->arrived_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                <td>
                                    <div class="btn-row" style="justify-content:center;">
                                        <button class="btn btn--sm" type="button" onclick="showRecord({{ $o->id }})">查看凭证</button>

                                        @if ($o->status != 3)
                                            <form method="post" action="{{ route('admin.records.arrive', $o->id) }}"
                                                data-confirm="确认该笔采购已到账？系统将立即按层级发放奖励：参与者 1.5%、第一代直推人 0.5%、店长 1.5%。">
                                                @csrf
                                                <button class="btn btn--sm btn--green" type="submit">确认到账</button>
                                            </form>
                                        @else
                                            <form method="post" action="{{ route('admin.records.revert', $o->id) }}"
                                                data-confirm="取消到账会把已发放的奖励全部冲回，确定继续吗？">
                                                @csrf
                                                <button class="btn btn--sm btn--danger" type="submit">取消到账</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="empty">暂无采购记录</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination">{{ $orders->links('admin.pagination') }}</div>
    </div>

    <div class="card">
        <div class="card__head">
            <div class="card__title">到账奖励规则</div>
        </div>
        <div class="card__body">
            <table class="data">
                <thead>
                    <tr><th>层级</th><th>对象</th><th>比例</th><th>说明</th></tr>
                </thead>
                <tbody>
                    <tr><td>参与者</td><td>付款人（买家）本人</td><td>1.5%</td><td>计入「订单收益」，会员端个人中心可见</td></tr>
                    <tr><td>第一代</td><td>买家的直推人（邀请人）</td><td>0.5%</td><td>计入「直推收益」</td></tr>
                    <tr><td>店长</td><td>买家所属店铺（fid）</td><td>1.5%</td><td>计入店长余额</td></tr>
                    <tr><td>平台</td><td>系统</td><td>0.5%</td><td>记入系统设置的平台收益</td></tr>
                    <tr><td>第三代（间推）</td><td>—</td><td>0%</td><td>按需求文档：间推只给第二代带来收益，不给第一代产生任务收益</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal-mask" id="recordModal" hidden>
        <div class="modal" style="max-width:760px;">
            <div class="modal__head">
                <span>采购记录详情</span>
                <span class="modal__close" onclick="closeModal('recordModal')">×</span>
            </div>
            <div class="modal__body" id="recordModalBody"></div>
            <div class="modal__foot">
                <button class="btn" type="button" onclick="closeModal('recordModal')">关闭</button>
            </div>
        </div>
    </div>
@endsection
