<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        Paginator::useBootstrap();
    }

    /**
     * Register any application services.
     * 
     * 🟢 ربط مسار public_path بمجلد public_html مباشرة لاستضافة Hostinger
     *
     * @return void
     */
    public function register()
    {
        // إذا كان مجلد public_html موجوداً، اجعل لارافيل تعتمد عليه كمجلد جذر للملفات العامة
        if (file_exists(base_path('public_html'))) {
            $this->app->bind('path.public', function() {
                return base_path('public_html');
            });
        }
    }
}