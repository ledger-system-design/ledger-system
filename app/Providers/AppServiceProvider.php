<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 站点根目录通过 .htaccess 重写指向 public/，
        // 因此强制生成不带 /public 前缀的地址。
        URL::forceRootUrl(rtrim((string) config('app.url'), '/'));
    }
}
