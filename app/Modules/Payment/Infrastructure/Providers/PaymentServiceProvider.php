<?php

namespace Modules\Payment\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Payment\Application\Repositories\AdvancePaymentAllocationRepositoryInterface;
use Modules\Payment\Application\Repositories\AdvancePaymentRepositoryInterface;
use Modules\Payment\Application\Repositories\CashRegisterRepositoryInterface;
use Modules\Payment\Application\Repositories\CheckRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentAllocationRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentGroupRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentMethodRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Application\Repositories\WriteOffRepositoryInterface;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentAdvancePaymentAllocationRepository;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentAdvancePaymentRepository;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentCashRegisterRepository;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentCheckRepository;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentAllocationRepository;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentGroupRepository;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentMethodRepository;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentRepository;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentWriteOffRepository;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            AdvancePaymentAllocationRepositoryInterface::class => EloquentAdvancePaymentAllocationRepository::class,
            AdvancePaymentRepositoryInterface::class => EloquentAdvancePaymentRepository::class,
            CashRegisterRepositoryInterface::class => EloquentCashRegisterRepository::class,
            CheckRepositoryInterface::class => EloquentCheckRepository::class,
            PaymentAllocationRepositoryInterface::class => EloquentPaymentAllocationRepository::class,
            PaymentGroupRepositoryInterface::class => EloquentPaymentGroupRepository::class,
            PaymentMethodRepositoryInterface::class => EloquentPaymentMethodRepository::class,
            PaymentRepositoryInterface::class => EloquentPaymentRepository::class,
            WriteOffRepositoryInterface::class => EloquentWriteOffRepository::class,
        ] as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
