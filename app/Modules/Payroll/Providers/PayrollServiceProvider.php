<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payroll\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Payroll\Services\PayrollAuthorizationService;

final class PayrollServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('payroll', PayrollAuthorizationService::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
