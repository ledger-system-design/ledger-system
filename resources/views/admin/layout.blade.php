@php
    // 出错页可能在 AdminAuth 中间件之前渲染，这里做空值兜底
    $currentAdmin = $currentAdmin ?? null;
    $isSuper = $currentAdmin ? $currentAdmin->isSuper() : false;
    $adminName = $currentAdmin ? ($currentAdmin->name ?: $currentAdmin->username) : '未登录';
    $nav = [
        '概览' => [
            ['route' => 'admin.dashboard', 'label' => '数据概览', 'icon' => '▦'],
            ['route' => 'admin.report', 'label' => '运营报表', 'icon' => '▤', 'super' => true],
        ],
        '用户' => [
            ['route' => 'admin.members.index', 'label' => '用户数据', 'icon' => '☰', 'super' => true],
            ['route' => 'admin.team', 'label' => '推荐关系', 'icon' => '⑃', 'super' => true],
        ],
        '采购' => [
            ['route' => 'admin.orders.index', 'label' => '采购清单', 'icon' => '▣'],
            ['route' => 'admin.records.index', 'label' => '采购记录', 'icon' => '▤', 'badge' => $pendingRecords ?? 0],
        ],
        '资金' => [
            ['route' => 'admin.balances.index', 'label' => '资金明细', 'icon' => '￥', 'super' => true],
            ['route' => 'admin.cashes.index', 'label' => '提现审核', 'icon' => '⇧', 'badge' => $pendingCashes ?? 0, 'super' => true],
        ],
        '配置' => [
            ['route' => 'admin.certs.index', 'label' => '收款方式', 'icon' => '▥', 'super' => true],
            ['route' => 'admin.addresses.index', 'label' => '收货地址', 'icon' => '⌂', 'super' => true],
            ['route' => 'admin.settings.index', 'label' => '系统设置', 'icon' => '⚙', 'super' => true],
            ['route' => 'admin.logs.index', 'label' => '操作日志', 'icon' => '✎', 'super' => true],
            ['route' => 'admin.admins.index', 'label' => '管理员账号', 'icon' => '♟', 'super' => true],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '管理后台') · 账本管理系统</title>
    <link rel="stylesheet" href="/static/admin/admin.css?v=5">
    <script>
        // 后台入口前缀（同时供 admin.js 里的 AJAX 使用）
        window.ADMIN_BASE = @json(url('/admin_manage_6789'));
        window.UPLOAD_URL = @json(url('/app/File/upload'));
        window.CSRF_TOKEN = @json(csrf_token());
    </script>
</head>

<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar__brand">
                账本管理系统
                <small>管理后台 v1.0</small>
            </div>
            @foreach ($nav as $group => $items)
                @php
                    $visible = array_values(array_filter($items, function ($it) use ($isSuper) {
                        return $isSuper || empty($it['super']);
                    }));
                @endphp
                @if ($visible)
                    <div class="sidebar__group">{{ $group }}</div>
                    @foreach ($visible as $item)
                        <a class="sidebar__item {{ request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route'])) ? 'is-active' : '' }}"
                            href="{{ route($item['route']) }}">
                            <span class="ico">{{ $item['icon'] }}</span>
                            <span>{{ $item['label'] }}</span>
                            @if (!empty($item['badge']))
                                <span class="badge">{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                @endif
            @endforeach
        </aside>

        <div class="main">
            <header class="topbar">
                <div class="topbar__title">@yield('title', '数据概览')</div>
                <div class="topbar__right">
                    <a href="/" target="_blank">会员端 →</a>
                    <span>{{ $adminName }}</span>
                    @if ($currentAdmin)
                        <a class="btn btn--sm" href="{{ route('admin.profile') }}">账号设置</a>
                    @endif
                    <a class="btn btn--sm btn--danger" href="{{ route('admin.logout') }}">退出</a>
                </div>
            </header>

            <main class="content">
                @if (session('status'))
                    <div class="alert alert--ok">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert--err">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="/static/admin/admin.js?v=5"></script>
    @stack('scripts')
</body>

</html>
