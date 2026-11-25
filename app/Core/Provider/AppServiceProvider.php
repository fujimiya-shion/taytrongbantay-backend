<?php

namespace App\Core\Provider;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * Không đăng ký provider của module ở đây: nwidart tự nạp provider khai báo
     * trong app/Module/<Module>/module.json, theo trạng thái bật/tắt ở modules_statuses.json.
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
        //
    }
}
