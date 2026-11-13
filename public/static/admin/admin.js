/* ==========================================================================
   账本管理系统 · 后台脚本
   ========================================================================== */

(function () {
    'use strict';

    var BASE = window.ADMIN_BASE || '';

    /* ---------- 弹窗 ---------- */
    window.openModal = function (id) {
        var el = document.getElementById(id);
        if (el) el.hidden = false;
    };

    window.closeModal = function (id) {
        var el = document.getElementById(id);
        if (el) el.hidden = true;
    };

    document.addEventListener('click', function (e) {
        var t = e.target;
        if (t && t.classList && t.classList.contains('modal-mask')) t.hidden = true;
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-mask').forEach(function (m) { m.hidden = true; });
        }
    });

    /* ---------- 二次确认 ---------- */
    document.addEventListener('submit', function (e) {
        var msg = e.target.getAttribute && e.target.getAttribute('data-confirm');
        if (msg && !window.confirm(msg)) e.preventDefault();
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('[data-confirm]') : null;
        if (btn && btn.tagName !== 'FORM' && !window.confirm(btn.getAttribute('data-confirm'))) {
            e.preventDefault();
            e.stopPropagation();
        }
    });

    /* ---------- 图片放大 ---------- */
    window.zoomImage = function (src) {
        var mask = document.getElementById('imgZoomModal');
        if (!mask) {
            mask = document.createElement('div');
            mask.id = 'imgZoomModal';
            mask.className = 'modal-mask';
            mask.innerHTML = '<div style="max-width:92vw;max-height:92vh;"><img id="imgZoomTarget" style="max-width:92vw;max-height:92vh;border-radius:8px;background:#fff;"></div>';
            mask.addEventListener('click', function () { mask.hidden = true; });
            document.body.appendChild(mask);
        }
        document.getElementById('imgZoomTarget').src = src;
        mask.hidden = false;
    };

    /* ------------------------------------------------------------------
     | 图片上传：点击缩略图或「上传」按钮选择本地文件
     |  结构：<div class="img-field"><input type="text" name="..."><button>上传</button><img></div>
     * ------------------------------------------------------------------ */
    var uploading = 0;

    function setBusy(busy) {
        document.body.style.cursor = busy ? 'progress' : '';
    }

    window.uploadImageTo = function (wrap, file) {
        if (!file) return;

        var input = wrap.querySelector('.img-field__input');
        var preview = wrap.querySelector('.img-field__preview');
        var tip = wrap.querySelector('.img-field__tip');

        var fd = new FormData();
        fd.append('data', file);

        uploading++;
        setBusy(true);
        if (tip) tip.textContent = '上传中…';

        fetch(window.UPLOAD_URL || '/app/File/upload', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': window.CSRF_TOKEN || '' },
            body: fd
        }).then(function (r) {
            return r.json();
        }).then(function (json) {
            if (json.code !== 0) throw json;
            var url = json.data.url;
            if (input) {
                input.value = url;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (preview) {
                preview.src = url;
                preview.style.display = '';
            }
            if (tip) tip.textContent = '已上传';
            setTimeout(function () { if (tip) tip.textContent = '点击图片可更换'; }, 1500);
        }).catch(function (err) {
            if (tip) tip.textContent = '上传失败';
            window.alert((err && err.msg) || '图片上传失败，请重试');
        }).then(function () {
            uploading--;
            if (uploading <= 0) { uploading = 0; setBusy(false); }
        });
    };

    window.pickImage = function (trigger) {
        var wrap = trigger.closest('.img-field');
        if (!wrap) return;
        var fi = document.createElement('input');
        fi.type = 'file';
        fi.accept = 'image/png,image/jpeg,image/gif,image/webp';
        fi.style.display = 'none';
        fi.addEventListener('change', function () {
            if (fi.files && fi.files[0]) window.uploadImageTo(wrap, fi.files[0]);
            document.body.removeChild(fi);
        });
        document.body.appendChild(fi);
        fi.click();
    };

    // 支持直接拖拽图片到上传区域
    document.addEventListener('dragover', function (e) {
        var w = e.target.closest && e.target.closest('.img-field');
        if (w) { e.preventDefault(); w.classList.add('is-drag'); }
    });
    document.addEventListener('dragleave', function (e) {
        var w = e.target.closest && e.target.closest('.img-field');
        if (w) w.classList.remove('is-drag');
    });
    document.addEventListener('drop', function (e) {
        var w = e.target.closest && e.target.closest('.img-field');
        if (!w) return;
        e.preventDefault();
        w.classList.remove('is-drag');
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
            window.uploadImageTo(w, e.dataTransfer.files[0]);
        }
    });

    // 输入框手动改了地址时同步预览
    document.addEventListener('input', function (e) {
        var input = e.target;
        if (!input.classList || !input.classList.contains('img-field__input')) return;
        var wrap = input.closest('.img-field');
        var preview = wrap && wrap.querySelector('.img-field__preview');
        if (preview) {
            preview.src = input.value || '';
            preview.style.display = input.value ? '' : 'none';
        }
    });

    /* ==================================================================
     | 友好提示：轻提示 + 表单必填校验
     | ------------------------------------------------------------------
     | 目标：任何「没填 / 填错」都在当前页面直接告诉用户，
     | 不再让用户提交后跳到 500 或空白错误页。
     * ================================================================== */

    var toastTimer = null;

    window.adminToast = function (text, type) {
        var el = document.getElementById('adminToast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'adminToast';
            el.className = 'admin-toast';
            document.body.appendChild(el);
        }
        el.textContent = text;
        el.className = 'admin-toast is-show' + (type === 'error' ? ' is-error' : '');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { el.className = 'admin-toast'; }, 3200);
    };

    /** 取字段的友好名称：优先读 label 文本，其次 placeholder，最后用字段名 */
    function fieldLabel(el) {
        var box = el.closest('.field') || el.closest('.img-field') || el.parentElement;
        if (box) {
            var lab = box.querySelector('label');
            if (lab && lab.textContent) {
                return lab.textContent.replace(/\*/g, '').replace(/\s+/g, ' ').trim();
            }
        }
        if (el.getAttribute('placeholder')) return el.getAttribute('placeholder');
        var n = el.getAttribute('name') || el.id || '';
        return n.replace(/\[\]$/, '');
    }

    function markInvalid(el, message) {
        el.classList.add('is-invalid');
        var box = el.closest('.field') || el.closest('.img-field') || el.parentElement;
        if (box) {
            var old = box.querySelector('.field-error');
            if (old) old.parentNode.removeChild(old);
            var tip = document.createElement('div');
            tip.className = 'field-error';
            tip.textContent = message;
            var anchor = el.closest('.img-field__row') || el;
            anchor.parentNode.insertBefore(tip, anchor.nextSibling);
        }
    }

    function clearInvalid(el) {
        el.classList.remove('is-invalid');
        var box = el.closest('.field') || el.closest('.img-field') || el.parentElement;
        if (box) {
            var old = box.querySelector('.field-error');
            if (old) old.parentNode.removeChild(old);
        }
    }

    /** 提交前的友好校验（捕获阶段执行，先于二次确认） */
    function validateForm(form) {
        var firstBad = null;
        var message = '';

        form.querySelectorAll('input, select, textarea').forEach(function (el) {
            if (el.disabled || el.type === 'hidden') return;
            if (el.closest('[hidden]')) return;
            clearInvalid(el);

            var label = fieldLabel(el);
            var raw = (el.value == null ? '' : String(el.value)).trim();

            if (el.hasAttribute('required') && raw === '') {
                if (!firstBad) { firstBad = el; message = '请填写「' + label + '」，这一项不能留空'; }
                markInvalid(el, '这一项必须填写');
                return;
            }

            if (raw !== '' && (el.type === 'number' || el.type === 'range')) {
                var num = Number(raw);
                if (isNaN(num)) {
                    if (!firstBad) { firstBad = el; message = '「' + label + '」只能填数字'; }
                    markInvalid(el, '请填写数字');
                    return;
                }
                if (el.hasAttribute('min') && num < Number(el.getAttribute('min'))) {
                    if (!firstBad) { firstBad = el; message = '「' + label + '」不能小于 ' + el.getAttribute('min'); }
                    markInvalid(el, '不能小于 ' + el.getAttribute('min'));
                    return;
                }
                if (el.hasAttribute('max') && num > Number(el.getAttribute('max'))) {
                    if (!firstBad) { firstBad = el; message = '「' + label + '」不能大于 ' + el.getAttribute('max'); }
                    markInvalid(el, '不能大于 ' + el.getAttribute('max'));
                    return;
                }
            }

            if (raw !== '' && el.hasAttribute('minlength') && raw.length < Number(el.getAttribute('minlength'))) {
                if (!firstBad) { firstBad = el; message = '「' + label + '」至少需要 ' + el.getAttribute('minlength') + ' 个字符'; }
                markInvalid(el, '至少 ' + el.getAttribute('minlength') + ' 个字符');
            }
        });

        if (firstBad) {
            window.adminToast(message, 'error');
            try {
                firstBad.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstBad.focus({ preventScroll: true });
            } catch (e) { /* 老浏览器忽略 */ }
        }

        return !firstBad;
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || form.tagName !== 'FORM') return;
        if (form.getAttribute('data-skip-check') === '1') return;
        if (!validateForm(form)) {
            e.preventDefault();
            e.stopPropagation();
        }
    }, true);

    // 用户重新输入时清掉红框
    document.addEventListener('input', function (e) {
        if (e.target && e.target.classList && e.target.classList.contains('is-invalid')) clearInvalid(e.target);
    });
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList && e.target.classList.contains('is-invalid')) clearInvalid(e.target);
    });

    /* ---------- 派单：智能推荐任务对象 + 收款码按序号陈列 ---------- */

    var candidateTimer = null;
    var lastCandidates = [];

    function money(n) {
        return '¥' + Number(n || 0).toFixed(2);
    }

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    /** 金额变化：防抖后重新拉推荐 */
    window.onAmountChange = function () {
        clearTimeout(candidateTimer);
        candidateTimer = setTimeout(loadCandidates, 400);
    };

    /** 买方变化：只刷新高亮，不重新排序 */
    window.onBuyerChange = function () {
        renderBuyerList();
    };

    window.loadCandidates = function () {
        var sellerBox = document.getElementById('sellerList');
        var buyerBox = document.getElementById('buyerList');
        var amountEl = document.getElementById('amountInput');
        if (!sellerBox && !buyerBox) return;

        var amount = amountEl ? (amountEl.value || 0) : 0;
        if (sellerBox) sellerBox.innerHTML = '<div class="empty">正在计算推荐…</div>';

        var url = BASE + '/orders/candidates?amount=' + encodeURIComponent(amount) + '&limit=50';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                lastCandidates = res.data || [];
                renderSellerList();
                renderBuyerList();
            })
            .catch(function () {
                if (sellerBox) sellerBox.innerHTML = '<div class="empty">推荐加载失败，可直接用下面的下拉框选择</div>';
                if (buyerBox) buyerBox.innerHTML = '<div class="empty">名单加载失败，可直接用下面的下拉框选择</div>';
            });
    };

    /** 卖方窗口：按差额排序 + 「已为他安排买家」标色 + 名称后显示回款总额 */
    function renderSellerList() {
        var box = document.getElementById('sellerList');
        if (!box) return;
        if (!lastCandidates.length) {
            box.innerHTML = '<div class="empty">还没有可选会员，请先在「用户数据」里新增会员</div>';
            return;
        }

        var sel = document.getElementById('sellerid');
        var current = sel ? String(sel.value) : '';
        var html = '';

        lastCandidates.forEach(function (m, i) {
            var arranged = (m.assigned_today > 0 || m.pending > 0);
            var cls = ['pick-item'];
            if (arranged) cls.push('is-assigned');
            if (String(m.id) === current) cls.push('is-active');

            var amountText = m.has_order
                ? '累计回款 ' + money(m.return_amount) + '（本金 ' + money(m.last_amount) + ' + 收益 ' + money(m.last_profit) + '）'
                : '还没有做过任务';
            var distText = (m.distance === null) ? '' : '差额 ' + money(m.distance);

            html += '<div class="' + cls.join(' ') + '" data-id="' + m.id + '" onclick="pickSeller(this)">';
            html += '<span class="pick-item__idx">' + (i + 1) + '</span>';
            html += '<span class="pick-item__main">';
            html += '<span class="pick-item__name' + (arranged ? ' is-done' : '') + '">' + esc(m.name)
                + '<small>（' + esc(m.username) + '）</small></span>';
            // 客户要求：卖方名称后面直接显示前一天本金 + 收益的总额
            html += '<span class="pick-item__sub">' + amountText + (distText ? ' · ' + distText : '') + '</span>';
            html += '</span>';
            html += '<span class="pick-item__tags">';
            html += '<i class="tag tag--rate">收益率 ' + (m.rate * 100).toFixed(2) + '%</i>';
            if (m.iscert) html += '<i class="tag tag--cert">已认证</i>';
            if (arranged) {
                html += '<i class="tag tag--assigned">已为他安排买家' + (m.pending ? '（未完成 ' + m.pending + '）' : '') + '</i>';
            }
            html += '</span></div>';
        });

        box.innerHTML = html;
    }

    /** 买方窗口：已安排过任务的用橙色加粗标出来 */
    function renderBuyerList() {
        var box = document.getElementById('buyerList');
        if (!box) return;
        if (!lastCandidates.length) {
            box.innerHTML = '<div class="empty">还没有可选会员，请先在「用户数据」里新增会员</div>';
            return;
        }

        var sel = document.getElementById('memberid');
        var current = sel ? String(sel.value) : '';

        // 已安排任务的排前面，其次按 ID
        var rows = lastCandidates.slice().sort(function (a, b) {
            var av = (a.buyer_pending > 0 || a.buyer_today > 0) ? 1 : 0;
            var bv = (b.buyer_pending > 0 || b.buyer_today > 0) ? 1 : 0;
            if (av !== bv) return bv - av;
            return a.id - b.id;
        });

        var html = '';
        rows.forEach(function (m, i) {
            var arranged = (m.buyer_today > 0 || m.buyer_pending > 0);
            var cls = ['pick-item'];
            if (arranged) cls.push('is-assigned');
            if (String(m.id) === current) cls.push('is-active');

            html += '<div class="' + cls.join(' ') + '" data-id="' + m.id + '" onclick="pickBuyer(this)">';
            html += '<span class="pick-item__idx">' + (i + 1) + '</span>';
            html += '<span class="pick-item__main">';
            html += '<span class="pick-item__name' + (arranged ? ' is-done' : '') + '">' + esc(m.name)
                + '<small>（' + esc(m.username) + '）</small></span>';
            html += '<span class="pick-item__sub">' + (m.has_order ? '累计任务额 ' + money(m.last_amount) : '还没有做过任务') + '</span>';
            html += '</span>';
            html += '<span class="pick-item__tags">';
            if (m.iscert) html += '<i class="tag tag--cert">已认证</i>';
            if (arranged) {
                html += '<i class="tag tag--assigned">' + (m.buyer_pending
                    ? '已安排任务（未完成 ' + m.buyer_pending + '）'
                    : '今日已安排任务') + '</i>';
            }
            html += '</span></div>';
        });
        box.innerHTML = html;
    }

    /** 点推荐条目 => 选中卖方 */
    window.pickSeller = function (el) {
        var id = el.getAttribute('data-id');
        var sel = document.getElementById('sellerid');
        if (!sel) return;

        ensureOption(sel, id, el);
        sel.value = id;

        var list = document.getElementById('sellerList');
        if (list) list.querySelectorAll('.pick-item').forEach(function (n) { n.classList.remove('is-active'); });
        el.classList.add('is-active');

        loadSellerPays(id);
    };

    /** 点买方条目 => 选中买方 */
    window.pickBuyer = function (el) {
        var id = el.getAttribute('data-id');
        var sel = document.getElementById('memberid');
        if (!sel) return;

        ensureOption(sel, id, el);
        sel.value = id;

        var list = document.getElementById('buyerList');
        if (list) list.querySelectorAll('.pick-item').forEach(function (n) { n.classList.remove('is-active'); });
        el.classList.add('is-active');
    };

    function ensureOption(sel, id, el) {
        for (var i = 0; i < sel.options.length; i++) {
            if (String(sel.options[i].value) === String(id)) return;
        }
        var name = el.querySelector('.pick-item__name');
        var opt = document.createElement('option');
        opt.value = id;
        opt.textContent = name ? name.textContent : ('#' + id);
        sel.appendChild(opt);
    }

    window.onSellerChange = function () {
        var sel = document.getElementById('sellerid');
        loadSellerPays(sel ? sel.value : '');
        renderSellerList();
    };

    /** 展示任务对象的收款方式（序号 1 为默认优选） */
    window.loadSellerPays = function (id) {
        var box = document.getElementById('sellerPayBox');
        if (!box) return;

        if (!id) {
            box.innerHTML = '<div class="empty">选择任务对象后显示其收款码</div>';
            return;
        }

        box.innerHTML = '<div class="empty">加载中…</div>';

        fetch(BASE + '/orders/sellerpay/' + encodeURIComponent(id), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function (r) { return r.json(); }).then(function (res) {
            var d = res.data || {};
            var pays = d.pays || [];
            if (!pays.length) {
                box.innerHTML = '<div class="empty">该会员还没有配置收款方式，请到「收款方式」里补充</div>';
                return;
            }
            var html = '<div class="pay-box__title">' + esc(d.name) + '（' + esc(d.username) + '）'
                + (d.iscert ? ' <i class="tag tag--cert">已认证</i>' : ' <i class="tag">未认证</i>') + '</div>';
            html += '<div class="pay-box__grid">';
            pays.forEach(function (p) {
                var isDefault = p.sort === 1;
                html += '<div class="pay-card' + (isDefault ? ' is-default' : '') + '">';
                html += '<div class="pay-card__hd"><b>序号 ' + p.sort + '</b>';
                if (isDefault) html += '<i class="tag tag--default">默认优选平台</i>';
                html += '<span class="pay-card__name">' + esc(p.name) + '</span></div>';
                if (p.image) {
                    html += '<img class="pay-card__img" src="' + esc(p.image) + '" onclick="zoomImage(this.src)">';
                }
                if (p.account) html += '<div class="pay-card__line">账号：' + esc(p.account) + '</div>';
                if (p.cardno) html += '<div class="pay-card__line">卡号：' + esc(p.cardno) + '</div>';
                if (p.carduser) html += '<div class="pay-card__line">收款人：' + esc(p.carduser) + '</div>';
                if (p.cardbank) html += '<div class="pay-card__line">银行：' + esc(p.cardbank) + '</div>';
                if (p.url && !p.image) html += '<div class="pay-card__line">链接：' + esc(p.url) + '</div>';
                html += '</div>';
            });
            html += '</div>';
            box.innerHTML = html;
        }).catch(function () {
            box.innerHTML = '<div class="empty">收款码加载失败</div>';
        });
    };

    /* ---------- 收款方式排序（上移 / 下移 / 设为默认） ---------- */

    function renumberPay() {
        var wraps = document.querySelectorAll('[data-pay-block]');
        wraps.forEach(function (w, i) {
            var badge = w.querySelector('[data-pay-no]');
            if (badge) badge.textContent = '序号 ' + (i + 1);
            w.classList.toggle('is-default', i === 0);
            var def = w.querySelector('[data-pay-default]');
            if (def) def.hidden = i !== 0;
        });
        saveOrder();
    }

    /** 把排序写回每个块的隐藏字段 */
    function saveOrder() {
        var wraps = document.querySelectorAll('[data-pay-block]');
        wraps.forEach(function (w, i) {
            var input = w.querySelector('input[data-pay-sort]');
            if (input) input.value = i + 1;
        });
    }

    window.payMove = function (btn, dir) {
        var block = btn.closest('[data-pay-block]');
        if (!block) return;
        var parent = block.parentElement;
        var blocks = Array.prototype.slice.call(parent.querySelectorAll('[data-pay-block]'));
        var i = blocks.indexOf(block);
        var j = i + dir;
        if (j < 0 || j >= blocks.length) return;

        if (dir < 0) parent.insertBefore(block, blocks[j]);
        else parent.insertBefore(blocks[j], block);
        renumberPay();
    };

    window.paySetDefault = function (btn) {
        var block = btn.closest('[data-pay-block]');
        if (!block) return;
        var parent = block.parentElement;
        parent.insertBefore(block, parent.querySelector('[data-pay-block]'));
        renumberPay();
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (document.querySelector('[data-pay-block]')) renumberPay();
    });

    /* ---------- 发布任务的收款人联动 ---------- */
    window.loadSellers = function (buyerId, selectId, selected) {
        var sel = document.getElementById(selectId);
        if (!sel) return;
        sel.innerHTML = '<option value="">加载中…</option>';
        fetch(BASE + '/orders/sellers?buyerid=' + encodeURIComponent(buyerId || 0), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (res) {
            var html = '<option value="">请选择收款人</option>';
            (res.data || []).forEach(function (item) {
                html += '<option value="' + item.id + '"' + (String(selected) === String(item.id) ? ' selected' : '') + '>' + item.text + '</option>';
            });
            sel.innerHTML = html;
        }).catch(function () {
            sel.innerHTML = '<option value="">加载失败</option>';
        });
    };

    /* ---------- 采购记录详情弹窗 ---------- */
    window.showRecord = function (id) {
        var body = document.getElementById('recordModalBody');
        if (!body) return;
        body.innerHTML = '<div class="empty">加载中…</div>';
        window.openModal('recordModal');

        fetch(BASE + '/records/' + id + '/detail', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (res) {
            var d = res.data || {};
            var o = d.order || {};
            var preview = d.preview || { rows: [], total: 0, platform: 0 };
            var paid = d.paid || [];

            var html = '';
            html += '<div class="kv">';
            html += '<dt>订单编号</dt><dd class="mono">' + (o.orderno || '') + '</dd>';
            html += '<dt>商品名称</dt><dd>' + (o.name || '') + '</dd>';
            html += '<dt>采购金额</dt><dd class="num" style="color:#c82f23;">¥' + Number(o.amount || 0).toFixed(2) + '</dd>';
            html += '<dt>付款人 / 买家</dt><dd>' + (d.buyer ? d.buyer.name + '（' + d.buyer.username + '）' : '-') + '</dd>';
            html += '<dt>收款人 / 卖家</dt><dd>' + (d.seller ? d.seller.name + '（' + d.seller.username + '）' : '-') + '</dd>';
            html += '<dt>下单时间</dt><dd>' + (o.addtime || '-') + '</dd>';
            html += '<dt>支付时间</dt><dd>' + (o.paytime || '-') + '</dd>';
            html += '<dt>到账时间</dt><dd>' + (o.arrived_at || '-') + '</dd>';
            html += '<dt>当前状态</dt><dd>' + (o.status_text || '') + '</dd>';
            html += '</dl>';

            html += '<h4 style="margin:18px 0 8px;">支付凭证</h4>';
            if (o.payphoto) {
                html += '<img class="proof-large" src="' + o.payphoto + '" onclick="zoomImage(this.src)">';
            } else {
                html += '<div class="muted">用户尚未上传支付凭证</div>';
            }

            html += '<h4 style="margin:18px 0 8px;">到账后各层级奖励</h4>';
            html += '<table class="data"><thead><tr><th>层级</th><th>会员</th><th>比例</th><th class="text-right">金额</th></tr></thead><tbody>';
            (preview.rows || []).forEach(function (row) {
                html += '<tr><td>' + row.level + '</td><td>' + row.name + '（' + row.username + '）</td><td>' + (row.rate * 100).toFixed(2) + '%</td><td class="text-right num">¥' + Number(row.amount).toFixed(2) + '</td></tr>';
            });
            html += '<tr><td>平台</td><td>-</td><td>0.50%</td><td class="text-right num">¥' + Number(preview.platform || 0).toFixed(2) + '</td></tr>';
            html += '<tr><td colspan="3" class="text-right"><strong>会员奖励合计</strong></td><td class="text-right num"><strong>¥' + Number(preview.total || 0).toFixed(2) + '</strong></td></tr>';
            html += '</tbody></table>';

            if (paid.length) {
                html += '<h4 style="margin:18px 0 8px;">已发放记录</h4>';
                html += '<table class="data"><thead><tr><th>会员</th><th>类型</th><th class="text-right">金额</th><th>时间</th></tr></thead><tbody>';
                paid.forEach(function (p) {
                    html += '<tr><td>' + p.name + '（' + p.username + '）</td><td>' + (p.type === 1 ? '收入' : '支出') + ' #' + p.typeid + '</td><td class="text-right num">' + (p.type === 1 ? '+' : '-') + Number(p.amount).toFixed(2) + '</td><td>' + (p.addtime || '') + '</td></tr>';
                });
                html += '</tbody></table>';
            }

            body.innerHTML = html;
        }).catch(function () {
            body.innerHTML = '<div class="empty">加载失败</div>';
        });
    };
})();
