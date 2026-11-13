/* ==========================================================================
   基础组件
   --------------------------------------------------------------------------
   DOM 结构与类名严格对齐参考站 H5（uni-app 自定义元素 + uView 组件）：
     - 页面骨架：uni-app / uni-page / uni-page-head / uni-page-body / uni-tabbar
     - uView 组件：u-form-item / u-input / u-cell / u-icon / u-swiper ...
   样式来自 uni-framework.css（uni-app 框架）与 component.css（uView）。
   ========================================================================== */

(function (window) {
    'use strict';

    var Vue = window.Vue;

    /** rpx → CSS 表达式（与 uni.upx2px 等价） */
    function u(n) {
        return 'calc(' + n + ' * var(--rpx))';
    }

    /** uView 的 addUnit：rpx 按 uni-app 规则换算成 px，纯数字视为 px */
    function unit(v, def) {
        if (v === undefined || v === null || v === '') return def;
        var s = String(v);
        var m = /^(-?[\d.]+)rpx$/.exec(s);
        if (m) return u(m[1]);
        if (/^-?[\d.]+(px|em|rem|%|vh|vw)$/.test(s)) return s;
        if (/^-?[\d.]+$/.test(s)) return s + 'px';
        return s;
    }

    /* ------------------------------------------------------------------
     | 导航栏（对齐 uni-page-head 结构）
     * ------------------------------------------------------------------ */
    Vue.component('page-nav', {
        props: {
            title: { type: String, default: '' },
            bgColor: { type: String, default: '#ffffff' },
            color: { type: String, default: '#000000' },
            showBack: { type: Boolean, default: true },
            rightText: { type: String, default: '' }
        },
        methods: {
            back: function () {
                if (window.history.length > 1) window.history.back();
                else window.location.hash = '#/pages/index/index';
            }
        },
        template: [
            '<uni-page-head uni-page-head-type="default">',
            '  <div class="uni-page-head" :style="{ backgroundColor: bgColor, color: color }">',
            '    <div class="uni-page-head-hd">',
            '      <div class="uni-page-head-btn" v-show="showBack" @click="back">',
            '        <i class="uni-btn-icon" :style="{ color: color, fontSize: \'27px\' }">&#xe601;</i>',
            '      </div>',
            '      <div class="uni-page-head-ft"></div>',
            '    </div>',
            '    <div class="uni-page-head-bd">',
            '      <div class="uni-page-head__title" :style="{ fontSize: \'16px\' }">{{ title }}</div>',
            '    </div>',
            '    <div class="uni-page-head-ft">',
            '      <div v-if="rightText" class="uni-page-head-btn" @click="$emit(\'right-click\')">',
            '        <i class="uni-btn-icon" :style="{ color: color, fontSize: \'12px\' }">{{ rightText }}</i>',
            '      </div>',
            '    </div>',
            '  </div>',
            '  <div class="uni-placeholder"></div>',
            '</uni-page-head>'
        ].join('\n')
    });

    /* ------------------------------------------------------------------
     | 底部 tabbar（对齐 uni-tabbar 结构）
     * ------------------------------------------------------------------ */
    Vue.component('page-tabbar', {
        data: function () {
            return {
                list: [
                    { path: '/pages/index/index', text: '首页', icon: '/static/tabcur/index_tab.png', active: '/static/tabcur/index_tabcur.png' },
                    { path: '/pages/team/team', text: '团队采购', icon: '/static/tabcur/team_tab.png', active: '/static/tabcur/team_tabcur.png' },
                    { path: '/pages/center/center', text: '我的', icon: '/static/tabcur/center_tab.png', active: '/static/tabcur/center_tabcur.png' }
                ]
            };
        },
        computed: {
            currentIndex: function () {
                var path = this.$route.path;
                for (var i = 0; i < this.list.length; i++) {
                    if (this.list[i].path === path) return i;
                }
                return 0;
            }
        },
        methods: {
            go: function (item, i) {
                if (i !== this.currentIndex) this.$router.replace(item.path);
            }
        },
        template: [
            '<uni-tabbar class="uni-tabbar-bottom">',
            '  <div class="uni-tabbar" style="backgroundColor:#ffffff">',
            '    <div class="uni-tabbar-border" style="backgroundColor:#000000"></div>',
            '    <div v-for="(item, i) in list" :key="i" class="uni-tabbar__item" @click="go(item, i)">',
            '      <div class="uni-tabbar__bd" style="height:50px">',
            '        <div class="uni-tabbar__icon" style="width:24px;height:24px">',
            '          <img :src="currentIndex === i ? item.active : item.icon" alt="">',
            '        </div>',
            '        <div class="uni-tabbar__label" :style="{ color: currentIndex === i ? \'#303133\' : \'#909399\', fontSize: \'10px\', lineHeight: \'normal\', marginTop: \'3px\' }">',
            '          {{ item.text }}',
            '        </div>',
            '      </div>',
            '    </div>',
            '  </div>',
            '  <div class="uni-placeholder"></div>',
            '</uni-tabbar>'
        ].join('\n')
    });

    /* ------------------------------------------------------------------
     | 图标（uView u-icon）
     * ------------------------------------------------------------------ */
    Vue.component('u-icon', {
        props: {
            name: { type: String, default: '' },
            color: { type: String, default: '' },
            size: { type: [String, Number], default: '23' },
            customPrefix: { type: String, default: 'uicon' },
            label: { type: String, default: '' },
            labelPos: { type: String, default: 'right' },
            labelSize: { type: [String, Number], default: '15' },
            labelColor: { type: String, default: '#606266' },
            marginLeft: { type: [String, Number], default: '6' },
            customStyle: { type: Object, default: function () { return {}; } }
        },
        computed: {
            classes: function () {
                var out = [this.customPrefix + '-' + this.name];
                out.push(this.customPrefix === 'uicon' ? 'u-iconfont' : this.customPrefix);
                return out;
            },
            iconStyle: function () {
                return Object.assign({
                    fontSize: unit(this.size, '23px'),
                    fontWeight: 'normal',
                    top: 0
                }, this.color ? { color: this.color } : {}, this.customStyle);
            },
            labelStyle: function () {
                return {
                    color: this.labelColor,
                    fontSize: unit(this.labelSize, '15px'),
                    marginLeft: this.labelPos === 'right' ? unit(this.marginLeft, '6px') : 0
                };
            }
        },
        template: [
            '<div class="u-icon" :class="\'u-icon--\' + labelPos" @click="$emit(\'click\')">',
            '  <span class="u-icon__icon" :class="classes" :style="iconStyle"><span></span></span>',
            '  <span v-if="label" class="u-icon__label" :style="labelStyle">{{ label }}</span>',
            '</div>'
        ].join('\n')
    });

    /* ------------------------------------------------------------------
     | 图片（uView u-image + uni-image 结构，支持 mode）
     * ------------------------------------------------------------------ */
    Vue.component('u-image', {
        props: {
            src: { type: String, default: '' },
            width: { type: [String, Number], default: '' },
            height: { type: [String, Number], default: '' },
            mode: { type: String, default: 'aspectFill' },
            radius: { type: [String, Number], default: 0 },
            bgColor: { type: String, default: 'transparent' }
        },
        computed: {
            wrapStyle: function () {
                var s = { backgroundColor: this.bgColor, overflow: 'visible' };
                if (this.width !== '') s.width = unit(this.width);
                if (this.height !== '') s.height = unit(this.height);
                s.borderRadius = unit(this.radius, '0px');
                return s;
            },
            radiusStyle: function () {
                return { borderRadius: unit(this.radius, '0px') };
            },
            bgStyle: function () {
                var fit = this.mode === 'aspectFit' ? 'contain' : (this.mode === 'widthFix' ? '100% 100%' : 'cover');
                return {
                    backgroundImage: this.src ? 'url("' + this.src + '")' : 'none',
                    backgroundPosition: 'center center',
                    backgroundSize: fit,
                    backgroundRepeat: 'no-repeat',
                    borderRadius: unit(this.radius, '0px')
                };
            }
        },
        template: [
            '<div class="u-image" :style="wrapStyle" @click="$emit(\'click\')">',
            '  <uni-image class="u-image__image" :style="radiusStyle">',
            '    <div :style="bgStyle"></div>',
            '    <img v-if="src" :src="src" draggable="false">',
            '  </uni-image>',
            '</div>'
        ].join('\n')
    });

    /* ------------------------------------------------------------------
     | 头像（uView u-avatar）
     * ------------------------------------------------------------------ */
    Vue.component('u-avatar', {
        props: {
            src: { type: String, default: '' },
            size: { type: [String, Number], default: 40 },
            text: { type: String, default: '' },
            bgColor: { type: String, default: '#fff' },
            shape: { type: String, default: 'circle' }
        },
        computed: {
            style: function () {
                return {
                    width: u(parseFloat(this.size) || 40),
                    height: u(parseFloat(this.size) || 40),
                    backgroundColor: this.bgColor,
                    borderRadius: this.shape === 'circle' ? '50%' : '10px'
                };
            },
            bgStyle: function () {
                return {
                    backgroundImage: this.src ? 'url("' + this.src + '")' : 'none',
                    backgroundPosition: 'center center',
                    backgroundSize: 'cover',
                    backgroundRepeat: 'no-repeat',
                    width: '100%',
                    height: '100%'
                };
            }
        },
        template:
            '<div class="u-avatar" :style="style">' +
            '  <uni-image class="u-avatar__img"><div :style="bgStyle"></div><img v-if="src" :src="src" draggable="false"></uni-image>' +
            '</div>'
    });

    /* ------------------------------------------------------------------
     | 按钮（uView u-btn）
     * ------------------------------------------------------------------ */
    Vue.component('u-button', {
        props: {
            type: { type: String, default: 'default' },
            size: { type: String, default: 'default' },
            shape: { type: String, default: 'square' },
            plain: { type: Boolean, default: false },
            disabled: { type: Boolean, default: false },
            loading: { type: Boolean, default: false },
            hairLine: { type: Boolean, default: true },
            customStyle: { type: Object, default: function () { return {}; } }
        },
        computed: {
            classes: function () {
                return [
                    'u-size-' + this.size,
                    this.plain ? 'u-btn--' + this.type + '--plain' : '',
                    this.loading ? 'u-loading' : '',
                    this.shape === 'circle' ? 'u-round-circle' : '',
                    'u-btn--' + this.type,
                    this.disabled ? 'u-btn--' + this.type + '--disabled' : ''
                ];
            }
        },
        methods: {
            click: function (e) {
                if (this.disabled) return;
                this.$emit('click', e);
            }
        },
        template:
            '<uni-button class="u-btn u-line-1 u-fix-ios-appearance" :class="classes" :style="customStyle" @click="click">' +
            '  <slot></slot>' +
            '  <div class="u-btn__icon"><slot name="icon"></slot></div>' +
            '</uni-button>'
    });

    /* ------------------------------------------------------------------
     | 原生输入框（uni-input 结构，用于登录 / 注册 / 改密页）
     * ------------------------------------------------------------------ */
    Vue.component('uni-text-input', {
        props: {
            value: { type: [String, Number], default: '' },
            type: { type: String, default: 'text' },
            placeholder: { type: String, default: '' },
            maxlength: { type: [String, Number], default: 140 },
            inputClass: { type: String, default: 'input' },
            placeholderClass: { type: String, default: 'input-placeholder' }
        },
        methods: {
            onInput: function (e) {
                this.$emit('input', e.target.value);
            }
        },
        template:
            '<uni-input :class="inputClass">' +
            '  <div class="uni-input-wrapper">' +
            '    <div v-show="!String(value).length" class="uni-input-placeholder" :class="placeholderClass">{{ placeholder }}</div>' +
            '    <input class="uni-input-input" :type="type" :value="value" :maxlength="maxlength" autocomplete="off" @input="onInput">' +
            '  </div>' +
            '</uni-input>'
    });

    /* ------------------------------------------------------------------
     | 输入框（uView u-input + uni-input 结构）
     * ------------------------------------------------------------------ */
    Vue.component('u-input', {
        props: {
            value: { type: [String, Number], default: '' },
            type: { type: String, default: 'text' },
            placeholder: { type: String, default: '' },
            maxlength: { type: [String, Number], default: 140 },
            disabled: { type: Boolean, default: false },
            border: { type: Boolean, default: false },
            clearable: { type: Boolean, default: true },
            inputAlign: { type: String, default: 'left' },
            selectOpen: { type: Boolean, default: false },
            customStyle: { type: Object, default: function () { return {}; } }
        },
        data: function () {
            return { focused: false };
        },
        computed: {
            wrapStyle: function () {
                return Object.assign({
                    padding: this.border ? u(20) : '0',
                    borderColor: '#dcdfe6',
                    textAlign: this.inputAlign
                }, this.customStyle);
            },
            inputStyle: function () {
                return { minHeight: u(70) };
            },
            inputType: function () {
                if (this.type === 'password') return 'text';
                if (this.type === 'select' || this.type === 'text') return 'text';
                return this.type;
            },
            isPassword: function () {
                return this.type === 'password';
            }
        },
        methods: {
            onInput: function (e) {
                this.$emit('input', e.target.value);
                this.$emit('change', e.target.value);
            },
            onFocus: function () {
                this.focused = true;
            },
            onBlur: function () {
                this.focused = false;
            },
            clear: function () {
                this.$emit('input', '');
                this.$emit('change', '');
            }
        },
        template: [
            '<div class="u-input" :class="{ \'u-input--border\': border }" :style="wrapStyle" @click="$emit(\'click\')">',
            '  <uni-input class="u-input__input" :style="inputStyle">',
            '    <div class="uni-input-wrapper">',
            '      <div v-show="!String(value).length" class="uni-input-placeholder input-placeholder" style="color:#c0c4cc">{{ placeholder }}</div>',
            '      <input class="uni-input-input" :type="inputType" :value="value" :maxlength="maxlength" :disabled="disabled || type === \'select\'" autocomplete="off" @input="onInput" @focus="onFocus" @blur="onBlur">',
            '    </div>',
            '  </uni-input>',
            '  <div class="u-input__right-icon u-flex">',
            '    <div v-if="clearable && String(value).length && focused" class="u-input__right-icon__clear u-input__right-icon__item" @click.stop="clear">',
            '      <u-icon size="32" name="close-circle-fill" color="#c0c4cc"></u-icon>',
            '    </div>',
            '    <div v-if="type === \'select\'" class="u-input__right-icon--select u-input__right-icon__item" :class="{ \'u-input__right-icon--select--reverse\': selectOpen }">',
            '      <u-icon name="arrow-down-fill" size="26" color="#c0c4cc"></u-icon>',
            '    </div>',
            '  </div>',
            '</div>'
        ].join('\n')
    });

    /* ------------------------------------------------------------------
     | 表单项（uView u-form-item，label 与内容横排）
     * ------------------------------------------------------------------ */
    Vue.component('u-form-item', {
        props: {
            label: { type: String, default: '' },
            labelWidth: { type: [String, Number], default: '' },
            labelPosition: { type: String, default: 'left' },
            labelAlign: { type: String, default: 'left' },
            labelStyle: { type: Object, default: function () { return {}; } },
            itemStyle: { type: Object, default: function () { return {}; } },
            required: { type: Boolean, default: false },
            leftIcon: { type: String, default: '' },
            rightIcon: { type: String, default: '' },
            borderBottom: { type: Boolean, default: true },
            flexAlignItem: { type: String, default: 'center' },
            rightIconStyle: { type: Object, default: function () { return {}; } }
        },
        inject: { uFormConf: { default: null } },
        computed: {
            isLabelWidthExplicit: function () {
                return this._props.labelWidth !== undefined && this._props.labelWidth !== '';
            },
            elLabelWidth: function () {
                if (this.isLabelWidthExplicit) return this.labelWidth;
                return this.uFormConf ? this.uFormConf.labelWidth : '';
            },
            elLabelPosition: function () {
                if (this._props.labelPosition !== undefined && this._props.labelPosition !== 'left') return this.labelPosition;
                return this.uFormConf ? this.uFormConf.labelPosition : this.labelPosition;
            },
            elLabelAlign: function () {
                if (this._props.labelAlign !== undefined && this._props.labelAlign !== 'left') return this.labelAlign;
                return this.uFormConf ? this.uFormConf.labelAlign : this.labelAlign;
            },
            elLabelStyle: function () {
                return Object.keys(this.labelStyle).length ? this.labelStyle : (this.uFormConf ? this.uFormConf.labelStyle : {});
            },
            uLabelWidth: function () {
                // uView 的 labelWidth 单位为 rpx
                return this.elLabelWidth === '' ? 'auto' : u(this.elLabelWidth);
            },
            bodyStyle: function () {
                return Object.assign({}, this.itemStyle, {
                    flexDirection: this.elLabelPosition === 'left' ? 'row' : 'column'
                });
            },
            leftStyle: function () {
                var s = {
                    alignItems: this.flexAlignItem,
                    marginBottom: this.elLabelPosition === 'left' ? 0 : u(10)
                };
                if (this.elLabelWidth !== '') {
                    s.width = this.uLabelWidth;
                    s.flex = '0 0 ' + this.uLabelWidth;
                }
                return s;
            },
            labelInnerStyle: function () {
                var pos = this.elLabelAlign;
                return Object.assign({}, this.elLabelStyle, {
                    justifyContent: pos === 'left' ? 'flex-start' : (pos === 'center' ? 'center' : 'flex-end')
                });
            }
        },
        template: [
            '<div class="u-form-item" :class="{ \'u-border-bottom\': borderBottom }">',
            '  <div class="u-form-item__body" :style="bodyStyle">',
            '    <div class="u-form-item--left" :style="leftStyle">',
            '      <div v-if="label || required || leftIcon" class="u-form-item--left__content">',
            '        <span v-if="required" class="u-form-item--left__content--required">*</span>',
            '        <div v-if="leftIcon" class="u-form-item--left__content__icon"><u-icon :name="leftIcon"></u-icon></div>',
            '        <div class="u-form-item--left__content__label" :style="labelInnerStyle"> {{ label }} </div>',
            '      </div>',
            '    </div>',
            '    <div class="u-form-item--right u-flex">',
            '      <div class="u-form-item--right__content">',
            '        <div class="u-form-item--right__content__slot "><slot></slot></div>',
            '        <div v-if="rightIcon || $slots.right" class="u-form-item--right__content__icon u-flex">',
            '          <u-icon v-if="rightIcon" :name="rightIcon" :custom-style="rightIconStyle"></u-icon>',
            '          <slot name="right"></slot>',
            '        </div>',
            '      </div>',
            '    </div>',
            '  </div>',
            '</div>'
        ].join('\n')
    });

    Vue.component('u-form', {
        props: {
            model: { type: Object, default: function () { return {}; } },
            labelWidth: { type: [String, Number], default: '' },
            labelPosition: { type: String, default: 'left' },
            labelAlign: { type: String, default: 'left' },
            labelStyle: { type: Object, default: function () { return {}; } }
        },
        provide: function () {
            var self = this;
            return {
                uFormConf: {
                    get labelWidth() { return self.labelWidth; },
                    get labelPosition() { return self.labelPosition; },
                    get labelAlign() { return self.labelAlign; },
                    get labelStyle() { return self.labelStyle; }
                }
            };
        },
        methods: {
            setRules: function () { },
            validate: function (cb) { if (typeof cb === 'function') cb(true); }
        },
        template: '<div class="u-form"><slot></slot></div>'
    });

    /* ------------------------------------------------------------------
     | 单元格（uView u-cell-item / u-cell-group）
     * ------------------------------------------------------------------ */
    Vue.component('u-cell-group', {
        props: { border: { type: Boolean, default: true } },
        template: '<div class="u-cell-box"><div class="u-cell-item-box"><slot></slot></div></div>'
    });

    Vue.component('u-cell-item', {
        props: {
            title: { type: [String, Number], default: '' },
            label: { type: String, default: '' },
            value: { type: [String, Number], default: '' },
            icon: { type: String, default: '' },
            arrow: { type: Boolean, default: true },
            borderBottom: { type: Boolean, default: true },
            titleStyle: { type: Object, default: function () { return {}; } },
            customStyle: { type: Object, default: function () { return {}; } }
        },
        template: [
            '<div class="u-cell" :class="{ \'u-border-bottom\': borderBottom }" :style="customStyle" @click="$emit(\'click\')">',
            '  <div v-if="icon || $slots.icon" class="u-flex">',
            '    <slot name="icon"><u-icon :name="icon" size="19"></u-icon></slot>',
            '  </div>',
            '  <div class="u-cell_title" :style="Object.assign({ width: \'auto\' }, titleStyle)">',
            '    {{ title }}',
            '    <div v-if="label" class="u-cell__label">{{ label }}</div>',
            '  </div>',
            '  <div class="u-cell__value">{{ value }}</div>',
            '  <div v-if="arrow" class="u-icon u-icon-wrap u-cell__right-icon-wrap u-icon--right" style="transform:rotate(0deg)">',
            '    <span class="u-icon__icon uicon-arrow-right u-iconfont" style="font-size:inherit;font-weight:normal;top:0"><span></span></span>',
            '  </div>',
            '</div>'
        ].join('\n')
    });

    Vue.component('u-cell', Vue.component('u-cell-item'));

    /* ------------------------------------------------------------------
     | 弹出层显隐 mixin
     | ------------------------------------------------------------------
     | 与 uView u-popup 完全一致：showPopup 控制组件是否渲染，showTrans 控制
     | 位移/透明度过渡。关闭时整棵节点被移除，避免隐藏的遮罩层（position:fixed
     | 铺满整屏）继续拦截页面点击。
     */
    var popupMixin = {
        data: function () {
            return { showPopup: false, showTrans: false };
        },
        created: function () {
            if (this.value) {
                this.showPopup = true;
                this.showTrans = true;
            }
        },
        watch: {
            value: function (v) {
                var self = this;
                clearTimeout(this._popupHideTimer);
                clearTimeout(this._popupShowTimer);
                if (v) {
                    this.showPopup = true;
                    this._popupShowTimer = setTimeout(function () { self.showTrans = true; }, 20);
                } else {
                    this.showTrans = false;
                    this._popupHideTimer = setTimeout(function () { self.showPopup = false; }, 300);
                }
            }
        },
        beforeDestroy: function () {
            clearTimeout(this._popupHideTimer);
            clearTimeout(this._popupShowTimer);
        }
    };

    /* ------------------------------------------------------------------
     | 遮罩 + 弹出层（uView u-mask / u-drawer）
     * ------------------------------------------------------------------ */
    Vue.component('u-mask', {
        props: {
            show: { type: Boolean, default: false },
            bgColor: { type: String, default: 'rgba(0, 0, 0, 0.4)' },
            maskClickAble: { type: Boolean, default: true }
        },
        template:
            '<div class="u-mask" :class="{ \'u-mask-show\': show }" :style="{ background: bgColor, zIndex: 1001 }" @click="maskClickAble && $emit(\'click\')"></div>'
    });

    Vue.component('u-popup', {
        mixins: [popupMixin],
        props: {
            value: { type: Boolean, default: false },
            mode: { type: String, default: 'right' },
            maskCloseAble: { type: Boolean, default: true },
            length: { type: [String, Number], default: 'auto' },
            mask: { type: Boolean, default: true }
        },
        computed: {
            contentStyle: function () {
                var s = {};
                if (this.mode === 'left' || this.mode === 'right') {
                    s.width = this.length === 'auto' ? u(600) : unit(this.length);
                    s.transform = this.showTrans
                        ? 'translate3d(0, 0, 0)'
                        : (this.mode === 'left' ? 'translate3d(-100%, 0, 0)' : 'translate3d(100%, 0, 0)');
                } else {
                    s.height = this.length === 'auto' ? u(600) : unit(this.length);
                    s.transform = this.showTrans
                        ? 'translate3d(0, 0, 0)'
                        : (this.mode === 'top' ? 'translate3d(0, -100%, 0)' : 'translate3d(0, 100%, 0)');
                }
                return s;
            }
        },
        methods: {
            close: function () {
                this.$emit('input', false);
                this.$emit('close');
            }
        },
        template: [
            '<div v-if="showPopup">',
            '  <u-mask :show="showTrans" :mask-click-able="maskCloseAble" @click="close"></u-mask>',
            '  <div class="u-drawer">',
            '    <div class="u-drawer-content" :class="[\'u-drawer-\' + mode, { \'u-drawer-content-visible\': showTrans }]" :style="contentStyle">',
            '      <slot></slot>',
            '    </div>',
            '  </div>',
            '</div>'
        ].join('\n')
    });

    /* ------------------------------------------------------------------
     | 单列选择器
     * ------------------------------------------------------------------ */
    Vue.component('u-picker', {
        mixins: [popupMixin],
        props: {
            value: { type: Boolean, default: false },
            mode: { type: String, default: 'selector' },
            range: { type: Array, default: function () { return []; } },
            defaultSelector: { type: Array, default: function () { return [0]; } },
            title: { type: String, default: '' }
        },
        data: function () {
            return { current: 0 };
        },
        watch: {
            value: function (v) {
                if (v) this.current = this.defaultSelector[0] || 0;
            }
        },
        methods: {
            close: function () { this.$emit('input', false); },
            confirm: function () {
                this.$emit('confirm', [this.current]);
                this.$emit('input', false);
            }
        },
        template: [
            '<div v-if="showPopup">',
            '  <u-mask :show="showTrans" @click="close"></u-mask>',
            '  <div class="u-drawer">',
            '    <div class="u-drawer-content u-drawer-bottom" :class="{ \'u-drawer-content-visible\': showTrans }" style="max-height:60vh">',
            '      <div class="u-select__header" style="height:44px">',
            '        <div class="u-btn-picker u-btn-picker--tips" @click="close">取消</div>',
            '        <div class="u-select__hader__title">{{ title }}</div>',
            '        <div class="u-btn-picker u-btn-picker--primary" @click="confirm">确定</div>',
            '      </div>',
            '      <div style="max-height:44vh;overflow-y:auto">',
            '        <div v-for="(item, i) in range" :key="i" class="u-select__body__picker-view__item" style="height:43px" :class="{ \'u-select__hader__title\': current === i }" @click="current = i">{{ item }}</div>',
            '      </div>',
            '    </div>',
            '  </div>',
            '</div>'
        ].join('\n')
    });

    /* ------------------------------------------------------------------
     | 多列联动选择器（省市区）
     * ------------------------------------------------------------------ */
    Vue.component('u-select', {
        mixins: [popupMixin],
        props: {
            value: { type: Boolean, default: false },
            list: { type: Array, default: function () { return []; } },
            mode: { type: String, default: 'single-column' },
            defaultValue: { type: Array, default: function () { return []; } },
            title: { type: String, default: '请选择' }
        },
        data: function () {
            return { cols: [0, 0, 0], inited: false };
        },
        watch: {
            value: function (v) {
                if (v && !this.inited) { this.initIndex(); this.inited = true; }
            },
            list: function () { this.initIndex(); },
            defaultValue: function () { this.initIndex(); }
        },
        computed: {
            col1: function () { return this.list || []; },
            col2: function () { var a = this.col1[this.cols[0]]; return (a && a.children) || []; },
            col3: function () { var b = this.col2[this.cols[1]]; return (b && b.children) || []; },
            columns: function () {
                return [this.col1, this.col2, this.col3].filter(function (c) { return c && c.length; });
            },
            selected: function () {
                var out = [];
                if (this.col1[this.cols[0]]) out.push(this.col1[this.cols[0]]);
                if (this.col2[this.cols[1]]) out.push(this.col2[this.cols[1]]);
                if (this.col3[this.cols[2]]) out.push(this.col3[this.cols[2]]);
                return out;
            }
        },
        methods: {
            initIndex: function () {
                var dv = this.defaultValue || [];
                var next = [0, 0, 0];
                for (var i = 0; i < dv.length; i++) {
                    var arr = i === 0 ? this.col1 : (i === 1 ? this.col2 : this.col3);
                    for (var j = 0; j < arr.length; j++) {
                        if (arr[j].label === dv[i] || arr[j].value === dv[i]) { next[i] = j; break; }
                    }
                }
                this.cols = next;
            },
            pick: function (colIndex, itemIndex) {
                var next = this.cols.slice();
                next[colIndex] = itemIndex;
                for (var k = colIndex + 1; k < 3; k++) next[k] = 0;
                this.cols = next;
            },
            close: function () { this.$emit('input', false); },
            confirm: function () {
                this.$emit('confirm', this.selected);
                this.$emit('input', false);
            }
        },
        template: [
            '<div v-if="showPopup">',
            '  <u-mask :show="showTrans" @click="close"></u-mask>',
            '  <div class="u-drawer">',
            '    <div class="u-drawer-content u-drawer-bottom" :class="{ \'u-drawer-content-visible\': showTrans }">',
            '      <div class="u-select__header">',
            '        <div class="u-btn-picker u-btn-picker--tips" @click="close">取消</div>',
            '        <div class="u-select__hader__title">{{ title }}</div>',
            '        <div class="u-btn-picker u-btn-picker--primary" @click="confirm">确定</div>',
            '      </div>',
            '      <div class="u-select__body">',
            '        <div class="u-select__body__picker-view" style="display:flex">',
            '          <div v-for="(col, ci) in columns" :key="ci" style="flex:1;height:100%;overflow-y:auto">',
            '            <div v-for="(item, ii) in col" :key="ii" class="u-select__body__picker-view__item" style="height:50px"',
            '              :class="{ \'u-select__hader__title\': cols[ci] === ii }" @click="pick(ci, ii)">{{ item.label }}</div>',
            '          </div>',
            '        </div>',
            '      </div>',
            '    </div>',
            '  </div>',
            '</div>'
        ].join('\n')
    });

    /* ------------------------------------------------------------------
     | 单选（uView u-radio）
     * ------------------------------------------------------------------ */
    Vue.component('u-radio', {
        props: {
            name: { type: [String, Number], default: '' },
            label: { type: String, default: '' },
            checked: { type: Boolean, default: false },
            shape: { type: String, default: 'circle' },
            activeColor: { type: String, default: '#2979ff' },
            size: { type: [String, Number], default: 42 }
        },
        computed: {
            wrapStyle: function () {
                var s = parseFloat(this.size) || 42;
                var st = {
                    width: u(s),
                    height: u(s),
                    fontSize: '20px'
                };
                if (this.checked) {
                    st.color = '#fff';
                    st.backgroundColor = this.activeColor;
                    st.borderColor = this.activeColor;
                }
                return st;
            }
        },
        template:
            '<div class="u-radio" @click="$emit(\'change\', name)">' +
            '  <div class="u-radio__icon-wrap" :class="[\'u-radio__icon-wrap--\' + shape, { \'u-radio__icon-wrap--checked\': checked }]" :style="wrapStyle">' +
            '    <span v-if="checked" class="u-icon__icon uicon-checkmark u-iconfont" style="font-size:12px"><span></span></span>' +
            '  </div>' +
            '  <div class="u-radio__label"><slot>{{ label }}</slot></div>' +
            '</div>'
    });

    Vue.component('u-radio-group', {
        props: { value: { type: [String, Number], default: '' } },
        template: '<div class="u-radio-group"><slot></slot></div>'
    });

    /* ------------------------------------------------------------------
     | 公告栏（uView u-notice-bar）
     * ------------------------------------------------------------------ */
    Vue.component('u-notice-bar', {
        props: {
            list: { type: Array, default: function () { return []; } },
            color: { type: String, default: '#f9ae3d' },
            bgColor: { type: String, default: '#fdf6ec' },
            borderRadius: { type: [String, Number], default: 8 },
            volumeIcon: { type: Boolean, default: true }
        },
        computed: {
            wrapStyle: function () {
                return { borderRadius: unit(this.borderRadius, '8px') };
            },
            barStyle: function () {
                return { background: this.bgColor, padding: u(18) + ' ' + u(24) };
            },
            text: function () {
                return (this.list || []).join('    ');
            },
            duration: function () {
                return Math.max(3, String(this.text).length * 0.35).toFixed(2) + 's';
            }
        },
        template: [
            '<div class="u-notice-bar-wrap" :style="wrapStyle">',
            '  <div class="u-notice-bar u-type-warning-light-bg" mode="horizontal" :style="barStyle">',
            '    <div class="u-direction-row">',
            '      <div class="u-icon-wrap">',
            '        <div class="u-icon u-left-icon u-icon--right" v-if="volumeIcon">',
            '          <span class="u-icon__icon uicon-volume u-iconfont" :style="{ fontSize: \'17px\', fontWeight: \'normal\', top: 0, color: color }"><span></span></span>',
            '        </div>',
            '      </div>',
            '      <div id="u-notice-box" class="u-notice-box">',
            '        <div id="u-notice-content" class="u-notice-content" :style="{ animationDuration: duration }">',
            '          <span class="u-notice-text u-type-warning" :style="{ color: color, fontSize: \'14px\' }"><span>{{ text }}</span></span>',
            '        </div>',
            '      </div>',
            '      <div class="u-icon-wrap"></div>',
            '    </div>',
            '  </div>',
            '</div>'
        ].join('\n')
    });

    /* ------------------------------------------------------------------
     | 轮播（uView u-swiper + 手动滑动）
     * ------------------------------------------------------------------ */
    Vue.component('u-swiper', {
        props: {
            list: { type: Array, default: function () { return []; } },
            name: { type: String, default: 'src' },
            height: { type: [String, Number], default: 300 },
            interval: { type: [String, Number], default: 3500 },
            duration: { type: [String, Number], default: 300 },
            radius: { type: [String, Number], default: 8 },
            indicatorMode: { type: String, default: 'round' },
            autoplay: { type: Boolean, default: true },
            circular: { type: Boolean, default: true },
            current: { type: Number, default: 0 }
        },
        data: function () {
            return { index: 0, dragging: false, startX: 0, startY: 0, offset: 0, timer: null };
        },
        computed: {
            images: function () {
                var name = this.name;
                return (this.list || []).map(function (item) {
                    if (typeof item === 'string') return item;
                    return item[name] || item.src || '';
                }).filter(Boolean);
            },
            wrapStyle: function () {
                return { borderRadius: unit(this.radius, '8px') };
            },
            swiperStyle: function () {
                return { height: u(this.height), backgroundColor: '#f3f4f6' };
            },
            trackStyle: function () {
                var pct = -this.index * 100;
                var px = this.dragging ? this.offset : 0;
                return {
                    transform: 'translate3d(calc(' + pct + '% + ' + px + 'px), 0, 0)',
                    transition: this.dragging ? 'none' : 'transform ' + (parseInt(this.duration, 10) || 300) + 'ms'
                };
            },
            indicatorStyle: function () {
                return { top: 'auto', bottom: u(12), justifyContent: 'center', padding: '0 ' + u(24) };
            },
            itemStyle: function () {
                return { borderRadius: unit(this.radius, '8px'), transform: 'scaleY(1)', margin: 0 };
            }
        },
        watch: {
            current: function (v) { this.index = v; },
            images: function () { this.start(); }
        },
        mounted: function () {
            this.start();
        },
        beforeDestroy: function () {
            clearInterval(this.timer);
        },
        methods: {
            start: function () {
                var self = this;
                clearInterval(this.timer);
                if (!this.autoplay) return;
                this.timer = setInterval(function () {
                    if (!self.dragging && self.images.length > 1) {
                        self.index = (self.index + 1) % self.images.length;
                    }
                }, parseInt(this.interval, 10) || 3500);
            },
            onStart: function (e) {
                if (this.images.length < 2) return;
                var p = this.point(e);
                this.dragging = true;
                this.startX = p.x;
                this.startY = p.y;
                this.offset = 0;
            },
            onMove: function (e) {
                if (!this.dragging) return;
                var p = this.point(e);
                var dx = p.x - this.startX;
                var dy = p.y - this.startY;
                // 横向拖动才拦截，纵向保留页面滚动
                if (Math.abs(dx) > Math.abs(dy)) {
                    this.offset = dx;
                    if (e.cancelable) e.preventDefault();
                }
            },
            onEnd: function () {
                if (!this.dragging) return;
                this.dragging = false;
                var w = this.$el.clientWidth || 1;
                var threshold = Math.max(20, w * 0.15);
                var moved = this.offset;
                this.offset = 0;
                if (moved <= -threshold) this.next();
                else if (moved >= threshold) this.prev();
            },
            next: function () {
                if (this.index >= this.images.length - 1) {
                    this.index = this.circular ? 0 : this.index;
                } else {
                    this.index++;
                }
            },
            prev: function () {
                if (this.index <= 0) {
                    this.index = this.circular ? this.images.length - 1 : 0;
                } else {
                    this.index--;
                }
            },
            point: function (e) {
                var t = (e.touches && e.touches[0]) || (e.changedTouches && e.changedTouches[0]) || e;
                return { x: t.clientX, y: t.clientY };
            },
            goTo: function (i) { this.index = i; }
        },
        template: [
            '<div class="u-swiper-wrap" :style="wrapStyle">',
            '  <uni-swiper :style="swiperStyle"',
            '    @touchstart="onStart" @touchmove="onMove" @touchend="onEnd" @touchcancel="onEnd"',
            '    @mousedown="onStart" @mousemove="onMove" @mouseup="onEnd" @mouseleave="onEnd">',
            '    <div class="uni-swiper-wrapper" :style="{ height: \'100%\', overflow: \'hidden\' }">',
            '      <div class="u-swiper-track" :style="trackStyle">',
            '        <div v-for="(img, i) in images" :key="i" class="u-swiper-item">',
            '          <div class="u-list-image-wrap" :style="itemStyle">',
            '            <u-image class="u-swiper-image" :src="img" mode="aspectFill"></u-image>',
            '          </div>',
            '        </div>',
            '      </div>',
            '    </div>',
            '  </uni-swiper>',
            '  <div class="u-swiper-indicator" :style="indicatorStyle">',
            '    <div v-for="(img, i) in images" :key="i"',
            '      :class="[\'u-indicator-item-\' + indicatorMode, { [\'u-indicator-item-\' + indicatorMode + \'-active\']: index === i }]"',
            '      @click="goTo(i)"></div>',
            '  </div>',
            '</div>'
        ].join('\n')
    });

    /* ------------------------------------------------------------------
     | 宫格（uView u-grid / u-grid-item）
     * ------------------------------------------------------------------ */
    Vue.component('u-grid', {
        props: {
            col: { type: [String, Number], default: 3 },
            border: { type: Boolean, default: true }
        },
        provide: function () {
            return { uGrid: { col: this.col, border: this.border } };
        },
        template: '<div class="u-grid" :class="{ \'u-border-top u-border-left\': border }"><slot></slot></div>'
    });

    Vue.component('u-grid-item', {
        props: {
            index: { type: [String, Number], default: 0 },
            col: { type: [String, Number], default: '' },
            bgColor: { type: String, default: '__inherit__' },
            customStyle: { type: Object, default: function () { return {}; } }
        },
        inject: { uGrid: { default: null } },
        computed: {
            gridCol: function () {
                return this.col !== '' ? this.col : (this.uGrid ? this.uGrid.col : 3);
            },
            style: function () {
                var s = { width: (100 / (parseInt(this.gridCol, 10) || 3)) + '%' };
                if (this.bgColor !== '__inherit__') s.background = this.bgColor;
                return Object.assign(s, this.customStyle);
            },
            boxClass: function () {
                return (this.uGrid && this.uGrid.border) ? 'u-border-right u-border-bottom' : '';
            }
        },
        template:
            '<div class="u-grid-item" :style="style" @click="$emit(\'click\')">' +
            '  <div class="u-grid-item-box" :class="boxClass"><slot></slot></div>' +
            '</div>'
    });

    /* ------------------------------------------------------------------
     | 空数据 / 加载更多（参考站 no-list 组件）
     * ------------------------------------------------------------------ */
    Vue.component('no-list', {
        props: {
            propList: { type: Boolean, default: false },
            propStatus: { type: String, default: 'loadmore' },
            propType: { type: String, default: 'icon' },
            propTips: { type: String, default: '暂无数据' },
            iconType: { type: String, default: 'circle' },
            loadText: { type: Object, default: function () { return {}; } }
        },
        computed: {
            loadTextMap: function () {
                return Object.assign({
                    loadmore: '加载更多',
                    loading: '正在加载...',
                    nomore: '没有更多了'
                }, this.loadText || {});
            },
            statusText: function () {
                return this.loadTextMap[this.propStatus] || '';
            }
        },
        template: [
            '<div>',
            '  <div v-if="propList" class="no_list">',
            '    <div class="u-m-b-40">',
            '      <u-image v-if="propType === \'img\'" class="img" src="/static/img/no_img.png" mode="widthFix" width="400"></u-image>',
            '      <u-icon v-else name="wushuju" custom-prefix="custom-icon" size="160" color="#999"></u-icon>',
            '    </div>',
            '    <div>{{ propTips }}</div>',
            '  </div>',
            '  <div v-else class="load_more">',
            '    <div class="u-load-more-wrap">',
            '      <div class="u-load-more-inner">',
            '        <div v-if="propStatus === \'loading\'" class="u-loadmore-icon-wrap">',
            '          <div class="u-loadmore-icon"><span class="u-loading-circle"></span></div>',
            '        </div>',
            '        <div class="u-line-1" :style="{ color: \'#606266\', fontSize: \'14px\' }">{{ statusText }}</div>',
            '      </div>',
            '    </div>',
            '  </div>',
            '</div>'
        ].join('\n')
    });

})(window);
