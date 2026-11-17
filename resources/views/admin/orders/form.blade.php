@extends('admin.layout')

@section('title', $order ? '编辑采购任务' : '发布采购任务')

@section('content')
    @php
        $isEdit = (bool) $order;
        $memberId = old('memberid', $order->memberid ?? ($preset->id ?? ''));
        $sellerId = old('sellerid', $order->sellerid ?? '');
        $status = old('status', $order->status ?? 0);
        $addtime = old('addtime', optional($order->addtime ?? null)->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i'));
    @endphp

    <form class="card" method="post"
        action="{{ $isEdit ? route('admin.orders.update', $order->id) : route('admin.orders.store') }}">
        @csrf

        <div class="card__head">
            <div class="card__title">
                {{ $isEdit ? '编辑采购任务 · ' . $order->orderno : '发布采购任务' }}
            </div>
            <div class="btn-row">
                <a class="btn btn--sm" href="{{ route('admin.orders.index') }}">返回清单</a>
                @if ($isEdit)
                    <button class="btn btn--sm" type="button" onclick="showRecord({{ $order->id }})">查看到账详情</button>
                @endif
                <button class="btn btn--primary btn--sm" type="submit">{{ $isEdit ? '保存修改' : '确认发布' }}</button>
            </div>
        </div>

        <div class="card__body">
            <div class="alert alert--ok" style="background:#eef6ff;border-color:#cfe4ff;color:#1d4ed8;">
                任务发布后<strong>只对该目标用户可见</strong>：会员登录后在「采购清单」里能看到这笔采购任务，
                打开后可查看收款人、付款人、金额与收款二维码，并上传支付凭证。
            </div>

            <div class="form-grid">
                {{-- ① 卖方（任务对象 / 收款方） --}}
                <div class="field field--full">
                    <label>① 选择卖方（任务对象 / 收款方）<span style="color:#c82f23;">*</span></label>

                    <div class="pick-panel">
                        <div class="pick-panel__head">
                            <span>系统推荐：按「任务额度」与对方「本金 + 收益回款额」的差额由近到远排序</span>
                            <span class="pick-panel__legend">
                                <i class="dot dot--ok"></i>可选
                                <i class="dot dot--assigned"></i>已为他安排买家
                            </span>
                        </div>
                        <div id="sellerList" class="pick-list">
                            <div class="empty">正在加载候选人…</div>
                        </div>
                    </div>

                    <select name="sellerid" id="sellerid" required onchange="onSellerChange()">
                        <option value="">请选择卖方</option>
                        @foreach ($members as $m)
                            <option value="{{ $m->id }}" @selected((string) $sellerId === (string) $m->id)>
                                #{{ $m->id }} · {{ $m->userreal ?: $m->nickname }}（{{ $m->username }}）{{ $m->iscert ? ' · 已认证' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <span class="hint">名称后面会显示他上一笔任务的「本金 + 收益」回款总额；点上方推荐可直接选中</span>
                </div>

                <div class="field field--full">
                    <label>该卖方的收款码（序号 1 为默认优选平台）</label>
                    <div id="sellerPayBox" class="pay-box">
                        <div class="empty">选择卖方后显示其收款码</div>
                    </div>
                </div>

                {{-- ② 任务额度 --}}
                <div class="field">
                    <label>② 任务额度（采购货款金额，元）<span style="color:#c82f23;">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="amountInput"
                        value="{{ old('amount', $order->amount ?? 3000) }}" required oninput="onAmountChange()">
                    <span class="hint">建议每日额度 2000 ~ 5000 元；修改后上方的卖方推荐顺序会按金额自动重排</span>
                </div>

                {{-- ③ 买方（任务方 / 付款人） --}}
                <div class="field field--full">
                    <label>③ 选择买方（任务方 / 付款人）<span style="color:#c82f23;">*</span></label>

                    <div class="pick-panel">
                        <div class="pick-panel__head">
                            <span>买方名单（已经安排过任务的会用橙色加粗标出来）</span>
                            <span class="pick-panel__legend">
                                <i class="dot dot--ok"></i>未安排
                                <i class="dot dot--assigned"></i>已安排任务
                            </span>
                        </div>
                        <div id="buyerList" class="pick-list">
                            <div class="empty">正在加载候选人…</div>
                        </div>
                    </div>

                    <select name="memberid" id="memberid" required onchange="onBuyerChange()">
                        <option value="">请选择要发布给哪个用户</option>
                        @foreach ($members as $m)
                            <option value="{{ $m->id }}" @selected((string) $memberId === (string) $m->id)>
                                #{{ $m->id }} · {{ $m->userreal ?: $m->nickname }}（{{ $m->username }}）{{ $m->iscert ? ' · 已认证' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <span class="hint">只有该用户能在会员端看到这条采购任务</span>
                </div>

                <div class="field">
                    <label>采购商品名称 <span style="color:#c82f23;">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $order->name ?? '采购货款') }}" required>
                </div>

                <div class="field">
                    <label>订单编号</label>
                    <input type="text" name="orderno" value="{{ old('orderno', $order->orderno ?? '') }}" placeholder="留空自动生成">
                </div>

                <div class="field">
                    <label>下单时间</label>
                    <input type="datetime-local" name="addtime" value="{{ $addtime }}">
                </div>

                <div class="field">
                    <label>任务状态</label>
                    <select name="status" required>
                        <option value="0" @selected((string) $status === '0')>0 · 待付款</option>
                        <option value="1" @selected((string) $status === '1')>1 · 待确认（用户已上传凭证）</option>
                        <option value="2" @selected((string) $status === '2')>2 · 已确认</option>
                        <option value="3" @selected((string) $status === '3')>3 · 已完成（已到账）</option>
                    </select>
                    <span class="hint">「到账」请到采购记录页操作，那里会自动发放各层级奖励</span>
                </div>

                <div class="field field--full">
                    @include('admin.partials.image', [
                        'name' => 'indexpic',
                        'value' => old('indexpic', $order->indexpic ?? '/static/img/no_img.png'),
                        'label' => '商品图片（会员端采购清单左侧缩略图）',
                        'size' => 84,
                    ])
                </div>

                <div class="field field--full">
                    @include('admin.partials.image', [
                        'name' => 'payphoto',
                        'value' => old('payphoto', $order->payphoto ?? ''),
                        'label' => '支付凭证（买家上传后可在此查看/替换）',
                        'size' => 84,
                    ])
                </div>

                <div class="field field--full">
                    <label>后台备注</label>
                    <textarea name="remark" placeholder="仅后台可见">{{ old('remark', $order->remark ?? '') }}</textarea>
                </div>
            </div>
        </div>

        @if ($isEdit)
            <div class="card__body" style="border-top:1px solid var(--border);">
                <dl class="kv">
                    <dt>发布人</dt><dd>{{ $order->admin_name ?: '系统' }}</dd>
                    <dt>创建时间</dt><dd>{{ optional($order->created_at)->format('Y-m-d H:i:s') }}</dd>
                    <dt>支付时间</dt><dd>{{ optional($order->paytime)->format('Y-m-d H:i:s') ?: '-' }}</dd>
                    <dt>到账时间</dt><dd>{{ optional($order->arrived_at)->format('Y-m-d H:i:s') ?: '-' }}</dd>
                    <dt>已发奖励</dt><dd>¥{{ number_format($order->settle_amount, 2) }}</dd>
                </dl>
            </div>
        @endif

        <div class="card__body" style="border-top:1px solid var(--border);">
            <div class="btn-row">
                <button class="btn btn--primary" type="submit">{{ $isEdit ? '保存修改' : '确认发布' }}</button>
                <a class="btn" href="{{ route('admin.orders.index') }}">取消</a>
            </div>
        </div>
    </form>

    @if ($isEdit)
        <div class="modal-mask" id="recordModal" hidden>
            <div class="modal" style="max-width:760px;">
                <div class="modal__head">
                    <span>任务详情</span>
                    <span class="modal__close" onclick="closeModal('recordModal')">×</span>
                </div>
                <div class="modal__body" id="recordModalBody"></div>
                <div class="modal__foot">
                    <button class="btn" type="button" onclick="closeModal('recordModal')">关闭</button>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        // 页面载入后渲染卖方推荐 / 买方名单，并加载已选卖方的收款码
        document.addEventListener('DOMContentLoaded', function () {
            loadCandidates();
            loadSellerPays(document.getElementById('sellerid').value);
        });
    </script>
@endpush
