@extends('admin.layout')

@section('title', '新增用户')

@section('content')
    <form class="card" method="post" action="{{ route('admin.members.store') }}">
        @csrf

        <div class="card__head">
            <div class="card__title">新增用户</div>
            <div class="btn-row">
                <a class="btn btn--sm" href="{{ route('admin.members.index') }}">返回列表</a>
                <button class="btn btn--primary btn--sm" type="submit">确认创建</button>
            </div>
        </div>

        <div class="card__body">
            <div class="alert alert--ok" style="background:#eef6ff;border-color:#cfe4ff;color:#1d4ed8;">
                创建后系统会自动生成该用户的推荐码。填写「推荐人邀请码」即可建立直推关系（第一代 → 第二代）。
            </div>

            <div class="form-grid">
                <div class="field">
                    <label>登录账号（手机号） <span style="color:#c82f23;">*</span></label>
                    <input type="text" name="username" value="{{ old('username') }}" placeholder="11 位手机号" maxlength="11" required>
                </div>
                <div class="field">
                    <label>真实姓名 <span style="color:#c82f23;">*</span></label>
                    <input type="text" name="userreal" value="{{ old('userreal') }}" required>
                </div>
                <div class="field">
                    <label>登录密码 <span style="color:#c82f23;">*</span></label>
                    <input type="text" name="userpwd" value="{{ old('userpwd') }}" placeholder="至少 6 位" minlength="6" required>
                    <span class="hint">请把账号密码告知该用户</span>
                </div>
                <div class="field">
                    <label>昵称</label>
                    <input type="text" name="nickname" value="{{ old('nickname') }}">
                </div>
                <div class="field">
                    <label>联系电话</label>
                    <input type="text" name="telephone" value="{{ old('telephone') }}" placeholder="留空则同登录账号">
                </div>
                <div class="field field--full">
                    @include('admin.partials.image', [
                        'name' => 'headimgurl',
                        'value' => old('headimgurl'),
                        'label' => '头像（可留空）',
                        'size' => 84,
                    ])
                </div>
                <div class="field">
                    <label>推荐人邀请码（直推人，可留空）</label>
                    <input type="text" name="invitecode" value="{{ old('invitecode') }}" list="inviteCodes" placeholder="选填，留空则创建为顶级用户">
                    <span class="hint">填写后该用户属于推荐人的直推下级；留空则没有上级，成为顶级用户</span>
                </div>
                <div class="field">
                    <label>等级名称</label>
                    <input type="text" name="gradename" value="{{ old('gradename', 'VIP') }}">
                </div>
                <div class="field">
                    <label>专属任务收益率（%，留空=跟随全局）</label>
                    <input type="number" step="0.01" min="1" max="100" name="rate_percent" value="{{ old('rate_percent') }}"
                        placeholder="例如 1.5">
                    <span class="hint">填 1.5 表示该用户参与 2000 元任务回款 2030 元</span>
                </div>
                <div class="field">
                    <label>初始余额</label>
                    <input type="number" step="0.01" name="balance1" value="{{ old('balance1', '0') }}">
                </div>
                <div class="field">
                    <label>初始积分</label>
                    <input type="number" step="0.01" name="balance2" value="{{ old('balance2', '0') }}">
                </div>
                <div class="field">
                    <label>店铺认证</label>
                    <select name="iscert">
                        <option value="0" @selected(old('iscert') === '0')>未认证</option>
                        <option value="1" @selected(old('iscert', '1') === '1')>已认证</option>
                    </select>
                </div>
                <div class="field">
                    <label>是否店长</label>
                    <select name="is_shop">
                        <option value="0" @selected(old('is_shop', '0') === '0')>否</option>
                        <option value="1" @selected(old('is_shop') === '1')>是</option>
                    </select>
                </div>
                <div class="field">
                    <label>账号状态</label>
                    <select name="status">
                        <option value="1" @selected(old('status', '1') === '1')>正常</option>
                        <option value="0" @selected(old('status') === '0')>禁用</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card__body" style="border-top:1px solid var(--border);">
            <div class="btn-row">
                <button class="btn btn--primary" type="submit">确认创建</button>
                <a class="btn" href="{{ route('admin.members.index') }}">取消</a>
            </div>
        </div>
    </form>

    <datalist id="inviteCodes">
        @foreach ($members as $m)
            <option value="{{ $m->invitecode }}">{{ $m->userreal ?: $m->nickname }}（{{ $m->username }}）</option>
        @endforeach
    </datalist>
@endsection
