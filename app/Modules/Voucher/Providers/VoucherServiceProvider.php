<?php

declare(strict_types=1);

namespace Modules\Voucher\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Voucher\Constants\VoucherPermission;

final class VoucherServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('voucher', VoucherPermission::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadViewsFrom(resource_path('views/modules/voucher'), 'voucher');
    }
}
