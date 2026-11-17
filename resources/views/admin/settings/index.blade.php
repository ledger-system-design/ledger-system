@extends('admin.layout')

@section('title', '系统设置')

@section('content')
    <form class="card" method="post" action="{{ route('admin.settings.update') }}">
        @csrf

        <div class="card__head">
            <div class="card__title">全局设置</div>
            <button class="btn btn--primary btn--sm" type="submit">保存设置</button>
        </div>

        <div class="card__body">
            <div class="form-grid">
                <div class="field">
                    <label>店铺名称（会员端显示）</label>
                    <input type="text" name="index_title" value="{{ old('index_title', $settings['index_title']) }}">
                </div>
                <div class="field field--full">
                    @include('admin.partials.image', [
                        'name' => 'index_logo',
                        'value' => old('index_logo', $settings['index_logo']),
                        'label' => '店铺 Logo',
                        'size' => 84,
                    ])
                </div>
                <div class="field">
                    <label>订单自动结算</label>
                    <select name="auto_settle">
                        <option value="0" @selected(!$settings['auto_settle'])>关闭（由管理员在采购记录手动确认到账）</option>
                        <option value="1" @selected($settings['auto_settle'])>开启（支付后自动确认并发放奖励）</option>
                    </select>
                    <span class="hint">默认关闭，保证奖励由后台人工审核后发放</span>
                </div>
                <div class="field">
                    <label>任务收益率（全局，1% ~ 100%）<span style="color:#c82f23;">*</span></label>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <input type="number" step="0.01" min="1" max="100" name="order_rate_percent"
                            value="{{ old('order_rate_percent', rtrim(rtrim(number_format($settings['order_rate'] * 100, 2, '.', ''), '0'), '.') ?: '0') }}"
                            style="max-width:140px;">
                        <span>%</span>
                    </div>
                    <span class="hint">
                        例如填 <strong>1.5</strong> 表示参与 2000 元的任务，回款 = 2000 + 2000×1.5% = <strong>2030</strong>。
                        单个用户可在「用户数据 → 编辑」里设置专属收益率（优先于此处）。
                    </span>
                </div>
                <div class="field">
                    <label>前台自助注册</label>
                    <select name="register_open">
                        <option value="0" @selected(!$settings['register_open'])>关闭（新用户由管理员在后台代注册，需填推荐码）</option>
                        <option value="1" @selected($settings['register_open'])>开启（会员可自行注册，仍需推荐码）</option>
                    </select>
                    <span class="hint">客户要求默认关闭</span>
                </div>
                <div class="field">
                    <label>平台累计收益（0.5%）</label>
                    <input type="number" step="0.01" name="platform_profit" value="{{ $settings['platform_profit'] }}">
                </div>
            </div>
        </div>

        <div class="card__body" style="border-top:1px solid var(--border);">
            <h3 style="margin:0 0 12px;font-size:14px;">首页轮播图</h3>
            @php $banners = array_pad($settings['index_banner'], 3, ['src' => '', 'url' => '', 'remark' => '']); @endphp
            <div class="form-grid">
                @foreach (array_slice($banners, 0, 5) as $i => $b)
                    <div class="field field--full" style="display:grid;grid-template-columns:minmax(280px,1fr) minmax(180px,1fr);gap:14px;align-items:start;">
                        @include('admin.partials.image', [
                            'name' => "banner[$i][src]",
                            'value' => $b['src'] ?? '',
                            'label' => '轮播图 ' . ($i + 1),
                            'size' => 120,
                        ])
                        <div style="display:flex;flex-direction:column;gap:12px;">
                            <div class="field">
                                <label>跳转链接</label>
                                <input type="text" name="banner[{{ $i }}][url]" value="{{ $b['url'] ?? '' }}">
                            </div>
                            <div class="field">
                                <label>说明</label>
                                <input type="text" name="banner[{{ $i }}][remark]" value="{{ $b['remark'] ?? '' }}">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card__body" style="border-top:1px solid var(--border);">
            <h3 style="margin:0 0 12px;font-size:14px;">首页导航宫格</h3>
            @php $navs = array_pad($settings['index_nav'], 5, ['src' => '', 'url' => '', 'remark' => '']); @endphp
            <div class="form-grid">
                @foreach (array_slice($navs, 0, 8) as $i => $n)
                    <div class="field field--full" style="display:grid;grid-template-columns:minmax(280px,1fr) minmax(180px,1fr);gap:14px;align-items:start;">
                        @include('admin.partials.image', [
                            'name' => "nav[$i][src]",
                            'value' => $n['src'] ?? '',
                            'label' => '图标 ' . ($i + 1),
                            'size' => 84,
                        ])
                        <div style="display:flex;flex-direction:column;gap:12px;">
                            <div class="field">
                                <label>名称</label>
                                <input type="text" name="nav[{{ $i }}][remark]" value="{{ $n['remark'] ?? '' }}">
                            </div>
                            <div class="field">
                                <label>跳转路径</label>
                                <input type="text" name="nav[{{ $i }}][url]" value="{{ $n['url'] ?? '' }}" placeholder="/pages/index/caigou">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card__body" style="border-top:1px solid var(--border);">
            <h3 style="margin:0 0 12px;font-size:14px;">首页公告（一行一条）</h3>
            @php $news = array_pad($settings['index_news'], 4, ['title' => '']); @endphp
            @foreach (array_slice($news, 0, 8) as $i => $n)
                <div class="field" style="margin-bottom:8px;">
                    <input type="text" name="news[{{ $i }}][title]" value="{{ $n['title'] ?? '' }}" placeholder="公告内容">
                </div>
            @endforeach
        </div>

        <div class="card__body" style="border-top:1px solid var(--border);">
            <h3 style="margin:0 0 12px;font-size:14px;">个人中心「我的服务」入口</h3>
            @php $tools = array_pad($settings['member_tools'], 4, ['src' => '', 'url' => '', 'remark' => '', 'status' => '1']); @endphp
            <div class="form-grid">
                @foreach (array_slice($tools, 0, 6) as $i => $t)
                    <div class="field field--full" style="display:grid;grid-template-columns:minmax(280px,1fr) minmax(180px,1fr);gap:14px;align-items:start;">
                        @include('admin.partials.image', [
                            'name' => "tools[$i][src]",
                            'value' => $t['src'] ?? '',
                            'label' => '图标 ' . ($i + 1),
                            'size' => 84,
                        ])
                        <div style="display:flex;flex-direction:column;gap:12px;">
                            <div class="field">
                                <label>名称</label>
                                <input type="text" name="tools[{{ $i }}][remark]" value="{{ $t['remark'] ?? '' }}">
                            </div>
                            <div class="field">
                                <label>跳转路径</label>
                                <input type="text" name="tools[{{ $i }}][url]" value="{{ $t['url'] ?? '' }}">
                            </div>
                            <div class="field">
                                <label>启用</label>
                                <select name="tools[{{ $i }}][status]">
                                    <option value="1" @selected(($t['status'] ?? '1') === '1')>启用</option>
                                    <option value="0" @selected(($t['status'] ?? '1') === '0')>停用</option>
                                </select>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card__body" style="border-top:1px solid var(--border);">
            <div class="btn-row">
                <button class="btn btn--primary" type="submit">保存设置</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card__head">
            <div class="card__title">业务参数（config/flow.php）</div>
        </div>
        <div class="card__body card__body--flush">
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>参数</th><th>当前值</th><th>说明</th></tr></thead>
                    <tbody>
                        <tr><td>serve_fee / service_fee</td><td class="num">{{ $flow['service_fee'] }} 元</td><td>参与服务费（一次性）</td></tr>
                        <tr><td>fee_shop</td><td class="num">{{ $flow['fee_shop'] }} 元</td><td>服务费 · 店长分成</td></tr>
                        <tr><td>fee_direct</td><td class="num">{{ $flow['fee_direct'] }} 元</td><td>服务费 · 直推人分成</td></tr>
                        <tr><td>fee_indirect</td><td class="num">{{ $flow['fee_indirect'] }} 元</td><td>服务费 · 间推人分成</td></tr>
                        <tr><td>daily_min / daily_max</td><td class="num">{{ $flow['daily_min'] }} ~ {{ $flow['daily_max'] }} 元</td><td>每日流水额度区间</td></tr>
                        <tr><td>order_rate</td><td class="num">{{ $flow['order_rate'] * 100 }}%</td><td>参与者订单收益</td></tr>
                        <tr><td>direct_rate</td><td class="num">{{ $flow['direct_rate'] * 100 }}%</td><td>第一代直推收益</td></tr>
                        <tr><td>shop_rate</td><td class="num">{{ $flow['shop_rate'] * 100 }}%</td><td>店长收益</td></tr>
                        <tr><td>platform_rate</td><td class="num">{{ $flow['platform_rate'] * 100 }}%</td><td>平台收益</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="card__body muted small">
                修改费率请编辑 <code>php/config/flow.php</code>，保存后执行 <code>php artisan config:clear</code> 生效。
            </div>
        </div>
    </div>
@endsection
