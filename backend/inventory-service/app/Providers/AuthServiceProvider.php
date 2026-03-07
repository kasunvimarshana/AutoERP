<?php

namespace App\Providers;

use App\Models\Inventory;
use App\Models\Warehouse;
use App\Policies\InventoryPolicy;
use App\Policies\WarehousePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Inventory::class => InventoryPolicy::class,
        Warehouse::class => WarehousePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
