@extends('admin.layout')

@section('title', '编辑收款方式')

@section('content')
    <form class="card" method="post" action="{{ route('admin.certs.update', $member->id) }}">
        @csrf

        <div class="card__head">
            <div class="card__title">收款方式 · {{ $member->userreal ?: $member->nickname }}（{{ $member->username }}）</div>
            <div class="btn-row">
                <a class="btn btn--sm" href="{{ route('admin.certs.index') }}">返回列表</a>
                <button class="btn btn--primary btn--sm" type="submit">保存</button>
            </div>
        </div>

        <div class="card__body">
            <div class="alert alert--ok" style="background:#eef6ff;border-color:#cfe4ff;color:#1d4ed8;">
                这些收款信息会展示在「采购清单 → 订单详情」里，作为付款人的收款依据。
                会员端店铺认证页面填写的就是这里的字段。
            </div>

            <div class="form-grid">
                <div class="field field--full">
                    @include('admin.partials.image', [
                        'name' => 'license',
                        'value' => old('license', $cert->license),
                        'label' => '营业执照 / 店铺照片',
                        'size' => 110,
                    ])
                </div>

                <div class="field">
                    <label>认证状态</label>
                    <select name="iscert">
                        <option value="0" @selected(!$cert->iscert)>未认证</option>
                        <option value="1" @selected($cert->iscert)>已认证</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card__body" style="border-top:1px solid var(--border);">
            <h3 style="margin:0 0 6px;font-size:14px;">收款方式（共 5 项）</h3>
            <p style="margin:0 0 14px;color:#6b7280;font-size:13px;">
                用「上移 / 下移 / 设为默认」调整陈列顺序。<b>序号 1 是默认优选平台</b>，
                派单时会员端会优先展示序号 1 的收款码，其余按序号依次作为替代。
            </p>

            <div class="pay-blocks">
                @foreach ($payinfo as $i => $p)
                    <div class="img-field pay-block {{ $i === 0 ? 'is-default' : '' }}" data-pay-block data-pay-index="{{ $i }}">
                        <input type="hidden" data-pay-sort name="payinfo[{{ $i }}][sort]" value="{{ $i + 1 }}">
                        <label class="img-field__label">
                            <b data-pay-no>序号 {{ $i + 1 }}</b>
                            <span class="badge" data-pay-default style="margin-left:6px;{{ $i === 0 ? '' : 'display:none;' }}">默认优选</span>
                            <span style="margin-left:8px;">{{ $p['name'] ?? '' }}</span>
                            <span class="badge" style="margin-left:6px;">{{ $p['type'] ?? '' }}</span>
                            <span class="btn-row" style="display:inline-flex;margin-left:10px;">
                                <button type="button" class="btn btn--sm" onclick="payMove(this,-1)">↑ 上移</button>
                                <button type="button" class="btn btn--sm" onclick="payMove(this,1)">↓ 下移</button>
                                <button type="button" class="btn btn--sm btn--primary" onclick="paySetDefault(this)">设为默认</button>
                            </span>
                        </label>
                        <div style="display:grid;grid-template-columns:minmax(280px,1fr) minmax(200px,1fr);gap:14px;align-items:start;">
                            @include('admin.partials.image', [
                                'name' => "payinfo[$i][image]",
                                'value' => $p['image'] ?? '',
                                'label' => '收款二维码',
                                'size' => 100,
                            ])
                            <div style="display:flex;flex-direction:column;gap:10px;">
                                <div class="field">
                                    <label>类型</label>
                                    <select name="payinfo[{{ $i }}][type]">
                                        <option value="bank" @selected(($p['type'] ?? '') === 'bank')>bank 银行卡</option>
                                        <option value="wechat" @selected(($p['type'] ?? '') === 'wechat')>wechat 微信</option>
                                        <option value="alipay" @selected(($p['type'] ?? '') === 'alipay')>alipay 支付宝</option>
                                        <option value="url" @selected(($p['type'] ?? '') === 'url')>url 收款码链接</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label>名称</label>
                                    <input type="text" name="payinfo[{{ $i }}][name]" value="{{ $p['name'] ?? '' }}">
                                </div>
                                <div class="field">
                                    <label>支付宝账号</label>
                                    <input type="text" name="payinfo[{{ $i }}][account]" value="{{ $p['account'] ?? '' }}">
                                </div>
                                <div class="field">
                                    <label>收款人</label>
                                    <input type="text" name="payinfo[{{ $i }}][carduser]" value="{{ $p['carduser'] ?? '' }}">
                                </div>
                                <div class="field">
                                    <label>银行名称</label>
                                    <input type="text" name="payinfo[{{ $i }}][cardbank]" value="{{ $p['cardbank'] ?? '' }}">
                                </div>
                                <div class="field">
                                    <label>卡号</label>
                                    <input type="text" name="payinfo[{{ $i }}][cardno]" value="{{ $p['cardno'] ?? '' }}">
                                </div>
                                <div class="field">
                                    <label>远程收款码链接（可选）</label>
                                    <input type="text" name="payinfo[{{ $i }}][url]" value="{{ $p['url'] ?? '' }}">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card__body" style="border-top:1px solid var(--border);">
            <div class="btn-row">
                <button class="btn btn--primary" type="submit">保存收款方式</button>
                <a class="btn" href="{{ route('admin.certs.index') }}">取消</a>
            </div>
        </div>
    </form>
@endsection
