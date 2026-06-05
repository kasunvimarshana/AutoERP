<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class VoucherServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/voucher.php', 'voucher');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Eloquent/Migrations');
    }
}
