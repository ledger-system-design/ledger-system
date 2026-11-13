<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport"
        content="width=device-width,user-scalable=no,initial-scale=1,maximum-scale=1,minimum-scale=1">
    <meta name="format-detection" content="telephone=no">
    <title>{{ $title ?? '账本管理系统' }}</title>
    <script>
        // 与参考站 H5 一致：html 字号 = 窗口宽 / 20；rpx 按 uni-app 规则换算
        (function () {
            function resize() {
                var w = document.documentElement.clientWidth || window.innerWidth;
                document.documentElement.style.fontSize = (w / 20) + 'px';
                var unit = w > 960 ? 375 : w;
                document.documentElement.style.setProperty('--rpx', (unit / 750) + 'px');
            }
            resize();
            window.addEventListener('resize', resize);
            document.addEventListener('DOMContentLoaded', resize);
        })();
    </script>
    <link rel="stylesheet" href="/static/css/uni-framework.css?v=5">
    <link rel="stylesheet" href="/static/css/component.css?v=5">
    <link rel="stylesheet" href="/static/css/page.css?v=5">
    <link rel="stylesheet" href="/static/css/app.css?v=5">
</head>

<body>
    <div id="app"></div>
    <script src="/static/js/vendor/vue.min.js"></script>
    <script src="/static/js/vendor/vue-router.min.js"></script>
    <script src="/static/js/api.js?v=5"></script>
    <script src="/static/js/components.js?v=5"></script>
    <script src="/static/js/pages.js?v=5"></script>
    <script src="/static/js/app.js?v=5"></script>
</body>

</html>
