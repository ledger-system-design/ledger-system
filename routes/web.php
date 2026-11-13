<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 前端路由
|--------------------------------------------------------------------------
| 单页应用（uni-app 风格 hash 路由）同时挂载在 / 与 /h5/ 上，
| 这样既可以用 http://localhost:9193/ 访问，
| 也可以用 http://localhost:9193/h5/#/pages/public/login 访问。
*/

$spa = function () {
    return response()
        ->view('app')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
};

Route::get('/', $spa);
Route::get('/h5', $spa);
Route::get('/h5/', $spa);
Route::get('/h5/index.html', $spa);

// 兼容 uni-app 的 hash 路由，任何未知路径都回落到单页应用
Route::fallback(function () use ($spa) {
    return $spa();
})->where('any', '.*');
