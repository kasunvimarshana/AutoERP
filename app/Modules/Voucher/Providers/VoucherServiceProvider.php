<?php

declare(strict_types=1);

namespace Modules\Voucher\Providers;

use Illuminate\Support\ServiceProvider;

final class VoucherServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadViewsFrom(resource_path('views/modules/voucher'), 'voucher');
    }
}
