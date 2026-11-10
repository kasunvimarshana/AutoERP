<?php

declare(strict_types=1);

namespace Modules\Promotion\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Promotion\Constants\PromotionPermission;
use Modules\Promotion\Services\PromotionCardService;
use Modules\Promotion\Services\PromotionRuntimeService;
use Modules\Vehicle\Events\CurrentCustomerOwnershipEnded;
use Modules\VehicleService\Contracts\JobPromotionHandlerInterface;

final class PromotionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(JobPromotionHandlerInterface::class, PromotionRuntimeService::class);
    }

    public function boot(): void
    {
        Event::listen(CurrentCustomerOwnershipEnded::class, function (CurrentCustomerOwnershipEnded $event): void {
            $this->app->make(PromotionCardService::class)->endForFormerOwner($event);
        });
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('promotion', PromotionPermission::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
