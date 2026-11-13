/* ==========================================================================
   页面组件（结构与样式一比一对应参考站 H5）
   ========================================================================== */

(function (window) {
    'use strict';

    var Vue = window.Vue;
    var app = window.$app;
    var request = app.request;
    var store = app.store;

    var Pages = {};

    /* ==================================================================
     | 登录
     * ================================================================== */
    Pages.login = {
        data: function () {
            return {
                formData: {},
                shopTitle: ''
            };
        },
        created: function () {
            var self = this;
            request({ url: 'index/setting' }).then(function (res) {
                self.shopTitle = res.index_title;
                store.set('shoptitle', res.index_title);
                store.set('shoplogo', res.index_logo || '');
            }).catch(function () { /* ignore */ });
        },
        methods: {
            handleLogin: function () {
                var form = this.formData;
                if (!form.username) {
                    return app.msg('登录账号不能为空');
                }
                if (!form.userpwd) {
                    return app.msg('请输入密码');
                }
                request({
                    url: 'Login/login',
                    hideLoading: false,
                    loadingText: '登录中...',
                    data: form
                }).then(function (res) {
                    store.set('Authorization', res.access_token);
                    app.msg('登录成功');
                    window.location.hash = '#/pages/index/index';
                }).catch(function () { /* ignore */ });
            },
            goReg: function () {
                window.location.hash = '#/pages/public/reg';
            }
        },
        template: [
            '<div class="login">',
            '  <div class="title"><span class="text">登录</span></div>',
            '  <div class="desc">欢迎您进入{{ shopTitle }}</div>',
            '  <div class="login_info">',
            '    <div class="login_con">',
            '      <uni-text-input v-model="formData.username" type="number" placeholder="请输入账号" maxlength="11"></uni-text-input>',
            '    </div>',
            '    <div class="login_con">',
            '      <uni-text-input v-model="formData.userpwd" type="password" maxlength="16" placeholder="请输入密码"></uni-text-input>',
            '    </div>',
            '    <div class="login_btn" @click="handleLogin">登录</div>',
            '    <div class="login_btn reg" @click="goReg">去注册</div>',
            '  </div>',
            '</div>'
        ].join('\n')
    };

    /* ==================================================================
     | 注册
     * ================================================================== */
    Pages.reg = {
        data: function () {
            return {
                formData: {},
                pageTitle: '',
                pageT: ''
            };
        },
        created: function () {
            this.pageTitle = store.get('shoptitle') || '';
            this.pageT = this.$route.query.invitecode ? 'invite' : '';
            if (this.pageT === 'invite') {
                this.formData = { invitecode: this.$route.query.invitecode };
            }
        },
        methods: {
            back: function () {
                window.location.hash = '#/pages/public/login';
            },
            handleReg: function () {
                var form = this.formData;
                if (!form.username) {
                    return app.msg('注册账号不能为空');
                }
                if (!app.isMobile(form.username)) {
                    return app.msg('请输入正确的手机号码');
                }
                if (!form.userreal) {
                    return app.msg('请输入姓名');
                }
                if (!form.userpwd) {
                    return app.msg('请输入密码');
                }
                if (form.userpwd !== form.userpwd2) {
                    return app.msg('两次输入的密码不一致');
                }
                request({
                    url: 'Login/register',
                    hideLoading: false,
                    loadingText: '注册中...',
                    data: Object.assign({}, form, { data: form })
                }).then(function () {
                    app.msg('注册成功');
                    setTimeout(function () {
                        window.location.hash = '#/pages/public/login';
                    }, 1500);
                }).catch(function () { /* ignore */ });
            }
        },
        template: [
            '<div class="login">',
            '  <div class="title"><span class="text">注册账户</span></div>',
            '  <div class="desc">欢迎您进入{{ pageTitle }}</div>',
            '  <div class="login_info">',
            '    <div style="padding:26rpx 0 34rpx;text-align:center;font-size:30rpx;line-height:1.9;color:#666;">',
            '      新用户注册已关闭',
            '      <br>',
            '      请联系管理员为您开通账号',
            '    </div>',
            '    <div class="login_btn reg" @click="back">返回登录</div>',
            '  </div>',
            '</div>'
        ].join('\n')
    };

    /* ==================================================================
     | 首页
     * ================================================================== */
    Pages.index = {
        data: function () {
            return {
                pageInfo: { index_banner: [], index_nav: [], index_news: [] },
                pageData: {},
                tool: ['1、完成店铺认证', '2、完成我要开店', '3、完成商家信息绑定']
            };
        },
        created: function () {
            this.getData();
            this.getSetting();
        },
        methods: {
            getSetting: function () {
                var self = this;
                request({ url: 'index/setting' }).then(function (res) {
                    self.pageData = res;
                    store.set('shoptitle', res.index_title);
                    store.set('shoplogo', res.index_logo || '');
                }).catch(function () { });
            },
            getData: function () {
                var self = this;
                request({ url: 'Index/indexdata' }).then(function (res) {
                    var news = (res.index_news || []).map(function (item) {
                        return item.title;
                    });
                    self.pageInfo = Object.assign({}, res, { index_news: news });
                }).catch(function () { });
            },
            toPage: function (url) {
                if (url) {
                    if (url === '/pages/index/caigou') {
                        window.location.hash = '#/pages/index/caigou';
                    } else {
                        window.location.hash = '#' + url;
                    }
                } else {
                    app.msg('开发中......');
                }
            },
            // 客户要求：店铺认证不对普通用户开放，仅作展示提示
            certTip: function () {
                app.msg('店铺认证由管理员统一录入，请联系管理员'); 
            }
        },
        template: [
            '<div class="page">',
            '  <div class="page_head flex_center">',
            '    <div class="logo"></div>',
            '    <div class="notice u-flex-1 flex_left">',
            '      <u-notice-bar :list="pageInfo.index_news" color="#CE233D" bg-color="#EDF9EA" border-radius="90"></u-notice-bar>',
            '    </div>',
            '  </div>',
            '  <div class="u-p-30">',
            '    <u-swiper :list="pageInfo.index_banner" name="src" height="310"></u-swiper>',
            '  </div>',
            '  <div class="page_opre">',
            '    <div class="flex_left">',
            '      <div class="logo"><img class="img" src="/static/img/shop.png"></div>',
            '      <div class="name">我的小店</div>',
            '    </div>',
            '    <div class="shop">',
            '      <div class="title">完成<span class="text_price">考试</span> 帮<span class="text_price">您</span>开启成功之路</div>',
            '      <div class="flex_left u-m-t-30">',
            '        <div class="logo"><img class="img" src="/static/img/shoprz.png"></div>',
            '        <div class="u-flex-1 shop_ver">',
            '          <div class="name u-m-b-10">店铺认证</div>',
            '          <div>完成店铺认证解锁更多功能</div>',
            '        </div>',
            '        <div v-if="pageInfo.iscert == 1" class="btn grey">已认证</div>',
            '        <div v-else class="btn" @click="certTip">去认证</div>',
            '      </div>',
            '    </div>',
            '    <u-grid :col="(pageInfo.index_nav || []).length">',
            '      <u-grid-item v-for="(item, i) in pageInfo.index_nav" :key="i" :col="(pageInfo.index_nav || []).length" @click="toPage(item.url)">',
            '        <u-image width="80rpx" height="80rpx" :src="item.src"></u-image>',
            '        <div class="grid_text">{{ item.remark }}</div>',
            '      </u-grid-item>',
            '    </u-grid>',
            '    <div class="today">',
            '      <div class="title">今日任务</div>',
            '      <div v-for="(item, i) in tool" :key="i" class="list flex_left">',
            '        <img class="img" src="/static/img/icon_renwu.png">',
            '        <span>{{ item }}</span>',
            '      </div>',
            '    </div>',
            '  </div>',
            '</div>'
        ].join('\n')
    };

    /* ==================================================================
     | 采购清单
     * ================================================================== */
    Pages.caigou = {
        data: function () {
            return {
                page: 1,
                list: [],
                noList: false,
                TabCur: '0',
                moreStatus: 'loadmore',
                tabsC: [
                    { name: '待付款', value: '0' },
                    { name: '待确认', value: '1' },
                    { name: '已确认', value: '2' },
                    { name: '已完成', value: '3' }
                ]
            };
        },
        created: function () {
            this.getList('refresh');
        },
        methods: {
            getList: function (type) {
                var self = this;
                if (type === 'refresh') {
                    this.page = 1;
                    this.list = [];
                }
                var page = this.page;
                if (page === -1) {
                    return;
                }
                this.moreStatus = 'loading';
                request({
                    url: 'Member/order1',
                    data: { page: page, act: 'list', status: this.TabCur }
                }).then(function (res) {
                    var next = res.current_page === res.last_page ? -1 : page + 1;
                    self.moreStatus = next > 0 ? 'loadmore' : 'nomore';
                    self.list = self.list.concat(res.data);
                    self.page = next;
                    self.noList = !res.data.length;
                }).catch(function () { });
            },
            changeTab: function (item) {
                this.TabCur = item.value;
                this.getList('refresh');
            },
            goDetail: function (item) {
                window.location.hash = '#/pages/index/caidet?orderno=' + item.orderno;
            }
        },
        template: [
            '<div class="caigou">',
            '  <div class="caigou_tabs top_fixed flex_center">',
            '    <div v-for="(item, i) in tabsC" :key="i" class="tab_item u-flex-1"',
            '      :class="{ active: TabCur === item.value }" @click="changeTab(item)">{{ item.name }}</div>',
            '  </div>',
            '  <div class="u-p-30">',
            '    <div v-for="(item, i) in list" :key="i" class="caigou_item">',
            '      <div class="title">订单编号：{{ item.orderno }}</div>',
            '      <div class="flex_center">',
            '        <div class="img_wrap">',
            '          <img class="img" :src="item.indexpic || \'/static/img/nopic.jpg\'">',
            '        </div>',
            '        <div class="u-flex-1 con">',
            '          <div class="u-line-1 name">{{ item.name }}</div>',
            '          <div><span class="tag sold">卖家</span>{{ item.sellerreal }}</div>',
            '          <div><span class="tag buy">买家</span>{{ item.userreal || item.username }}</div>',
            '          <div class="date">{{ item.addtime }}</div>',
            '        </div>',
            '      </div>',
            '      <div class="flex_center item_price">',
            '        <div>金额 <span class="price">{{ item.amount }}</span></div>',
            '      </div>',
            '      <div class="item_opre">',
            '        <div class="btn" @click="goDetail(item)">{{ item.status ? \'查看\' : \'立即支付\' }}</div>',
            '      </div>',
            '    </div>',
            '    <no-list :prop-list="noList" :prop-status="moreStatus"></no-list>',
            '  </div>',
            '</div>'
        ].join('\n')
    };

    /* ==================================================================
     | 订单详情（采购清单详情：收款人 / 付款人 / 金额 / 收款二维码）
     * ================================================================== */
    Pages.caidet = {
        data: function () {
            return {
                model: {},
                showPicker: false,
                selector: [],
                paylist: [],
                payIndex: 0
            };
        },
        created: function () {
            this.getInfo(this.$route.query.orderno);
        },
        computed: {
            currentPay: function () {
                return this.paylist[this.payIndex] || null;
            }
        },
        methods: {
            getInfo: function (orderno) {
                var self = this;
                request({
                    url: 'Member/order1',
                    data: { act: 'view', data: { orderno: orderno } }
                }).then(function (res) {
                    self.model = res;
                    self.paylist = res.paylist || [];
                    self.selector = self.paylist.map(function (item) {
                        return item.name;
                    });
                    app.store.set('currentOrder', JSON.stringify(res));
                }).catch(function () { });
            },
            changePicker: function (indexes) {
                this.payIndex = indexes[0];
                this.showPicker = false;
            },
            ChooseImage: function () {
                var self = this;
                if (this.model.status > 0) {
                    return false;
                }
                app.uploadImage(1).then(function (urls) {
                    self.model = Object.assign({}, self.model, { payphoto: urls[0] });
                }).catch(function () { });
            },
            handleCopy: function () {
                var item = this.currentPay;
                if (!item) {
                    return;
                }
                var text = '';
                if (item.type === 'url') {
                    text = item.url;
                } else if (item.type === 'bank') {
                    text = item.cardno;
                } else if (item.type === 'alipay') {
                    text = item.account;
                }
                app.copyText(text);
            },
            handleConfirm: function () {
                var self = this;
                var model = this.model;
                if (!model.payphoto) {
                    return app.msg('请先上传支付凭证');
                }
                app.confirm({ content: '是否确定支付？' }).then(function () {
                    request({
                        url: 'Member/order1',
                        hideLoading: false,
                        loadingText: '提交中...',
                        data: {
                            act: 'pay',
                            data: {
                                payphoto: model.payphoto,
                                paytype: self.currentPay ? self.currentPay.type : '',
                                payname: self.currentPay ? self.currentPay.name : '',
                                orderno: model.orderno
                            }
                        }
                    }).then(function () {
                        app.msg('确认支付成功');
                        setTimeout(function () {
                            window.location.hash = '#/pages/index/caigou';
                        }, 800);
                    }).catch(function () { });
                }).catch(function () { });
            }
        },
        template: [
            '<div class="caidet">',
            '  <div class="cai_box">',
            '    <div>采购商品名称：{{ model.title }}</div>',
            '    <div>订单编号: {{ model.orderno }}</div>',
            '    <div>下单时间：{{ model.addtime }}</div>',
            '    <div>买家：{{ model.userreal }}</div>',
            '    <div>买家联系方式：{{ model.username }}</div>',
            '    <div>卖家：{{ model.sellerreal }}</div>',
            '    <div>卖家联系方式：{{ model.sellername }}</div>',
            '    <div>采购货款金额: {{ model.amount }}</div>',
            '  </div>',
            '  <div class="cai_box single" v-if="model.status < 1">',
            '    <div class="flex_center">',
            '      <div>支付方式</div>',
            '      <div @click="showPicker = true">',
            '        {{ selector[payIndex] }}',
            '        <span class="custom-icon custom-icon-youjiantou1"></span>',
            '      </div>',
            '    </div>',
            '  </div>',
            '  <div class="cai_box single" v-if="currentPay && model.status < 1">',
            '    <div class="flex_center">',
            '      <div class="u-flex-1">',
            '        <template v-if="currentPay.type === \'url\'">',
            '          <div>{{ currentPay.name }}：{{ currentPay.url }}</div>',
            '        </template>',
            '        <template v-else-if="currentPay.type === \'alipay\'">',
            '          <div class="u-m-b-10 flex_center">',
            '            <div>支付宝账号：{{ currentPay.account }}</div>',
            '            <u-button v-if="currentPay.account" type="primary" size="mini" @click="handleCopy">复制</u-button>',
            '          </div>',
            '          <img class="img pay_qr" :src="currentPay.image">',
            '        </template>',
            '        <template v-else-if="currentPay.type === \'wechat\'">',
            '          <img class="img pay_qr" :src="currentPay.image">',
            '        </template>',
            '        <template v-else-if="currentPay.type === \'bank\'">',
            '          <div>{{ currentPay.name }}卡号：{{ currentPay.cardno }}</div>',
            '          <div>姓名：{{ currentPay.carduser }}</div>',
            '          <div>银行：{{ currentPay.cardbank }}</div>',
            '        </template>',
            '      </div>',
            '      <u-button v-if="currentPay.type !== \'wechat\' && currentPay.type !== \'alipay\'"',
            '        type="primary" size="mini" @click="handleCopy">复制</u-button>',
            '    </div>',
            '  </div>',
            '  <div class="cai_box single">',
            '    <div class="u-m-b-10">支付凭证：</div>',
            '    <div :class="model.payphoto ? \'\' : \'img_box\'" @click="ChooseImage">',
            '      <img v-if="model.payphoto" class="upload_img" :src="model.payphoto">',
            '      <img v-else class="img" src="/static/img/camera.png">',
            '    </div>',
            '  </div>',
            '  <div class="u-p-l-30 u-p-r-30" v-if="model.status < 1">',
            '    <u-button type="success" shape="circle" @click="handleConfirm">确认支付</u-button>',
            '  </div>',
            '  <div class="u-p-30" v-if="model.status == 1">',
            '    <u-button type="info" shape="circle" disabled>等待卖家确认收款</u-button>',
            '  </div>',
            '  <div class="u-p-30" v-if="model.status == 2">',
            '    <u-button type="primary" shape="circle" disabled>卖家已确认，等待收益结算</u-button>',
            '  </div>',
            '  <div class="u-p-30" v-if="model.status == 3">',
            '    <u-button type="success" shape="circle" disabled>交易已完成</u-button>',
            '  </div>',
            '  <u-picker mode="selector" :range="selector" :default-selector="[payIndex]" v-model="showPicker" @confirm="changePicker"></u-picker>',
            '</div>'
        ].join('\n')
    };

    /* ==================================================================
     | 团队采购
     * ================================================================== */
    Pages.team = {
        data: function () {
            return {
                page: 1,
                list: [],
                noList: false,
                logoPng: '',
                loading: 'loadmore',
                summary: { all: {} }
            };
        },
        created: function () {
            this.logoPng = store.get('shoplogo') || '';
            this.getList();
        },
        methods: {
            getList: function () {
                var self = this;
                var page = this.page;
                if (page === -1) {
                    return;
                }
                this.loading = 'loading';
                request({
                    url: 'Member/children',
                    data: { page: page }
                }).then(function (res) {
                    var next = res.current_page === res.last_page ? -1 : page + 1;
                    self.loading = next > 0 ? 'loadmore' : 'nomore';
                    self.summary = (res.summary && res.summary.all) || {};
                    self.list = self.list.concat(res.data);
                    self.page = next;
                    self.noList = !res.data.length;
                }).catch(function () { });
            }
        },
        template: [
            '<div class="team">',
            '  <div class="team_head">',
            '    <div class="user_head"><img class="img" :src="logoPng"></div>',
            '    <div class="team_price">',
            '      <span class="border">团队采购总金额：<span class="text_price">¥{{ summary.amount || 0 }}</span></span>',
            '      <span>采购数量：<span class="text_price">{{ summary.num || 0 }}</span></span>',
            '    </div>',
            '    <div class="team_price">',
            '      <span class="border">个人采购总金额：<span class="text_price">¥{{ summary.amount0 || 0 }}</span></span>',
            '      <span>采购数量：<span class="text_price">{{ summary.num0 || 0 }}</span></span>',
            '    </div>',
            '    <div class="team_price">',
            '      <span class="border">直推采购总金额：<span class="text_price">¥{{ summary.amount1 || 0 }}</span></span>',
            '      <span>采购数量：<span class="text_price">{{ summary.num1 || 0 }}</span></span>',
            '    </div>',
            '    <div class="team_price">团队采购总人数：<span class="text_price">{{ summary.num_team || 0 }}</span></div>',
            '    <div class="team_price">直推采购总人数：<span class="text_price">{{ summary.num_recommend || 0 }}</span></div>',
            '  </div>',
            '  <div class="team_list">',
            '    <div class="team_title">',
            '      <div class="box">姓名</div>',
            '      <div class="username">会员账号</div>',
            '      <div class="box">人数</div>',
            '    </div>',
            '    <div v-for="(item, i) in list" :key="i" class="list_item">',
            '      <div class="box">',
            '        <div>{{ item.userreal }}</div>',
            '        <div class="text_price">{{ item.pidname }}</div>',
            '      </div>',
            '      <div class="username">{{ item.username }}</div>',
            '      <div class="box">{{ item.fchild }}</div>',
            '    </div>',
            '    <no-list :prop-list="noList" :prop-status="loading"></no-list>',
            '  </div>',
            '</div>'
        ].join('\n')
    };

    /* ==================================================================
     | 我的（个人中心）
     * ================================================================== */
    Pages.center = {
        data: function () {
            return {
                showPopup: false,
                logoPng: '',
                tools: [],
                userinfo: {},
                userincome: {}
            };
        },
        created: function () {
            this.logoPng = store.get('shoplogo') || '';
            this.getInfo();
        },
        activated: function () {
            this.getInfo();
        },
        methods: {
            getInfo: function () {
                var self = this;
                request({ url: 'Member/usercenter', hideLoading: false }).then(function (res) {
                    self.tools = res.tools || [];
                    self.userinfo = res.user || {};
                    self.userincome = res.income || {};
                }).catch(function () { });
            },
            toPage: function (item) {
                if (item && item.url) {
                    window.location.hash = '#' + item.url;
                }
            },
            setClip: function (text) {
                app.copyText(text);
            },
            loginOut: function () {
                store.remove('Authorization');
                window.location.hash = '#/pages/public/login';
            },
            goJifen: function () {
                window.location.hash = '#/pages/center/tixianList?link=balance&type=account2&name=' + encodeURIComponent('积分明细');
            },
            goAmount: function (type, name) {
                window.location.hash = '#/pages/center/amountDet?type=' + type + '&name=' + encodeURIComponent(name);
            },
            goTixianList: function () {
                window.location.hash = '#/pages/center/tixianList?link=balance&type=account1&name=' + encodeURIComponent('余额明细');
            }
        },
        template: [
            '<div class="user">',
            '  <div class="user_head">',
            '    <div class="head_con">',
            '      <div class="flex_center">',
            '        <u-avatar :src="userinfo.headimgurl || logoPng" size="105"></u-avatar>',
            '        <div class="u-flex-1 u-m-l-30">',
            '          <div class="user_name">',
            '            {{ userinfo.userreal || userinfo.username }}',
            '            <span class="tag">{{ userinfo.parentname }}</span>',
            '          </div>',
            '        </div>',
            '        <div @click="showPopup = true">',
            '          <span class="custom-icon custom-icon-shezhi01" style="font-size:22px;"></span>',
            '        </div>',
            '      </div>',
            '      <div class="flex_center user_summary">',
            '        <div class="u-flex-1" style="border-right: 2px solid #fff;" @click="setClip(userinfo.invitecode)">',
            '          <div class="u-m-b-10">{{ userinfo.invitecode || \'\' }}</div>',
            '          <div>邀请码</div>',
            '        </div>',
            '        <div class="u-flex-1" @click="goJifen">',
            '          <div class="u-m-b-10">{{ userinfo.balance2 || \'0\' }}</div>',
            '          <div>积分</div>',
            '        </div>',
            '      </div>',
            '    </div>',
            '  </div>',
            '  <div class="user_price">',
            '    <div class="price_item" @click="goAmount(1, \'订单收益\')">',
            '      <div class="price">{{ userincome.bonus1 || \'0\' }}</div>',
            '      <div>订单收益</div>',
            '    </div>',
            '    <div class="price_item" @click="goAmount(2, \'直推收益\')">',
            '      <div class="price">{{ userincome.bonus2 || \'0\' }}</div>',
            '      <div>直推收益</div>',
            '    </div>',
            '    <div class="price_item" @click="goTixianList()">',
            '      <div class="price">{{ userinfo.balance1 || \'0\' }}</div>',
            '      <div>余额</div>',
            '    </div>',
            '  </div>',
            '  <div class="user_box">',
            '    <div class="label flex_center">我的服务</div>',
            '    <u-cell-group :border="false">',
            '      <u-cell-item v-for="(item, i) in tools" :key="i" :title="item.remark" :title-style="{ color: \'#333\' }" @click="toPage(item)">',
            '        <uni-image slot="icon" class="icon"><div :style="{ backgroundImage: \'url(\' + item.src + \')\', backgroundPosition: \'center center\', backgroundSize: \'contain\', backgroundRepeat: \'no-repeat\', width: \'100%\', height: \'100%\' }"></div><img :src="item.src" draggable="false"></uni-image>',
            '      </u-cell-item>',
            '    </u-cell-group>',
            '  </div>',
            '  <u-popup mode="right" v-model="showPopup">',
            '    <div class="popup_con">',
            '      <div class="flex_center">',
            '        <u-avatar :src="userinfo.headimgurl || logoPng" size="105"></u-avatar>',
            '        <div class="u-flex-1 u-m-l-30">',
            '          <div class="user_name">{{ userinfo.userreal || userinfo.username }}</div>',
            '        </div>',
            '      </div>',
            '      <div class="user_box">',
            '        <div class="label flex_center">账户</div>',
            '        <u-cell-group :border="false">',
            '          <u-cell-item title="编辑资料" icon="xiugaishuishuai" :border-bottom="false" :title-style="{ color: \'#333\' }" @click="toPage({ url: \'/pages/center/userinfo\' })"></u-cell-item>',
            '          <u-cell-item title="收货地址" icon="daohang1" :border-bottom="false" :title-style="{ color: \'#333\' }" @click="toPage({ url: \'/pages/center/address\' })"></u-cell-item>',
            '          <u-cell-item title="修改密码" icon="24gf-lock" :border-bottom="false" :title-style="{ color: \'#333\' }" @click="toPage({ url: \'/pages/public/resetPwd\' })"></u-cell-item>',
            '        </u-cell-group>',
            '      </div>',
            '      <u-button type="error" @click="loginOut">退出登录</u-button>',
            '    </div>',
            '  </u-popup>',
            '</div>'
        ].join('\n')
    };

    /* ==================================================================
     | 收益明细 / 明细列表
     |   alwaysPlus = true  → 订单收益 / 直推收益（恒为 +）
     |   alwaysPlus = false → 余额明细 / 提现记录（收入 +，支出 -）
     * ================================================================== */
    function makeDetailPage(alwaysPlus) {
        return {
            data: function () {
                return {
                    noList: false,
                    moreStatus: 'loadmore',
                    type: '',
                    link: '',
                    page: 1,
                    list: []
                };
            },
            created: function () {
                this.type = this.$route.query.type || '';
                this.link = this.$route.query.link || 'balance';
                this.alwaysPlus = alwaysPlus;
                this.getList();
            },
            methods: {
                sign: function (item) {
                    if (this.alwaysPlus) {
                        return '+';
                    }
                    return parseInt(item.type, 10) === 1 ? '+' : '-';
                },
                getList: function () {
                    var self = this;
                    var page = this.page;
                    if (page === -1) {
                        return;
                    }
                    this.moreStatus = 'loading';

                    var payload = { page: page, act: 'list', type: this.type };

                    if (this.link === 'cash') {
                        payload.type = '';
                    } else if (this.$route.query.type === '1' || this.$route.query.type === '2') {
                        payload.type = 'account1';
                        payload.typeid = this.$route.query.type === '1' ? 7 : 8;
                    }

                    request({
                        url: 'Member/' + (this.link || 'balance'),
                        data: payload
                    }).then(function (res) {
                        var next = res.current_page === res.last_page ? -1 : page + 1;
                        self.moreStatus = next > 0 ? 'loadmore' : 'nomore';
                        self.list = self.list.concat(res.data);
                        self.page = next;
                        self.noList = !res.data.length;
                    }).catch(function () { });
                }
            },
            template: [
                '<div class="page">',
                '  <div class="page-content">',
                '    <div v-for="(item, i) in list" :key="i" class="mxitem">',
                '      <div class="u-flex-1">',
                '        <div class="title">{{ item.remark1 }}</div>',
                '        <div class="date">{{ item.addtime }}</div>',
                '      </div>',
                '      <div class="u-text-right">',
                '        <div class="number">{{ sign(item) }}{{ item.amount }}</div>',
                '      </div>',
                '    </div>',
                '    <no-list :prop-list="noList" :prop-status="moreStatus"></no-list>',
                '  </div>',
                '</div>'
            ].join('\n')
        };
    }

    Pages.amountDet = makeDetailPage(true);
    Pages.tixianList = makeDetailPage(false);

    /* ==================================================================
     | 提现收益
     * ================================================================== */
    Pages.tixian = {
        data: function () {
            return { model: { amount: '' }, balance: '0.00' };
        },
        created: function () {
            this.getData();
        },
        methods: {
            getData: function () {
                var self = this;
                request({ url: 'member/balance' }).then(function () {
                    /* 兼容参考站：仅用于触发余额刷新 */
                }).catch(function () { });
                request({ url: 'Member/usercenter' }).then(function (res) {
                    self.balance = res.user.balance1;
                }).catch(function () { });
            },
            handleConfirm: function () {
                var self = this;
                var model = this.model;
                if (!model.amount || parseFloat(model.amount) <= 0) {
                    return app.msg('请输入提现金额');
                }
                app.confirm({ content: '是否确定提现？' }).then(function () {
                    request({
                        url: 'Member/cash',
                        hideLoading: false,
                        loadingText: '提交中...',
                        data: {
                            act: 'add',
                            data: {
                                amount: model.amount,
                                account: 'account1',
                                type: 0
                            }
                        }
                    }).then(function () {
                        app.msg('提交成功');
                        setTimeout(function () {
                            window.location.hash = '#/pages/center/center';
                        }, 1000);
                    }).catch(function () { });
                }).catch(function () { });
            }
        },
        template: [
            '<div class="shop">',
            '  <div class="shop_box form_box">',
            '    <u-form-item label="提现金额" label-width="180">',
            '      <u-input v-model="model.amount" type="number" placeholder="请输入提现金额"></u-input>',
            '    </u-form-item>',
            '  </div>',
            '  <div class="u-p-30">',
            '    <u-button type="success" @click="handleConfirm">提交</u-button>',
            '  </div>',
            '</div>'
        ].join('\n')
    };

    /* ==================================================================
     | 编辑资料
     * ================================================================== */
    Pages.userinfo = {
        data: function () {
            return { formData: {}, logoPng: '' };
        },
        created: function () {
            this.logoPng = store.get('shoplogo') || '';
            this.getInfo();
        },
        methods: {
            getInfo: function () {
                var self = this;
                request({ url: 'Member/profile', data: { act: 'view' }, hideLoading: false }).then(function (res) {
                    self.formData = res;
                }).catch(function () { });
            },
            ChooseImage: function () {
                var self = this;
                app.uploadImage(1).then(function (urls) {
                    self.formData = Object.assign({}, self.formData, { headimgurl: urls[0] });
                }).catch(function () { });
            },
            handleConfirm: function () {
                var form = this.formData;
                request({
                    url: 'Member/profile',
                    hideLoading: false,
                    data: {
                        act: 'edit',
                        data: {
                            headimgurl: form.headimgurl,
                            nickname: form.nickname
                        }
                    }
                }).then(function () {
                    app.msg('修改成功');
                    setTimeout(function () {
                        window.location.hash = '#/pages/center/center';
                    }, 1000);
                }).catch(function () { });
            }
        },
        template: [
            '<div class="info">',
            '  <u-form class="info_form" :model="formData" label-position="left" label-width="200" :label-style="{ color: \'#1A1A1A\' }">',
            '    <u-form-item label="头像">',
            '      <div style="display:flex;justify-content:flex-end;" @click="ChooseImage">',
            '        <u-image :src="formData.headimgurl || logoPng" width="130rpx" height="130rpx" radius="50%"></u-image>',
            '      </div>',
            '    </u-form-item>',
            '    <u-form-item label="昵称" label-position="top">',
            '      <u-input v-model="formData.nickname" placeholder="请输入昵称" :custom-style="{ fontSize: \'36rpx\' }"></u-input>',
            '    </u-form-item>',
            '  </u-form>',
            '  <div class="info_bot">',
            '    <div class="bot_btn" @click="handleConfirm">保存</div>',
            '  </div>',
            '</div>'
        ].join('\n')
    };

    /* ==================================================================
     | 收货地址
     * ================================================================== */
    Pages.address = {
        data: function () {
            return { list: [], page: 1, noList: false, moreStatus: 'loadmore' };
        },
        created: function () {
            this.getList('refresh');
        },
        methods: {
            getList: function (type) {
                var self = this;
                if (type === 'refresh') {
                    this.page = 1;
                    this.list = [];
                }
                var page = this.page;
                if (page === -1) {
                    return;
                }
                request({
                    url: 'Member/address',
                    data: { page: page, act: 'list' }
                }).then(function (res) {
                    var next = res.current_page === res.last_page ? -1 : page + 1;
                    self.moreStatus = next > 0 ? 'loadmore' : 'nomore';
                    self.list = self.list.concat(res.data);
                    self.page = next;
                    self.noList = !res.data.length;
                }).catch(function () { });
            },
            setDefault: function (item, index) {
                var self = this;
                request({
                    url: 'Member/address',
                    data: { act: 'setdefault', data: Object.assign({}, item, { isdefault: 1 }) }
                }).then(function () {
                    app.msg('设置成功');
                    self.list = self.list.map(function (it) {
                        return Object.assign({}, it, { isdefault: 0 });
                    });
                    self.list[index].isdefault = 1;
                }).catch(function () { });
            },
            deleteAddress: function (id, index) {
                var self = this;
                app.confirm({ content: '是否删除该地址？' }).then(function () {
                    request({
                        url: 'Member/address',
                        data: { act: 'delete', data: { id: id } }
                    }).then(function () {
                        app.msg('删除成功');
                        self.list.splice(index, 1);
                    }).catch(function () { });
                }).catch(function () { });
            },
            editAddress: function (id) {
                window.location.hash = '#/pages/center/addressDet?id=' + id;
            },
            addAddress: function () {
                window.location.hash = '#/pages/center/addressDet';
            }
        },
        template: [
            '<div class="address">',
            '  <div class="address_list">',
            '    <div v-for="(item, i) in list" :key="i" class="address_item">',
            '      <div class="u-flex u-row-between u-col-top">',
            '        <div class="flex-sub">',
            '          <div class="name">{{ item.userreal || \'\' }} <span class="tel">{{ item.telephone || \'\' }}</span></div>',
            '          <div class="addr">{{ item.region || \'\' }} {{ item.address || \'\' }}</div>',
            '        </div>',
            '      </div>',
            '      <div class="flex_center addr_bom u-border-top">',
            '        <div class="flex_center" @click.stop="setDefault(item, i)">',
            '          <u-icon :name="item.isdefault ? \'31xuanzhong\' : \'weixuanzhongyuanquan\'"',
            '            :color="item.isdefault ? \'#FF4844\' : \'#000\'" size="18"',
            '            :custom-style="{ marginRight: \'10rpx\', verticalAlign: \'middle\' }"></u-icon>',
            '          <span class="default_desc" :class="item.isdefault ? \'basics\' : \'\'">{{ item.isdefault ? \'已设默认地址\' : \'设置为默认地址\' }}</span>',
            '        </div>',
            '        <div class="opre">',
            '          <span class="btn edit" @click.stop="editAddress(item.id)">修改</span>',
            '          <span class="btn" @click.stop="deleteAddress(item.id, i)">删除</span>',
            '        </div>',
            '      </div>',
            '    </div>',
            '    <no-list :prop-list="noList" :prop-status="moreStatus"></no-list>',
            '  </div>',
            '  <div class="bottom_fixed">',
            '    <div class="btn" @click="addAddress">添加新地址</div>',
            '  </div>',
            '</div>'
        ].join('\n')
    };

    /* ==================================================================
     | 地址详情
     * ================================================================== */
    Pages.addressDet = {
        data: function () {
            return {
                model: { userreal: '', telephone: '', region: '', address: '', isdefault: '' },
                region: [],
                radioList: [{ name: '否' }, { name: '是' }],
                radio: '否',
                pickerShow: false,
                cityList: []
            };
        },
        created: function () {
            this.getCity();
            if (this.$route.query.id) {
                this.getData(this.$route.query.id);
            }
        },
        methods: {
            getCity: function () {
                var self = this;
                request({ url: 'Tool/pcdjson' }).then(function (res) {
                    self.cityList = res;
                }).catch(function () { });
            },
            getData: function (id) {
                var self = this;
                request({
                    url: 'Member/address',
                    data: { act: 'view', data: { id: id } }
                }).then(function (res) {
                    self.model = res;
                    self.radio = res.isdefault ? '是' : '否';
                    self.region = res.region ? String(res.region).split(',') : [];
                }).catch(function () { });
            },
            radioGroupChange: function (value) {
                this.radio = value;
            },
            regionConfirm: function (items) {
                if (!items || !items.length) {
                    return;
                }
                var labels = items.map(function (i) { return i.label; });
                var last = items[items.length - 1];
                this.model = Object.assign({}, this.model, {
                    code: last.value,
                    region: labels.join(',')
                });
            },
            handleSubmit: function () {
                var model = this.model;
                if (!model.userreal) {
                    return app.msg('请输入姓名');
                }
                if (!app.isMobile(model.telephone)) {
                    return app.msg('请输入正确的手机号码');
                }
                if (!model.region) {
                    return app.msg('请选择所在地区');
                }
                if (!model.address) {
                    return app.msg('请输入详细地址');
                }
                var payload = {
                    userreal: model.userreal,
                    telephone: model.telephone,
                    address: model.address,
                    region: model.region,
                    code: model.code,
                    isdefault: this.radio === '是' ? 1 : 0,
                    id: model.id || ''
                };
                request({
                    url: 'Member/address',
                    data: { act: model.id ? 'edit' : 'add', data: payload }
                }).then(function () {
                    app.msg('保存成功');
                    setTimeout(function () {
                        window.location.hash = '#/pages/center/address';
                    }, 800);
                }).catch(function () { });
            }
        },
        template: [
            '<div class="wrap">',
            '  <div class="u-form">',
            '    <u-form-item label="姓名" label-width="150">',
            '      <u-input v-model="model.userreal" placeholder="请输入姓名"></u-input>',
            '    </u-form-item>',
            '    <u-form-item label="手机号码" label-width="150">',
            '      <u-input v-model="model.telephone" type="number" maxlength="11" placeholder="请输入手机号"></u-input>',
            '    </u-form-item>',
            '    <u-form-item label="所在地区" label-width="150">',
            '      <div class="u-input" @click="pickerShow = true">',
            '        <span v-if="model.region" style="font-size:14px;">{{ model.region }}</span>',
            '        <span v-else style="font-size:14px;color:#c0c4cc;">请选择地区</span>',
            '      </div>',
            '    </u-form-item>',
            '    <u-form-item label="详细地址" label-width="150">',
            '      <u-input v-model="model.address" placeholder="请输入详细地址"></u-input>',
            '    </u-form-item>',
            '    <u-form-item label="默认地址" label-width="150">',
            '      <div style="display:flex;">',
            '        <u-radio v-for="(item, i) in radioList" :key="i" :name="item.name" :label="item.name"',
            '          :checked="radio === item.name" active-color="#FF4844" @change="radioGroupChange"></u-radio>',
            '      </div>',
            '    </u-form-item>',
            '  </div>',
            '  <div class="submit" @click="handleSubmit">保存</div>',
            '  <u-select mode="mutil-column-auto" :list="cityList" :default-value="region" v-model="pickerShow" @confirm="regionConfirm"></u-select>',
            '</div>'
        ].join('\n')
    };

    /* ==================================================================
     | 修改密码
     * ================================================================== */
    Pages.resetPwd = {
        data: function () {
            return { pageName: '修改密码', formData: {} };
        },
        created: function () {
            if (this.$route.query.type === 'forget') {
                this.pageName = '重置密码';
            }
        },
        methods: {
            handleLogin: function () {
                var form = this.formData;
                if (!form.userpwd) {
                    return app.msg('请输入密码');
                }
                if (!form.userpwd1) {
                    return app.msg('请输入新密码');
                }
                if (form.userpwd2 !== form.userpwd1) {
                    return app.msg('两次输入的密码不一致');
                }
                request({
                    url: 'member/profile',
                    hideLoading: false,
                    loadingText: '保存中...',
                    data: { act: 'setuserpwd', data: form }
                }).then(function () {
                    app.msg('修改成功，请重新登陆');
                    setTimeout(function () {
                        store.remove('Authorization');
                        window.location.hash = '#/pages/public/login';
                    }, 1000);
                }).catch(function () { });
            }
        },
        template: [
            '<div class="login">',
            '  <div class="login_info">',
            '    <div class="login_con">',
            '      <uni-text-input v-model="formData.userpwd" type="password" placeholder="请输入旧密码"></uni-text-input>',
            '    </div>',
            '    <div class="login_con">',
            '      <uni-text-input v-model="formData.userpwd1" type="password" placeholder="请输入新密码"></uni-text-input>',
            '    </div>',
            '    <div class="login_con">',
            '      <uni-text-input v-model="formData.userpwd2" type="password" placeholder="请再次输入新密码"></uni-text-input>',
            '    </div>',
            '  </div>',
            '  <u-button :type="formData.userpwd1 ? \'primary\' : \'info\'" :disabled="!formData.userpwd" @click="handleLogin">保存</u-button>',
            '</div>'
        ].join('\n')
    };

    /* ==================================================================
     | 店铺认证
     * ================================================================== */
    Pages.shop = {
        data: function () {
            return {
                model: {},
                shopinfo: {},
                payinfo: [
                    { cardbank: '', cardno: '', carduser: '' },
                    { image: '' },
                    { account: '', image: '' },
                    { image: '' },
                    { image: '' }
                ]
            };
        },
        created: function () {
            this.getData();
        },
        methods: {
            getData: function () {
                var self = this;
                request({ url: 'Member/cert', data: { act: 'view' } }).then(function (res) {
                    self.model = res;
                    self.shopinfo = res.shopinfo || {};
                    if (res.payinfo && res.payinfo.length) {
                        self.payinfo = res.payinfo;
                    }
                    if (!self.payinfo[0].cardbank) {
                        self.payinfo[0].cardbank = '浙江支付宝网商银行';
                    }
                }).catch(function () { });
            },
            ChooseImage: function (target, field, index) {
                var self = this;
                app.uploadImage(1).then(function (urls) {
                    if (typeof index !== 'undefined') {
                        var list = self[target].slice();
                        list[index] = Object.assign({}, list[index]);
                        list[index][field] = urls[0];
                        self[target] = list;
                    } else {
                        self[target] = Object.assign({}, self[target], { [field]: urls[0] });
                    }
                }).catch(function () { });
            },
            handleConfirm: function () {
                var self = this;
                app.confirm({ content: '请确认信息无误，是否确定提交？' }).then(function () {
                    request({
                        url: 'Member/cert',
                        hideLoading: false,
                        loadingText: self.model.iscert > 0 ? '修改中...' : '提交中...',
                        data: {
                            act: self.model.iscert > 0 ? 'edit' : 'save',
                            data: Object.assign({}, self.model, {
                                shopinfo: self.shopinfo,
                                payinfo: self.payinfo
                            })
                        }
                    }).then(function () {
                        app.msg((self.model.iscert > 0 ? '修改' : '提交') + '成功');
                        setTimeout(function () {
                            window.location.hash = '#/pages/index/index';
                        }, 900);
                    }).catch(function () { });
                }).catch(function () { });
            }
        },
        template: [
            '<div class="shop">',
            '  <div class="shop_box form_box">',
            '    <u-form-item label="认证状态" label-width="180">',
            '      <div style="font-size:28rpx;">{{ model.iscert > 0 ? \'已认证\' : \'未认证\' }}</div>',
            '    </u-form-item>',
            '    <u-form-item label="营业执照照片" label-width="180" :item-style="{ alignItems: \'flex-start\' }">',
            '      <u-image class="img_box" :src="model.license || \'/static/img/no_img.png\'" width="160rpx" height="160rpx"></u-image>',
            '    </u-form-item>',
            '  </div>',
            '  <div class="shop_box form_box">',
            '    <u-form-item label="支付宝账号" label-width="180">',
            '      <div style="font-size:28rpx;">{{ (payinfo[2] && payinfo[2].account) || \'-\' }}</div>',
            '    </u-form-item>',
            '    <u-form-item label="支付宝商家收款码" label-width="230" :item-style="{ alignItems: \'flex-start\' }">',
            '      <u-image class="img_box" :src="(payinfo[2] && payinfo[2].image) || \'/static/img/no_img.png\'" width="160rpx" height="160rpx"></u-image>',
            '    </u-form-item>',
            '    <u-form-item label="支付宝远程收款码" label-width="230" :item-style="{ alignItems: \'flex-start\' }">',
            '      <u-image class="img_box" :src="(payinfo[3] && payinfo[3].image) || \'/static/img/no_img.png\'" width="160rpx" height="160rpx"></u-image>',
            '    </u-form-item>',
            '    <u-form-item label="微信商家收款码" label-width="230" :item-style="{ alignItems: \'flex-start\' }">',
            '      <u-image class="img_box" :src="(payinfo[1] && payinfo[1].image) || \'/static/img/no_img.png\'" width="160rpx" height="160rpx"></u-image>',
            '    </u-form-item>',
            '    <u-form-item label="银联收款码" label-width="230" :item-style="{ alignItems: \'flex-start\' }">',
            '      <u-image class="img_box" :src="(payinfo[4] && payinfo[4].image) || \'/static/img/no_img.png\'" width="160rpx" height="160rpx"></u-image>',
            '    </u-form-item>',
            '    <u-form-item label="收款人" label-width="230">',
            '      <div style="font-size:28rpx;">{{ (payinfo[0] && payinfo[0].carduser) || \'-\' }}</div>',
            '    </u-form-item>',
            '    <u-form-item label="浙江网商银行卡号" label-width="230">',
            '      <div style="font-size:28rpx;">{{ (payinfo[0] && payinfo[0].cardno) || \'-\' }}</div>',
            '    </u-form-item>',
            '    <u-form-item label="银行名称" label-width="230">',
            '      <div style="font-size:28rpx;">{{ (payinfo[0] && payinfo[0].cardbank) || \'-\' }}</div>',
            '    </u-form-item>',
            '  </div>',
            '  <div style="padding:40rpx 40rpx 80rpx;text-align:center;font-size:26rpx;line-height:1.8;color:#909399;">',
            '    认证资料由管理员统一录入<br>如需认证或修改资料，请联系管理员',
            '  </div>',
            '</div>'
        ].join('\n')
    };

    window.$pages = Pages;
})(window);
