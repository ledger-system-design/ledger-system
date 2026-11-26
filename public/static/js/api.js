/* ==========================================================================
   接口封装 —— 与参考站保持一致的调用约定
   --------------------------------------------------------------------------
   接口地址：/app/模块/方法
   请求方式：POST（application/json）
   鉴权方式：Authorization: Bearer <access_token>
   响应格式：{ code: 0, msg: '成功', data: ... }
   code: 0 成功 / 4000 未登录 / 其它为业务错误
   ========================================================================== */

(function (window) {
    'use strict';

    var HOST = '/app/';

    var TOKEN_KEY = 'Authorization';
    var TITLE_KEY = 'shoptitle';
    var LOGO_KEY = 'shoplogo';

    var store = {
        get: function (key) {
            try {
                return window.localStorage.getItem(key);
            } catch (e) {
                return null;
            }
        },
        set: function (key, value) {
            try {
                window.localStorage.setItem(key, value);
            } catch (e) { /* ignore */ }
        },
        remove: function (key) {
            try {
                window.localStorage.removeItem(key);
            } catch (e) { /* ignore */ }
        },
        clear: function () {
            try {
                window.localStorage.clear();
            } catch (e) { /* ignore */ }
        }
    };

    /* ---------------- 轻提示 / 加载 ----------------
       与参考站（uni-app H5 的 uni-toast 组件）结构保持一致：
         <uni-toast>
           <div class="uni-sample-toast"><p class="uni-simple-toast__text"> 开发中...... </p></div>
         </uni-toast>
       加载中：
         <uni-toast>
           <div class="uni-toast"><i class="uni-icon_toast uni-loading"></i>
             <p class="uni-toast__content"> 加载中 </p></div>
         </uni-toast>
       样式全部来自 uni-framework.css（与参考站同源）。
     */

    var toastTimer = null;
    var toastNode = null;
    var loadingCount = 0;

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function toastRoot() {
        if (toastNode && toastNode.parentNode) {
            return toastNode;
        }
        toastNode = document.createElement('uni-toast');
        document.body.appendChild(toastNode);
        return toastNode;
    }

    function destroyToast() {
        if (toastNode && toastNode.parentNode) {
            toastNode.parentNode.removeChild(toastNode);
        }
        toastNode = null;
    }

    function msg(title, duration) {
        var el = toastRoot();
        el.innerHTML =
            '<div class="uni-sample-toast">' +
            '<p class="uni-simple-toast__text"> ' + escapeHtml(title || '') + ' </p>' +
            '</div>';
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () {
            if (loadingCount === 0) {
                destroyToast();
            }
        }, duration || 1500);
    }

    var loadingCount = 0;

    function showLoading(title) {
        loadingCount++;
        clearTimeout(toastTimer);
        var el = toastRoot();
        el.innerHTML =
            '<div class="uni-toast">' +
            '<i class="uni-icon_toast uni-loading"></i>' +
            '<p class="uni-toast__content"> ' + escapeHtml(title || '加载中') + ' </p>' +
            '</div>';
    }

    function hideLoading() {
        loadingCount = Math.max(0, loadingCount - 1);
        if (loadingCount === 0) {
            destroyToast();
        }
    }

    function confirmDialog(options) {
        options = options || {};
        return new Promise(function (resolve, reject) {
            var mask = document.createElement('div');
            mask.className = 'uni-mask';
            mask.style.zIndex = 10010;
            mask.innerHTML =
                '<div style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:76%;max-width:340px;' +
                'background:#fff;border-radius:12px;overflow:hidden;text-align:center;">' +
                '<div style="padding:22px 18px;font-size:15px;color:#333;line-height:1.6;">' +
                (options.content || '是否确认？') +
                '</div>' +
                '<div style="display:flex;border-top:1px solid #f0f0f0;">' +
                '<div data-act="cancel" style="flex:1;padding:13px 0;color:#909399;font-size:15px;">取消</div>' +
                '<div data-act="ok" style="flex:1;padding:13px 0;color:#0c9cf5;font-size:15px;border-left:1px solid #f0f0f0;">确定</div>' +
                '</div></div>';
            document.body.appendChild(mask);
            mask.addEventListener('click', function (e) {
                var act = e.target.getAttribute && e.target.getAttribute('data-act');
                if (act === 'ok') {
                    document.body.removeChild(mask);
                    resolve(true);
                } else if (act === 'cancel') {
                    document.body.removeChild(mask);
                    reject(false);
                }
            });
        });
    }

    /* ---------------- 请求 ---------------- */

    function request(options) {
        options = options || {};
        var url = options.url || '';
        var data = options.data || {};
        var method = options.method || 'POST';
        var showLoad = options.hideLoading === false ? false : (options.showLoading === true);

        if (showLoad) {
            showLoading(options.loadingText || '请求中...');
        }

        var headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };

        var token = store.get(TOKEN_KEY);
        if (token) {
            headers['Authorization'] = 'Bearer ' + token;
        }

        var payload = Object.assign({ skey: token || '' }, data);

        var full = /^https?:/i.test(url) ? url : HOST + String(url).replace(/^\//, '');
        try {
            full = new URL(full, window.location.href).href;
        } catch (e) { /* 保持原样 */ }

        return fetch(full, {
            method: method,
            headers: headers,
            body: method === 'GET' ? undefined : JSON.stringify(payload)
        }).then(function (res) {
            return res.json().catch(function () {
                throw { code: -1, msg: '服务器返回格式异常' };
            });
        }).then(function (json) {
            if (showLoad) {
                hideLoading();
            }
            if (json.code === 0) {
                return json.data;
            }
            if (json.code === 4000) {
                store.remove(TOKEN_KEY);
                msg('您还未登录，请登陆后再操作');
                setTimeout(function () {
                    window.location.hash = '#/pages/public/login';
                }, 600);
                throw json;
            }
            msg(json.msg || '操作失败');
            throw json;
        }).catch(function (err) {
            if (showLoad) {
                hideLoading();
            }
            if (err && err.code !== undefined) {
                throw err;
            }
            msg('网络异常，请稍后重试');
            throw { code: -1, msg: '网络异常' };
        });
    }

    /* ---------------- 图片上传 ---------------- */

    function uploadImage(count) {
        return new Promise(function (resolve, reject) {
            var input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            if (count > 1) {
                input.multiple = true;
            }
            input.style.display = 'none';
            document.body.appendChild(input);

            input.addEventListener('change', function () {
                var files = Array.prototype.slice.call(input.files || []);
                if (!files.length) {
                    document.body.removeChild(input);
                    reject(false);
                    return;
                }

                showLoading('上传中...');

                var tasks = files.map(function (file) {
                    var fd = new FormData();
                    fd.append('data', file);
                    var token = store.get(TOKEN_KEY);
                    return fetch(HOST + 'File/upload', {
                        method: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + (token || '')
                        },
                        body: fd
                    }).then(function (r) {
                        return r.json();
                    }).then(function (json) {
                        if (json.code === 0) {
                            return json.data.url;
                        }
                        throw json;
                    });
                });

                Promise.all(tasks).then(function (urls) {
                    hideLoading();
                    document.body.removeChild(input);
                    resolve(urls);
                }).catch(function (err) {
                    hideLoading();
                    document.body.removeChild(input);
                    msg((err && err.msg) || '上传失败');
                    reject(err);
                });
            });

            input.click();
        });
    }

    /* ---------------- 其它工具 ---------------- */

    function copyText(text) {
        text = String(text == null ? '' : text);
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                msg('复制成功');
            }).catch(function () {
                fallbackCopy(text);
            });
        } else {
            fallbackCopy(text);
        }
    }

    function fallbackCopy(text) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            msg('复制成功');
        } catch (e) {
            msg('复制失败，请手动复制');
        }
        document.body.removeChild(ta);
    }

    function isMobile(value) {
        return /^1[3-9]\d{9}$/.test(String(value || ''));
    }

    function formatMoney(value) {
        var n = parseFloat(value || 0);
        if (isNaN(n)) {
            return '0.00';
        }
        return n.toFixed(2);
    }

    window.$app = {
        HOST: HOST,
        store: store,
        request: request,
        uploadImage: uploadImage,
        msg: msg,
        showLoading: showLoading,
        hideLoading: hideLoading,
        confirm: confirmDialog,
        copyText: copyText,
        isMobile: isMobile,
        formatMoney: formatMoney
    };
})(window);
