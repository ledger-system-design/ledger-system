<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BalanceController;
use App\Http\Controllers\Admin\CaptchaController;
use App\Http\Controllers\Admin\CashController;
use App\Http\Controllers\Admin\CertController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\RecordController;
use App\Http\Controllers\Admin\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 账本管理系统 · 后台路由
|--------------------------------------------------------------------------
|   登录地址：http://localhost:9193/admin_manage_6789/login
|   为降低被扫描/暴力破解的风险，后台入口不使用常见的 /admin。
|
|   权限分级（客户要求）：
|     · 普通管理员：只能进入「采购清单 / 采购记录」给用户安排任务
|     · 超级管理员：全部功能，另可禁用用户、删除单个/全部用户数据
*/

Route::prefix('admin_manage_6789')->name('admin.')->group(function () {
    /* 登录 / 登出 / 验证码（无需鉴权） */
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.post');
    Route::get('captcha', [CaptchaController::class, 'image'])->name('captcha');
    Route::get('logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('logout', [AuthController::class, 'logout']);

    Route::middleware(\App\Http\Middleware\AdminAuth::class)->group(function () {
        /* ---------- 所有管理员都可用：概览 + 派单流程 + 自己的账号 ---------- */
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::match(['get', 'post'], 'profile', [AuthController::class, 'profile'])->name('profile');

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
        Route::post('orders/batch', [OrderController::class, 'batch'])->name('orders.batch');
        Route::get('orders/sellers', [OrderController::class, 'sellers'])->name('orders.sellers');
        Route::get('orders/candidates', [OrderController::class, 'candidates'])->name('orders.candidates');
        Route::get('orders/sellerpay/{id}', [OrderController::class, 'sellerPay'])->name('orders.sellerpay')->whereNumber('id');
        Route::get('orders/{id}/edit', [OrderController::class, 'edit'])->name('orders.edit')->whereNumber('id');
        Route::post('orders/{id}', [OrderController::class, 'update'])->name('orders.update')->whereNumber('id');
        Route::post('orders/{id}/delete', [OrderController::class, 'destroy'])->name('orders.destroy')->whereNumber('id');

        Route::get('records', [RecordController::class, 'index'])->name('records.index');
        Route::get('records/member/{id}', [RecordController::class, 'member'])->name('records.member')->whereNumber('id');
        Route::get('records/{id}/detail', [RecordController::class, 'detail'])->name('records.detail')->whereNumber('id');
        Route::post('records/{id}/arrive', [RecordController::class, 'arrive'])->name('records.arrive')->whereNumber('id');
        Route::post('records/{id}/revert', [RecordController::class, 'revert'])->name('records.revert')->whereNumber('id');
        Route::post('records/{id}', [RecordController::class, 'update'])->name('records.update')->whereNumber('id');

        /* ---------- 仅超级管理员 ---------- */
        Route::middleware('super')->group(function () {
            /* 用户数据 */
            Route::get('members', [MemberController::class, 'index'])->name('members.index');
            Route::get('members/create', [MemberController::class, 'create'])->name('members.create');
            Route::post('members', [MemberController::class, 'store'])->name('members.store');
            Route::post('members/purge', [MemberController::class, 'purge'])->name('members.purge');
            Route::get('members/{id}', [MemberController::class, 'show'])->name('members.show')->whereNumber('id');
            Route::get('members/{id}/edit', [MemberController::class, 'edit'])->name('members.edit')->whereNumber('id');
            Route::post('members/{id}', [MemberController::class, 'update'])->name('members.update')->whereNumber('id');
            Route::post('members/{id}/toggle', [MemberController::class, 'toggle'])->name('members.toggle')->whereNumber('id');
            Route::post('members/{id}/adjust', [MemberController::class, 'adjust'])->name('members.adjust')->whereNumber('id');
            Route::post('members/{id}/delete', [MemberController::class, 'destroy'])->name('members.destroy')->whereNumber('id');

            /* 资金明细 */
            Route::get('balances', [BalanceController::class, 'index'])->name('balances.index');
            Route::post('balances/{id}/delete', [BalanceController::class, 'destroy'])->name('balances.destroy')->whereNumber('id');

            /* 提现审核 */
            Route::get('cashes', [CashController::class, 'index'])->name('cashes.index');
            Route::post('cashes/{id}/approve', [CashController::class, 'approve'])->name('cashes.approve')->whereNumber('id');
            Route::post('cashes/{id}/reject', [CashController::class, 'reject'])->name('cashes.reject')->whereNumber('id');

            /* 收款方式 / 收货地址 */
            Route::get('certs', [CertController::class, 'index'])->name('certs.index');
            Route::get('certs/{id}/edit', [CertController::class, 'edit'])->name('certs.edit')->whereNumber('id');
            Route::post('certs/{id}', [CertController::class, 'update'])->name('certs.update')->whereNumber('id');
            Route::get('addresses', [CertController::class, 'addresses'])->name('addresses.index');
            Route::post('addresses/{id}/delete', [CertController::class, 'deleteAddress'])->name('addresses.destroy')->whereNumber('id');

            /* 系统设置 / 运营报表 */
            Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
            Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
            Route::get('report', [SettingController::class, 'report'])->name('report');

            /* 推荐关系树 / 操作日志 */
            Route::get('team', [LogController::class, 'team'])->name('team');
            Route::get('logs', [LogController::class, 'index'])->name('logs.index');

            /* 管理员账号 */
            Route::get('admins', [AdminUserController::class, 'index'])->name('admins.index');
            Route::get('admins/create', [AdminUserController::class, 'create'])->name('admins.create');
            Route::post('admins', [AdminUserController::class, 'store'])->name('admins.store');
            Route::get('admins/{id}/edit', [AdminUserController::class, 'edit'])->name('admins.edit')->whereNumber('id');
            Route::post('admins/{id}', [AdminUserController::class, 'update'])->name('admins.update')->whereNumber('id');
            Route::post('admins/{id}/delete', [AdminUserController::class, 'destroy'])->name('admins.destroy')->whereNumber('id');
        });
    });
});
