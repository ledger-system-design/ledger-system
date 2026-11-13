<?php

use App\Http\Controllers\Api\RouteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API 路由（与参考站保持一致的 /app/模块/方法 结构）
|--------------------------------------------------------------------------
|   POST /app/Login/login
|   POST /app/Login/register
|   POST /app/index/setting
|   POST /app/Index/indexdata
|   POST /app/Member/usercenter | balance | cash | shopincome | profile
|                              | order1 | cert | children | address
|   POST /app/Tool/pcdjson
|   POST /app/account/smscode
|   POST /app/File/upload
*/

Route::prefix('app')->group(function () {
    Route::any('{module}/{action}', [RouteController::class, 'dispatch'])
        ->where('module', '[A-Za-z]+')
        ->where('action', '[A-Za-z0-9_]+');

    Route::any('{module}', [RouteController::class, 'dispatch'])
        ->where('module', '[A-Za-z]+')
        ->defaults('action', 'index');
});
