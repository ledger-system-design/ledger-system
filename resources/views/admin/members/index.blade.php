@extends('admin.layout')

@section('title', '用户数据')

@section('content')
    <div class="card">
        <div class="card__head">
            <div class="card__title">用户数据（共 {{ $members->total() }} 位会员）</div>
            <div class="btn-row">
                <a class="btn btn--primary btn--sm" href="{{ route('admin.members.create') }}">+ 新增用户</a>
                <a class="btn btn--sm" href="{{ route('admin.orders.create') }}">+ 发布采购任务</a>
                <button class="btn btn--sm btn--danger" type="button" onclick="openModal('purgeModal')">清空所有用户数据</button>
            </div>
        </div>

        <div class="card__body">
            <form class="filter-bar" method="get">
                <div class="field">
                    <label>关键词</label>
                    <input type="text" name="kw" value="{{ request('kw') }}" placeholder="手机号 / 姓名 / 邀请码 / ID">
                </div>
                <div class="field">
                    <label>直推人 ID</label>
                    <input type="text" name="pid" value="{{ request('pid') }}" placeholder="上级会员 ID">
                </div>
                <div class="field">
                    <label>状态</label>
                    <select name="status">
                        <option value="">全部</option>
                        <option value="1" @selected(request('status') === '1')>正常</option>
                        <option value="0" @selected(request('status') === '0')>已禁用</option>
                    </select>
                </div>
                <div class="field">
                    <label>认证</label>
                    <select name="iscert">
                        <option value="">全部</option>
                        <option value="1" @selected(request('iscert') === '1')>已认证</option>
                        <option value="0" @selected(request('iscert') === '0')>未认证</option>
                    </select>
                </div>
                <div class="field">
                    <label>店长</label>
                    <select name="is_shop">
                        <option value="">全部</option>
                        <option value="1" @selected(request('is_shop') === '1')>是</option>
                        <option value="0" @selected(request('is_shop') === '0')>否</option>
                    </select>
                </div>
                <div class="field">
                    <label>排序</label>
                    <select name="sort">
                        <option value="id" @selected(request('sort') === 'id')>ID</option>
                        <option value="balance1" @selected(request('sort') === 'balance1')>余额</option>
                        <option value="bonus1" @selected(request('sort') === 'bonus1')>订单收益</option>
                        <option value="bonus2" @selected(request('sort') === 'bonus2')>直推收益</option>
                        <option value="logintimes" @selected(request('sort') === 'logintimes')>登录次数</option>
                        <option value="addtime" @selected(request('sort') === 'addtime')>注册时间</option>
                    </select>
                </div>
                <div class="field">
                    <label>方向</label>
                    <select name="dir">
                        <option value="desc" @selected(request('dir', 'desc') === 'desc')>倒序</option>
                        <option value="asc" @selected(request('dir') === 'asc')>正序</option>
                    </select>
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <div class="btn-row">
                        <button class="btn btn--primary" type="submit">筛选</button>
                        <a class="btn" href="{{ route('admin.members.index') }}">重置</a>
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
                            <th>真实姓名（注册资料）</th>
                            <th>昵称（用户可改）</th>
                            <th>登录账号</th>
                            <th>邀请码</th>
                            <th>专属收益率</th>
                            <th>等级</th>
                            <th>直推人</th>
                            <th>店铺(ID)</th>
                            <th class="text-right">余额</th>
                            <th class="text-right">积分</th>
                            <th class="text-right">订单收益</th>
                            <th class="text-right">直推收益</th>
                            <th class="text-right">已提现</th>
                            <th class="text-right">团队/直推</th>
                            <th class="text-right">采购金额/笔数</th>
                            <th>认证</th>
                            <th>状态</th>
                            <th>注册时间</th>
                            <th>登录</th>
                            <th class="text-center">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($members as $m)
                            @php
                                $s = $stat[$m->id] ?? [];
                                $p = $m->pid ? \App\Models\Member::find($m->pid) : null;
                                $globalRate = (float) \App\Models\Setting::get('order_rate', config('flow.order_rate'));
                            @endphp
                            <tr>
                                <td class="mono">{{ $m->id }}</td>
                                <td>
                                    <a href="{{ route('admin.members.show', $m->id) }}"><strong>{{ $m->userreal ?: '未填写' }}</strong></a>
                                    @if ($m->is_shop)
                                        <span class="badge badge--red">店长</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $m->nickname ?: '-' }}
                                    @if ($m->nickname && $m->userreal && $m->nickname !== $m->userreal)
                                        <span class="badge badge--gray" title="用户自行修改过的昵称">已改昵称</span>
                                    @endif
                                </td>
                                <td class="mono">{{ $m->username }}</td>
                                <td class="mono">{{ $m->invitecode }}</td>
                                <td class="small">
                                    @if ($m->rate)
                                        <strong style="color:#c82f23;">{{ rtrim(rtrim(number_format($m->rate * 100, 2, '.', ''), '0'), '.') }}%</strong>
                                    @else
                                        <span class="muted">{{ rtrim(rtrim(number_format($globalRate * 100, 2, '.', ''), '0'), '.') }}%（全局）</span>
                                    @endif
                                </td>
                                <td>{{ $m->gradename }}</td>
                                <td>
                                    @if ($p)
                                        <a href="{{ route('admin.members.show', $p->id) }}">{{ $p->userreal ?: $p->nickname }}</a>
                                        <span class="muted small">#{{ $p->id }}</span>
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>
                                <td class="mono">{{ $m->fid ?: '-' }}</td>
                                <td class="text-right num">{{ number_format($m->balance1, 2) }}</td>
                                <td class="text-right num">{{ number_format($m->balance2, 2) }}</td>
                                <td class="text-right num">{{ number_format($m->bonus1, 2) }}</td>
                                <td class="text-right num">{{ number_format($m->bonus2, 2) }}</td>
                                <td class="text-right num">{{ number_format($m->withdrawed, 2) }}</td>
                                <td class="text-right small">{{ $s['team'] ?? 0 }} / {{ $s['children'] ?? 0 }}</td>
                                <td class="text-right small">¥{{ number_format($s['flow'] ?? 0, 0) }} / {{ $s['orders'] ?? 0 }}</td>
                                <td>
                                    <span class="badge {{ $m->iscert ? 'badge--green' : 'badge--gray' }}">{{ $m->iscert ? '已认证' : '未认证' }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $m->status ? 'badge--green' : 'badge--red' }}">{{ $m->status ? '正常' : '已禁用' }}</span>
                                </td>
                                <td class="small">{{ optional($m->addtime)->format('Y-m-d') }}</td>
                                <td class="small">{{ $m->logintimes }}</td>
                                <td>
                                    <div class="btn-row" style="justify-content:center;">
                                        <a class="btn btn--sm btn--primary"
                                            href="{{ route('admin.orders.create', ['member' => $m->id]) }}">发布任务</a>
                                        <a class="btn btn--sm" href="{{ route('admin.members.show', $m->id) }}">详情</a>
                                        <a class="btn btn--sm" href="{{ route('admin.records.member', $m->id) }}">采购记录</a>
                                        <a class="btn btn--sm" href="{{ route('admin.members.edit', $m->id) }}">编辑</a>
                                        <form method="post" action="{{ route('admin.members.toggle', $m->id) }}">
                                            @csrf
                                            <button class="btn btn--sm" type="submit">{{ $m->status ? '禁用' : '启用' }}</button>
                                        </form>
                                        <form method="post" action="{{ route('admin.members.destroy', $m->id) }}"
                                            data-confirm="确定要删除会员 {{ $m->username }} 吗？其订单、明细、地址都会被一并删除，且不可恢复。">
                                            @csrf
                                            <button class="btn btn--sm btn--danger" type="submit">删除</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="21" class="empty">没有符合条件的会员</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination">
            {{ $members->links('admin.pagination') }}
        </div>
    </div>

    <div class="modal-mask" id="purgeModal" hidden>
        <div class="modal" style="max-width:520px;">
            <div class="modal__head">
                <span style="color:#b3261e;">⚠ 清空所有用户数据</span>
                <span class="modal__close" onclick="closeModal('purgeModal')">×</span>
            </div>
            <div class="modal__body">
                <p style="margin:0 0 10px;line-height:1.8;">
                    该操作会删除 <strong>全部会员</strong> 及其 <strong>采购任务、资金明细、提现记录、收货地址、认证资料</strong>，
                    <span style="color:#b3261e;font-weight:600;">删除后无法恢复</span>。
                </p>
                <p style="margin:0 0 10px;color:#6b7280;font-size:13px;">管理员账号与站点配置（店铺名称、轮播、收益率等）会保留。</p>
                <form method="post" action="{{ route('admin.members.purge') }}">
                    @csrf
                    <div class="field">
                        <label>请输入 <strong>DEL</strong> 确认 <span style="color:#c82f23;">*</span></label>
                        <input type="text" name="confirm_text" placeholder="DEL" autocomplete="off" required>
                    </div>
                    <div class="btn-row" style="margin-top:14px;">
                        <button class="btn btn--danger" type="submit">确认清空</button>
                        <button class="btn" type="button" onclick="closeModal('purgeModal')">取消</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
