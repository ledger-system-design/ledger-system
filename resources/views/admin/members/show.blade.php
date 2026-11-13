@extends('admin.layout')

@section('title', '会员详情 · ' . ($member->userreal ?: $member->nickname))

@section('content')
    <div class="card">
        <div class="card__head">
            <div class="card__title">会员资料 #{{ $member->id }}</div>
            <div class="btn-row">
                <a class="btn btn--primary btn--sm" href="{{ route('admin.orders.create', ['member' => $member->id]) }}">发布采购任务</a>
                <a class="btn btn--sm" href="{{ route('admin.records.member', $member->id) }}">采购记录</a>
                <a class="btn btn--sm" href="{{ route('admin.members.edit', $member->id) }}">编辑资料</a>
                <a class="btn btn--sm" href="{{ route('admin.members.index') }}">返回列表</a>
            </div>
        </div>
        <div class="card__body">
            <div class="form-grid">
                <dl class="kv">
                    <dt>会员 ID</dt><dd class="mono">{{ $member->id }}</dd>
                    <dt>姓名</dt><dd>{{ $member->userreal ?: '-' }}</dd>
                    <dt>昵称</dt><dd>{{ $member->nickname ?: '-' }}</dd>
                    <dt>登录账号</dt><dd class="mono">{{ $member->username }}</dd>
                    <dt>联系电话</dt><dd class="mono">{{ $member->telephone ?: '-' }}</dd>
                    <dt>邀请码</dt><dd class="mono">{{ $member->invitecode }}</dd>
                    <dt>等级</dt><dd>{{ $member->gradename }}</dd>
                    <dt>直推人</dt>
                    <dd>
                        @if ($parent)
                            <a href="{{ route('admin.members.show', $parent->id) }}">{{ $parent->userreal ?: $parent->nickname }}</a>
                            <span class="muted">（{{ $parent->username }} #{{ $parent->id }}）</span>
                        @else
                            <span class="muted">无（顶级）</span>
                        @endif
                    </dd>
                    <dt>所属店铺</dt><dd class="mono">{{ $member->fid ?: '-' }}</dd>
                    <dt>认证状态</dt>
                    <dd><span class="badge {{ $member->iscert ? 'badge--green' : 'badge--gray' }}">{{ $member->iscert ? '已认证' : '未认证' }}</span></dd>
                    <dt>账号状态</dt>
                    <dd><span class="badge {{ $member->status ? 'badge--green' : 'badge--red' }}">{{ $member->status ? '正常' : '已禁用' }}</span></dd>
                    <dt>注册时间</dt><dd>{{ optional($member->addtime)->format('Y-m-d H:i:s') }}</dd>
                    <dt>登录次数</dt><dd>{{ $member->logintimes }}</dd>
                    <dt>最近登录</dt><dd>{{ optional($member->updated_at)->format('Y-m-d H:i:s') }}</dd>
                </dl>
                <dl class="kv">
                    <dt>余额</dt><dd class="num">¥{{ number_format($member->balance1, 2) }}</dd>
                    <dt>积分</dt><dd class="num">{{ number_format($member->balance2, 2) }}</dd>
                    <dt>订单收益</dt><dd class="num">¥{{ number_format($summary['bonus1'], 2) }}</dd>
                    <dt>直推收益</dt><dd class="num">¥{{ number_format($summary['bonus2'], 2) }}</dd>
                    <dt>已提现</dt><dd class="num">¥{{ number_format($member->withdrawed, 2) }}</dd>
                    <dt>个人采购额</dt><dd class="num">¥{{ number_format($summary['flow'], 2) }}（{{ $summary['flow_num'] }} 笔）</dd>
                    <dt>团队人数</dt><dd>{{ $summary['team'] }} 人</dd>
                    <dt>直推人数</dt><dd>{{ $children->count() }} 人</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card__head">
            <div class="card__title">调整余额（正数增加 / 负数扣减）</div>
        </div>
        <div class="card__body">
            <form class="filter-bar" method="post" action="{{ route('admin.members.adjust', $member->id) }}">
                @csrf
                <div class="field">
                    <label>金额</label>
                    <input type="number" step="0.01" name="amount" placeholder="如 100 或 -100" required>
                </div>
                <div class="field">
                    <label>账户</label>
                    <select name="account">
                        <option value="account1">余额</option>
                        <option value="account2">积分</option>
                    </select>
                </div>
                <div class="field" style="flex:1;min-width:220px;">
                    <label>备注</label>
                    <input type="text" name="remark" placeholder="后台手动调整">
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn btn--primary" type="submit">确认调整</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="tabs">
            <a class="{{ $tab === 'orders' ? 'is-active' : '' }}" href="?tab=orders">采购任务（{{ $orders->total() }}）</a>
            <a class="{{ $tab === 'balances' ? 'is-active' : '' }}" href="?tab=balances">资金明细（{{ $balances->total() }}）</a>
            <a class="{{ $tab === 'children' ? 'is-active' : '' }}" href="?tab=children">直推成员（{{ $children->count() }}）</a>
            <a class="{{ $tab === 'sold' ? 'is-active' : '' }}" href="?tab=sold">作为收款人（{{ $sold->total() }}）</a>
            <a class="{{ $tab === 'cashes' ? 'is-active' : '' }}" href="?tab=cashes">提现记录（{{ $cashes->total() }}）</a>
            <a class="{{ $tab === 'cert' ? 'is-active' : '' }}" href="?tab=cert">收款方式</a>
            <a class="{{ $tab === 'address' ? 'is-active' : '' }}" href="?tab=address">收货地址</a>
        </div>

        <div class="card__body card__body--flush">
            @if ($tab === 'orders')
                <div class="table-wrap">
                    <table class="data">
                        <thead>
                            <tr>
                                <th>订单编号</th><th>商品</th><th class="text-right">金额</th><th>状态</th>
                                <th>收款人</th><th>支付凭证</th><th>到账时间</th><th>发布人</th><th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as $o)
                                @php $seller = \App\Models\Member::find($o->sellerid); @endphp
                                <tr>
                                    <td class="mono">{{ $o->orderno }}</td>
                                    <td>{{ $o->name }}</td>
                                    <td class="text-right num">¥{{ number_format($o->amount, 2) }}</td>
                                    <td>
                                        @php $cls = [0=>'badge--gray',1=>'badge--orange',2=>'badge--blue',3=>'badge--green'][$o->status] ?? 'badge--gray'; @endphp
                                        <span class="badge {{ $cls }}">{{ $o->statusText() }}</span>
                                    </td>
                                    <td>{{ $seller->userreal ?? '-' }}</td>
                                    <td>
                                        @if ($o->payphoto)
                                            <img class="proof-img" src="{{ $o->payphoto }}" onclick="zoomImage(this.src)">
                                        @else
                                            <span class="muted small">未上传</span>
                                        @endif
                                    </td>
                                    <td class="small">{{ optional($o->arrived_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                    <td class="small muted">{{ $o->admin_name ?: '系统' }}</td>
                                    <td><a class="btn btn--sm" href="{{ route('admin.orders.edit', $o->id) }}">编辑</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="empty">暂无采购任务</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pagination">{{ $orders->links('admin.pagination') }}</div>
            @elseif ($tab === 'balances')
                <div class="table-wrap">
                    <table class="data">
                        <thead>
                            <tr><th>ID</th><th>类型</th><th>账户</th><th class="text-right">金额</th><th class="text-right">变动前</th><th class="text-right">变动后</th><th>说明</th><th>关联单号</th><th>时间</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($balances as $b)
                                <tr>
                                    <td class="mono">{{ $b->id }}</td>
                                    <td>{{ $b->type == 1 ? '收入' : '支出' }} #{{ $b->typeid }}</td>
                                    <td>{{ $b->account === 'account2' ? '积分' : '余额' }}</td>
                                    <td class="text-right num" style="color:{{ $b->type == 1 ? '#16a34a' : '#b3261e' }}">{{ $b->type == 1 ? '+' : '-' }}{{ number_format($b->amount, 2) }}</td>
                                    <td class="text-right num muted">{{ number_format($b->prebalance, 2) }}</td>
                                    <td class="text-right num">{{ number_format($b->balance, 2) }}</td>
                                    <td>{{ $b->remark1 }}</td>
                                    <td class="mono small">{{ $b->remark ?: '-' }}</td>
                                    <td class="small">{{ optional($b->addtime)->format('Y-m-d H:i:s') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="empty">暂无资金明细</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pagination">{{ $balances->links('admin.pagination') }}</div>
            @elseif ($tab === 'children')
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>ID</th><th>姓名</th><th>账号</th><th>余额</th><th>订单收益</th><th>直推收益</th><th>下级人数</th><th>注册时间</th><th>操作</th></tr></thead>
                        <tbody>
                            @forelse ($children as $c)
                                <tr>
                                    <td class="mono">{{ $c->id }}</td>
                                    <td>{{ $c->userreal ?: $c->nickname }}</td>
                                    <td class="mono">{{ $c->username }}</td>
                                    <td class="num">¥{{ number_format($c->balance1, 2) }}</td>
                                    <td class="num">¥{{ number_format($c->bonus1, 2) }}</td>
                                    <td class="num">¥{{ number_format($c->bonus2, 2) }}</td>
                                    <td>{{ \App\Models\Member::where('pid', $c->id)->count() }}</td>
                                    <td class="small">{{ optional($c->addtime)->format('Y-m-d') }}</td>
                                    <td><a class="btn btn--sm" href="{{ route('admin.members.show', $c->id) }}">查看</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="empty">暂无直推成员</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif ($tab === 'sold')
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>订单编号</th><th>买家</th><th class="text-right">金额</th><th>状态</th><th>时间</th></tr></thead>
                        <tbody>
                            @forelse ($sold as $o)
                                @php $buyer = \App\Models\Member::find($o->memberid); @endphp
                                <tr>
                                    <td class="mono">{{ $o->orderno }}</td>
                                    <td>{{ $buyer->userreal ?? '-' }}（{{ $buyer->username ?? '' }}）</td>
                                    <td class="text-right num">¥{{ number_format($o->amount, 2) }}</td>
                                    <td>{{ $o->statusText() }}</td>
                                    <td class="small">{{ optional($o->addtime)->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="empty">暂无记录</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pagination">{{ $sold->links('admin.pagination') }}</div>
            @elseif ($tab === 'cashes')
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>ID</th><th class="text-right">金额</th><th>账户</th><th>状态</th><th>备注</th><th>时间</th></tr></thead>
                        <tbody>
                            @forelse ($cashes as $c)
                                <tr>
                                    <td class="mono">{{ $c->id }}</td>
                                    <td class="text-right num">¥{{ number_format($c->amount, 2) }}</td>
                                    <td>{{ $c->account }}</td>
                                    <td>
                                        @php $cls = [0=>'badge--orange',1=>'badge--green',2=>'badge--red'][$c->status] ?? 'badge--gray'; @endphp
                                        <span class="badge {{ $cls }}">{{ [0=>'待审核',1=>'已到账',2=>'已驳回'][$c->status] ?? '-' }}</span>
                                    </td>
                                    <td class="small">{{ $c->remark }}</td>
                                    <td class="small">{{ optional($c->addtime)->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="empty">暂无提现记录</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pagination">{{ $cashes->links('admin.pagination') }}</div>
            @elseif ($tab === 'cert')
                <div class="card__body">
                    <div class="btn-row" style="margin-bottom:12px;">
                        <a class="btn btn--primary btn--sm" href="{{ route('admin.certs.edit', $member->id) }}">编辑收款方式</a>
                    </div>
                    @if ($cert && $cert->payinfo)
                        <div class="table-wrap">
                            <table class="data">
                                <thead><tr><th>序号</th><th>类型</th><th>名称</th><th>账号/卡号</th><th>收款人</th><th>银行</th><th>收款码</th></tr></thead>
                                <tbody>
                                    @foreach ($cert->payinfo as $i => $p)
                                        <tr>
                                            <td>{{ $i }}</td>
                                            <td>{{ $p['type'] ?? '' }}</td>
                                            <td>{{ $p['name'] ?? '' }}</td>
                                            <td class="mono small">{{ $p['account'] ?? ($p['cardno'] ?? '') }}</td>
                                            <td>{{ $p['carduser'] ?? '' }}</td>
                                            <td>{{ $p['cardbank'] ?? '' }}</td>
                                            <td>
                                                @php $img = $p['image'] ?? ($p['url'] ?? ''); @endphp
                                                @if ($img)
                                                    <img class="proof-img" style="width:54px;height:54px;" src="{{ $img }}" onclick="zoomImage(this.src)">
                                                @else
                                                    <span class="muted small">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty">该会员尚未配置收款方式</div>
                    @endif
                </div>
            @elseif ($tab === 'address')
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>ID</th><th>收货人</th><th>电话</th><th>地区</th><th>详细地址</th><th>默认</th><th>时间</th></tr></thead>
                        <tbody>
                            @forelse ($addresses as $a)
                                <tr>
                                    <td class="mono">{{ $a->id }}</td>
                                    <td>{{ $a->userreal }}</td>
                                    <td class="mono">{{ $a->telephone }}</td>
                                    <td>{{ str_replace(',', ' ', $a->region) }}</td>
                                    <td class="wrap">{{ $a->address }}</td>
                                    <td>{{ $a->isdefault ? '是' : '否' }}</td>
                                    <td class="small">{{ optional($a->addtime)->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="empty">暂无收货地址</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
