<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Voucher\Application\Contracts\UseCases\RecurringVouchers\CreateRecurringVoucherServiceInterface;
use Modules\Voucher\Application\Contracts\UseCases\RecurringVouchers\DeleteRecurringVoucherServiceInterface;
use Modules\Voucher\Application\Contracts\UseCases\RecurringVouchers\GetRecurringVoucherServiceInterface;
use Modules\Voucher\Application\Contracts\UseCases\RecurringVouchers\ListRecurringVouchersServiceInterface;
use Modules\Voucher\Application\Contracts\UseCases\RecurringVouchers\UpdateRecurringVoucherServiceInterface;
use Modules\Voucher\Application\Contracts\UseCases\Vouchers\CreateVoucherServiceInterface;
use Modules\Voucher\Application\Contracts\UseCases\Vouchers\DeleteVoucherServiceInterface;
use Modules\Voucher\Application\Contracts\UseCases\Vouchers\GetVoucherServiceInterface;
use Modules\Voucher\Application\Contracts\UseCases\Vouchers\ListVouchersServiceInterface;
use Modules\Voucher\Application\Contracts\UseCases\Vouchers\UpdateVoucherServiceInterface;
use Modules\Voucher\Application\Repositories\RecurringVoucherRepositoryInterface;
use Modules\Voucher\Application\Repositories\VoucherRepositoryInterface;
use Modules\Voucher\Application\UseCases\RecurringVouchers\CreateRecurringVoucherService;
use Modules\Voucher\Application\UseCases\RecurringVouchers\DeleteRecurringVoucherService;
use Modules\Voucher\Application\UseCases\RecurringVouchers\GetRecurringVoucherService;
use Modules\Voucher\Application\UseCases\RecurringVouchers\ListRecurringVouchersService;
use Modules\Voucher\Application\UseCases\RecurringVouchers\UpdateRecurringVoucherService;
use Modules\Voucher\Application\UseCases\Vouchers\CreateVoucherService;
use Modules\Voucher\Application\UseCases\Vouchers\DeleteVoucherService;
use Modules\Voucher\Application\UseCases\Vouchers\GetVoucherService;
use Modules\Voucher\Application\UseCases\Vouchers\ListVouchersService;
use Modules\Voucher\Application\UseCases\Vouchers\UpdateVoucherService;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\RecurringVoucherModel;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherModel;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories\EloquentRecurringVoucherRepository;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories\EloquentVoucherRepository;

final class VoucherServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/voucher.php', 'voucher');

        foreach (
            [
                ListVouchersServiceInterface::class => ListVouchersService::class,
                GetVoucherServiceInterface::class => GetVoucherService::class,
                CreateVoucherServiceInterface::class => CreateVoucherService::class,
                UpdateVoucherServiceInterface::class => UpdateVoucherService::class,
                DeleteVoucherServiceInterface::class => DeleteVoucherService::class,
                ListRecurringVouchersServiceInterface::class => ListRecurringVouchersService::class,
                GetRecurringVoucherServiceInterface::class => GetRecurringVoucherService::class,
                CreateRecurringVoucherServiceInterface::class => CreateRecurringVoucherService::class,
                UpdateRecurringVoucherServiceInterface::class => UpdateRecurringVoucherService::class,
                DeleteRecurringVoucherServiceInterface::class => DeleteRecurringVoucherService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(VoucherRepositoryInterface::class, function (): VoucherRepositoryInterface {
            return new EloquentVoucherRepository(new VoucherModel());
        });
        $this->app->singleton(RecurringVoucherRepositoryInterface::class, function (): RecurringVoucherRepositoryInterface {
            return new EloquentRecurringVoucherRepository(new RecurringVoucherModel());
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Eloquent/Migrations');
    }
}