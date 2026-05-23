<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Voucher\Application\Repositories\RecurringVoucherRepositoryInterface;
use Modules\Voucher\Application\Repositories\VoucherRepositoryInterface;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories\EloquentRecurringVoucherRepository;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories\EloquentVoucherRepository;

class VoucherServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/voucher.php', 'voucher');

        foreach ([
            RecurringVoucherRepositoryInterface::class => EloquentRecurringVoucherRepository::class,
            VoucherRepositoryInterface::class => EloquentVoucherRepository::class,
        ] as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
