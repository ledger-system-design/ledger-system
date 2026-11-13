<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>管理员登录 · 账本管理系统</title>
    <link rel="stylesheet" href="/static/admin/admin.css?v=3">
</head>

<body>
    <div class="login-page">
        <form class="login-card" method="post" action="{{ route('admin.login.post') }}">
            @csrf
            <h1>账本管理系统</h1>
            <p class="sub">管理员登录</p>

            @if ($errors->any())
                <div class="alert alert--err">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="field" style="margin-bottom:12px;">
                <label>管理员用户名</label>
                <input type="text" name="username" value="{{ old('username') }}" autocomplete="username" autofocus
                    required>
            </div>

            <div class="field" style="margin-bottom:12px;">
                <label>登录密码</label>
                <input type="password" name="password" autocomplete="current-password" required>
            </div>

            <div class="field" style="margin-bottom:18px;">
                <label>验证码</label>
                <div class="captcha-row">
                    <input type="text" name="captcha" maxlength="4" autocomplete="off" placeholder="请输入右侧 4 位验证码" required>
                    <img id="captchaImg" class="captcha-img" src="{{ route('admin.captcha') }}?t={{ time() }}"
                        alt="验证码" title="点击刷新验证码">
                </div>
                <span class="hint">看不清？点击图片刷新</span>
            </div>

            <button class="btn btn--primary btn--block" type="submit">登 录</button>
        </form>
    </div>

    <script>
        document.getElementById('captchaImg').addEventListener('click', function () {
            this.src = @json(route('admin.captcha')) + '?t=' + Date.now();
        });
    </script>
</body>

</html>
