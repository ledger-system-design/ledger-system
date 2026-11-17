@extends('admin.layout')

@section('title', '编辑会员 · ' . ($member->userreal ?: $member->nickname))

@section('content')
    <form class="card" method="post" action="{{ route('admin.members.update', $member->id) }}">
        @csrf

        <div class="card__head">
            <div class="card__title">编辑会员 #{{ $member->id }}</div>
            <div class="btn-row">
                <a class="btn btn--sm" href="{{ route('admin.members.show', $member->id) }}">返回详情</a>
                <button class="btn btn--primary btn--sm" type="submit">保存</button>
            </div>
        </div>

        <div class="card__body">
            <div class="form-grid">
                <div class="field">
                    <label>登录账号（手机号）</label>
                    <input type="text" name="username" value="{{ old('username', $member->username) }}" required>
                </div>
                <div class="field">
                    <label>真实姓名</label>
                    <input type="text" name="userreal" value="{{ old('userreal', $member->userreal) }}">
                </div>
                <div class="field">
                    <label>昵称</label>
                    <input type="text" name="nickname" value="{{ old('nickname', $member->nickname) }}">
                </div>
                <div class="field">
                    <label>联系电话</label>
                    <input type="text" name="telephone" value="{{ old('telephone', $member->telephone) }}">
                </div>
                <div class="field field--full">
                    @include('admin.partials.image', [
                        'name' => 'headimgurl',
                        'value' => old('headimgurl', $member->headimgurl),
                        'label' => '会员头像',
                        'size' => 84,
                    ])
                </div>
                <div class="field">
                    <label>邀请码</label>
                    <input type="text" name="invitecode" value="{{ old('invitecode', $member->invitecode) }}">
                </div>
                <div class="field">
                    <label>等级名称</label>
                    <input type="text" name="gradename" value="{{ old('gradename', $member->gradename) }}">
                </div>
                <div class="field">
                    <label>专属任务收益率（%，留空=跟随全局）</label>
                    <input type="number" step="0.01" min="1" max="100" name="rate_percent"
                        value="{{ old('rate_percent', $member->rate ? rtrim(rtrim(number_format($member->rate * 100, 2, '.', ''), '0'), '.') : '') }}"
                        placeholder="例如 1.5">
                    <span class="hint">
                        当前生效：<strong>{{ rtrim(rtrim(number_format(($member->rate ?: (float) \App\Models\Setting::get('order_rate', config('flow.order_rate'))) * 100, 2, '.', ''), '0'), '.') }}%</strong>
                        （{{ $member->rate ? '该用户专属' : '全局设置' }}）· 清空则恢复跟随全局
                    </span>
                </div>
                <div class="field">
                    <label>直推人 ID（第一代）</label>
                    <input type="number" name="pid" value="{{ old('pid', $member->pid) }}" list="memberList">
                    <span class="hint">当前：{{ $parent ? ($parent->userreal ?: $parent->nickname) . '（' . $parent->username . '）' : '无' }}</span>
                </div>
                <div class="field">
                    <label>所属店铺 ID</label>
                    <input type="number" name="fid" value="{{ old('fid', $member->fid) }}">
                </div>
                <div class="field">
                    <label>余额</label>
                    <input type="number" step="0.01" name="balance1" value="{{ old('balance1', $member->balance1) }}">
                </div>
                <div class="field">
                    <label>积分</label>
                    <input type="number" step="0.01" name="balance2" value="{{ old('balance2', $member->balance2) }}">
                </div>
                <div class="field">
                    <label>累计订单收益</label>
                    <input type="number" step="0.01" name="bonus1" value="{{ old('bonus1', $member->bonus1) }}">
                </div>
                <div class="field">
                    <label>累计直推收益</label>
                    <input type="number" step="0.01" name="bonus2" value="{{ old('bonus2', $member->bonus2) }}">
                </div>
                <div class="field">
                    <label>已提现</label>
                    <input type="number" step="0.01" name="withdrawed" value="{{ old('withdrawed', $member->withdrawed) }}">
                </div>
                <div class="field">
                    <label>店铺认证</label>
                    <select name="iscert">
                        <option value="0" @selected(!$member->iscert)>未认证</option>
                        <option value="1" @selected($member->iscert)>已认证</option>
                    </select>
                </div>
                <div class="field">
                    <label>是否店长</label>
                    <select name="is_shop">
                        <option value="0" @selected(!$member->is_shop)>否</option>
                        <option value="1" @selected($member->is_shop)>是</option>
                    </select>
                </div>
                <div class="field">
                    <label>账号状态</label>
                    <select name="status">
                        <option value="1" @selected($member->status)>正常</option>
                        <option value="0" @selected(!$member->status)>禁用</option>
                    </select>
                </div>
                <div class="field">
                    <label>重置登录密码</label>
                    <input type="text" name="userpwd" placeholder="留空则不修改，至少 6 位">
                    <span class="hint">会员端登录密码，保存后立即生效</span>
                </div>
            </div>
        </div>

        <div class="card__body" style="border-top:1px solid var(--border);">
            <div class="btn-row">
                <button class="btn btn--primary" type="submit">保存修改</button>
                <a class="btn" href="{{ route('admin.members.index') }}">取消</a>
            </div>
        </div>
    </form>

    <datalist id="memberList">
        @foreach ($members as $m)
            <option value="{{ $m->id }}">{{ $m->userreal ?: $m->nickname }}（{{ $m->username }}）</option>
        @endforeach
    </datalist>
@endsection
