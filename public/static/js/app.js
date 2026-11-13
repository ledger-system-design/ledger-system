/* ==========================================================================
   应用入口：页面骨架 + 路由
   --------------------------------------------------------------------------
   骨架结构完全对齐参考站 H5：
     body.uni-body.pages-xxx
       uni-app.uni-app--showtabbar.uni-app--maxwidth
         uni-page
           uni-page-head > .uni-page-head + .uni-placeholder
           uni-page-body > 页面内容
         uni-tabbar.uni-tabbar-bottom
   路由与参考站一致（uni-app hash 风格）：
       http://localhost:9193/#/pages/public/login
   ========================================================================== */

(function (window) {
    'use strict';

    var Vue = window.Vue;
    var VueRouter = window.VueRouter;
    var app = window.$app;
    var Pages = window.$pages;

    Vue.use(VueRouter);
    Vue.config.productionTip = false;

    var NAV_PRIMARY = '#C82F23';

    var routes = [
        { path: '/', redirect: '/pages/index/index' },

        { path: '/pages/public/login', component: Pages.login, meta: { nav: true, title: '', bodyClass: 'pages-public-login', pg: 'pg-public-login' } },
        { path: '/pages/public/reg', component: Pages.reg, meta: { nav: true, title: '', bodyClass: 'pages-public-reg', pg: 'pg-public-reg' } },
        {
            path: '/pages/public/resetPwd', component: Pages.resetPwd,
            meta: { nav: true, title: '修改密码', auth: true, bodyClass: 'pages-public-resetPwd', pg: 'pg-public-reset-pwd' }
        },

        {
            path: '/pages/index/index', component: Pages.index,
            meta: { nav: true, title: '', tab: true, tabIndex: 0, auth: true, shopTitle: true, bodyClass: 'pages-index-index', pg: 'pg-index-index' }
        },
        {
            path: '/pages/index/caigou', component: Pages.caigou,
            meta: { nav: true, title: '采购清单', bg: NAV_PRIMARY, color: '#ffffff', auth: true, bodyClass: 'pages-index-caigou', pg: 'pg-index-caigou' }
        },
        {
            path: '/pages/index/caidet', component: Pages.caidet,
            meta: { nav: true, title: '订单详情', bg: NAV_PRIMARY, color: '#ffffff', auth: true, bodyClass: 'pages-index-caidet', pg: 'pg-index-caidet' }
        },
        {
            path: '/pages/index/shop', component: Pages.shop,
            meta: { nav: true, title: '店铺认证', bg: NAV_PRIMARY, color: '#ffffff', auth: true, bodyClass: 'pages-index-shop', pg: 'pg-index-shop' }
        },

        {
            path: '/pages/team/team', component: Pages.team,
            meta: { nav: true, title: '团队采购', tab: true, tabIndex: 1, auth: true, bodyClass: 'pages-team-team', pg: 'pg-team-team' }
        },

        {
            path: '/pages/center/center', component: Pages.center,
            meta: { nav: true, title: '我的', tab: true, tabIndex: 2, auth: true, bodyClass: 'pages-center-center', pg: 'pg-center-center' }
        },
        {
            path: '/pages/center/userinfo', component: Pages.userinfo,
            meta: { nav: true, title: '编辑资料', bg: NAV_PRIMARY, color: '#ffffff', auth: true, bodyClass: 'pages-center-userinfo', pg: 'pg-center-userinfo' }
        },
        {
            path: '/pages/center/tixian', component: Pages.tixian,
            meta: { nav: true, title: '提现收益', bg: NAV_PRIMARY, color: '#ffffff', auth: true, bodyClass: 'pages-center-tixian', pg: 'pg-center-tixian', rightText: '明细' }
        },
        {
            path: '/pages/center/tixianList', component: Pages.tixianList,
            meta: { nav: true, title: '明细', auth: true, bodyClass: 'pages-center-tixianList', pg: 'pg-center-tixian-list' }
        },
        {
            path: '/pages/center/amountDet', component: Pages.amountDet,
            meta: { nav: true, title: '明细', auth: true, bodyClass: 'pages-center-amountDet', pg: 'pg-center-amount-det' }
        },
        {
            path: '/pages/center/address', component: Pages.address,
            meta: { nav: true, title: '收货地址', auth: true, bodyClass: 'pages-center-address', pg: 'pg-center-address' }
        },
        {
            path: '/pages/center/addressDet', component: Pages.addressDet,
            meta: { nav: true, title: '地址详情', auth: true, bodyClass: 'pages-center-addressDet', pg: 'pg-center-address-det' }
        },

        { path: '*', redirect: '/pages/index/index' }
    ];

    var router = new VueRouter({
        mode: 'hash',
        routes: routes,
        scrollBehavior: function () {
            return { x: 0, y: 0 };
        }
    });

    // 登录守卫
    router.beforeEach(function (to, from, next) {
        var meta = to.meta || {};
        var token = app.store.get('Authorization');

        if (meta.auth && !token) {
            return next('/pages/public/login');
        }
        if (!meta.auth && token && to.path === '/pages/public/login') {
            return next('/pages/index/index');
        }
        next();
    });

    router.afterEach(function (to) {
        var meta = to.meta || {};
        var title = (to.query && to.query.name) ? to.query.name : (meta.title || '');
        var shop = app.store.get('shoptitle') || '';
        document.title = title ? (title + ' - ' + (shop || '账本管理系统')) : (shop || '账本管理系统');

        // 与参考站一致：body 上带页面标识类
        var classes = ['uni-body'];
        if (meta.bodyClass) classes.push(meta.bodyClass);
        document.body.className = classes.join(' ');

        // uni-app 运行时写入的窗口变量（tabbar 高度决定 --window-bottom）
        var root = document.documentElement;
        root.style.setProperty('--window-left', '0px');
        root.style.setProperty('--window-right', '0px');
        root.style.setProperty('--window-top', '0px');
        root.style.setProperty('--window-bottom', meta.tab ? '50px' : '0px');

        window.scrollTo(0, 0);
    });

    var Root = {
        computed: {
            meta: function () {
                return this.$route.meta || {};
            },
            showNav: function () {
                return this.meta.nav !== false;
            },
            showTab: function () {
                return !!this.meta.tab;
            },
            navTitle: function () {
                var q = this.$route.query || {};
                if (q.name) return q.name;
                if (this.meta.title) return this.meta.title;
                // 首页导航栏显示店铺名称（与参考站一致）
                if (this.meta.shopTitle) return app.store.get('shoptitle') || '';
                return '';
            },
            navBg: function () {
                return this.meta.bg || '#ffffff';
            },
            navColor: function () {
                return this.meta.color || '#000000';
            },
            showBack: function () {
                // 与参考站一致：uni-page-head 的 backButton 默认为 true，只有 isQuit 页面才隐藏
                return this.meta.nav !== false;
            },
            appClass: function () {
                return {
                    'uni-app--showtabbar': this.showTab,
                    'uni-app--maxwidth': true
                };
            },
            pageClass: function () {
                return this.meta.pg || '';
            }
        },
        methods: {
            onNavRight: function () {
                if (this.$route.path === '/pages/center/tixian') {
                    window.location.hash = '#/pages/center/tixianList?link=cash&name=' + encodeURIComponent('提现明细');
                }
            }
        },
        template: [
            '<uni-app :class="appClass">',
            '  <uni-page>',
            '    <page-nav v-if="showNav" :title="navTitle" :bg-color="navBg" :color="navColor" :show-back="showBack" :right-text="meta.rightText || \'\'" @right-click="onNavRight"></page-nav>',
            '    <uni-page-wrapper>',
            '      <uni-page-body>',
            '        <div :class="pageClass" style="display:contents">',
            '          <router-view></router-view>',
            '        </div>',
            '      </uni-page-body>',
            '    </uni-page-wrapper>',
            '  </uni-page>',
            '  <page-tabbar v-if="showTab"></page-tabbar>',
            '</uni-app>'
        ].join('\n')
    };

    window.$vue = new Vue({
        el: '#app',
        router: router,
        render: function (h) {
            return h(Root);
        }
    });
})(window);
