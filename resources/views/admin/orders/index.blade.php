@extends('admin.layout')

@section('title', '采购清单')

@section('content')
    <div class="stats">
        <div class="stat">
            <div class="stat__label">任务总数</div>
            <div class="stat__value">{{ number_format($stat['total']) }}</div>
        </div>
        <div class="stat stat--blue">
            <div class="stat__label">任务金额合计</div>
            <div class="stat__value">¥{{ number_format($stat['amount'], 2) }}</div>
        </div>
        <div class="stat stat--orange">
            <div class="stat__label">进行中</div>
            <div class="stat__value">{{ number_format($stat['pending']) }}</div>
            <div class="stat__sub">待付款 / 待确认 / 已确认</div>
        </div>
        <div class="stat stat--green">
            <div class="stat__label">已到账</div>
            <div class="stat__value">{{ number_format($stat['arrived']) }}</div>
        </div>
    </div>

    <div class="card">
        <div class="card__head">
            <div class="card__title">历史发布的采购清单</div>
            <div class="btn-row">
                <a class="btn btn--primary btn--sm" href="{{ route('admin.orders.create') }}">+ 发布采购任务</a>
                <button class="btn btn--sm" type="button" onclick="openModal('batchModal')">批量发布</button>
            </div>
        </div>

        <div class="card__body">
            <form class="filter-bar" method="get">
                <div class="field">
                    <label>关键词</label>
                    <input type="text" name="kw" value="{{ request('kw') }}" placeholder="订单编号 / 商品名称 / 备注">
                </div>
                <div class="field">
                    <label>目标用户 ID</label>
                    <input type="text" name="memberid" value="{{ request('memberid') }}">
                </div>
                <div class="field">
                    <label>收款人 ID</label>
                    <input type="text" name="sellerid" value="{{ request('sellerid') }}">
                </div>
                <div class="field">
                    <label>状态</label>
                    <select name="status">
                        <option value="">全部</option>
                        <option value="0" @selected(request('status') === '0')>待付款</option>
                        <option value="1" @selected(request('status') === '1')>待确认</option>
                        <option value="2" @selected(request('status') === '2')>已确认</option>
                        <option value="3" @selected(request('status') === '3')>已完成（已到账）</option>
                    </select>
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
                        <a class="btn" href="{{ route('admin.orders.index') }}">重置</a>
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
                            <th>商品名称</th>
                            <th>目标用户（买家）</th>
                            <th>收款人（卖家）</th>
                            <th class="text-right">采购金额</th>
                            <th>状态</th>
                            <th>支付凭证</th>
                            <th>发布人</th>
                            <th>下单时间</th>
                            <th>到账时间</th>
                            <th class="text-center">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $o)
                            @php
                                $buyer = $members[$o->memberid] ?? null;
                                $seller = $members[$o->sellerid] ?? null;
                                $cls = [0 => 'badge--gray', 1 => 'badge--orange', 2 => 'badge--blue', 3 => 'badge--green'][$o->status] ?? 'badge--gray';
                            @endphp
                            <tr>
                                <td class="mono">{{ $o->id }}</td>
                                <td class="mono">{{ $o->orderno }}</td>
                                <td>{{ $o->name }}</td>
                                <td>
                                    @if ($buyer)
                                        <a href="{{ route('admin.members.show', $buyer->id) }}">{{ $buyer->userreal ?: $buyer->nickname }}</a>
                                        <span class="muted small">（{{ $buyer->username }}）</span>
                                    @else
                                        <span class="muted">#{{ $o->memberid }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($seller)
                                        {{ $seller->userreal ?: $seller->nickname }}
                                        <span class="muted small">（{{ $seller->username }}）</span>
                                    @else
                                        <span class="muted">#{{ $o->sellerid }}</span>
                                    @endif
                                </td>
                                <td class="text-right num" style="color:#c82f23;">¥{{ number_format($o->amount, 2) }}</td>
                                <td><span class="badge {{ $cls }}">{{ $o->statusText() }}</span></td>
                                <td>
                                    @if ($o->payphoto)
                                        <img class="proof-img" src="{{ $o->payphoto }}" onclick="zoomImage(this.src)">
                                    @else
                                        <span class="muted small">未上传</span>
                                    @endif
                                </td>
                                <td class="small muted">{{ $o->admin_name ?: '系统' }}</td>
                                <td class="small">{{ optional($o->addtime)->format('Y-m-d H:i') }}</td>
                                <td class="small">{{ optional($o->arrived_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                <td>
                                    <div class="btn-row" style="justify-content:center;">
                                        @if ($o->status == 0)
                                            <a class="btn btn--sm btn--green" href="{{ route('admin.orders.edit', $o->id) }}">编辑</a>
                                        @elseif ($o->status == 3)
                                            <button class="btn btn--sm" type="button" onclick="showRecord({{ $o->id }})">查看</button>
                                        @else
                                            <a class="btn btn--sm btn--green" href="{{ route('admin.records.index') }}">去处理</a>
                                        @endif
                                        <a class="btn btn--sm" href="{{ route('admin.orders.edit', $o->id) }}">修改</a>
                                        <form method="post" action="{{ route('admin.orders.destroy', $o->id) }}"
                                            data-confirm="确定删除该采购任务吗？">
                                            @csrf
                                            <button class="btn btn--sm btn--danger" type="submit">删除</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="empty">暂无采购任务，点击右上角「发布采购任务」为指定用户发布</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination">{{ $orders->links('admin.pagination') }}</div>
    </div>

    {{-- 批量发布 --}}
    <div class="modal-mask" id="batchModal" hidden>
        <form class="modal" method="post" action="{{ route('admin.orders.batch') }}">
            @csrf
            <div class="modal__head">
                <span>批量发布采购任务</span>
                <span class="modal__close" onclick="closeModal('batchModal')">×</span>
            </div>
            <div class="modal__body">
                <p class="muted small">每一条任务都是针对单个用户发布的，只有该用户能在会员端「采购清单」中看到。</p>
                <div class="field" style="margin-bottom:12px;">
                    <label>选择目标用户（可多选）</label>
                    <select name="memberids[]" multiple size="8" style="height:auto;">
                        @foreach (\App\Models\Member::orderByDesc('id')->limit(500)->get() as $m)
                            <option value="{{ $m->id }}">{{ $m->userreal ?: $m->nickname }}（{{ $m->username }} #{{ $m->id }}）</option>
                        @endforeach
                    </select>
                    <span class="hint">按住 Ctrl / ⌘ 可多选</span>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label>采购金额</label>
                        <input type="number" step="0.01" name="amount" value="3000" required>
                    </div>
                    <div class="field">
                        <label>商品名称</label>
                        <input type="text" name="name" value="采购货款">
                    </div>
                    <div class="field field--full">
                        <label>收款人（卖家）</label>
                        <select name="sellerid" required>
                            <option value="">请选择</option>
                            @foreach (\App\Models\Member::orderByDesc('id')->limit(500)->get() as $m)
                                <option value="{{ $m->id }}">{{ $m->userreal ?: $m->nickname }}（{{ $m->username }} #{{ $m->id }}）</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal__foot">
                <button class="btn" type="button" onclick="closeModal('batchModal')">取消</button>
                <button class="btn btn--primary" type="submit">确认发布</button>
            </div>
        </form>
    </div>
@endsection
