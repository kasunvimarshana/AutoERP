<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Voucher\Application\Contracts\Services\VoucherManagementServiceInterface;
use Modules\Voucher\Application\Contracts\Services\VoucherTypeServiceInterface;
use Modules\Voucher\Application\Contracts\Services\VoucherUtilityServiceInterface;
use Modules\Voucher\Application\Contracts\Services\VoucherWorkflowServiceInterface;
use Modules\Voucher\Application\Repositories\VoucherAllocationRepositoryInterface;
use Modules\Voucher\Application\Repositories\VoucherApprovalRepositoryInterface;
use Modules\Voucher\Application\Repositories\VoucherDocumentLinkRepositoryInterface;
use Modules\Voucher\Application\Repositories\VoucherFinancePostingLinkRepositoryInterface;
use Modules\Voucher\Application\Repositories\VoucherLineRepositoryInterface;
use Modules\Voucher\Application\Repositories\VoucherMetadataDefinitionRepositoryInterface;
use Modules\Voucher\Application\Repositories\VoucherMetadataValueRepositoryInterface;
use Modules\Voucher\Application\Repositories\VoucherPaymentLinkRepositoryInterface;
use Modules\Voucher\Application\Repositories\VoucherRepositoryInterface;
use Modules\Voucher\Application\Repositories\VoucherSettingRepositoryInterface;
use Modules\Voucher\Application\Repositories\VoucherStatusHistoryRepositoryInterface;
use Modules\Voucher\Application\Repositories\VoucherTypeRepositoryInterface;
use Modules\Voucher\Application\Services\VoucherManagementService;
use Modules\Voucher\Application\Services\VoucherTypeService;
use Modules\Voucher\Application\Services\VoucherUtilityService;
use Modules\Voucher\Application\Services\VoucherWorkflowService;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherAllocationModel;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherApprovalModel;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherDocumentLinkModel;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherFinancePostingLinkModel;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherLineModel;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherMetadataDefinitionModel;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherMetadataValueModel;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherPaymentLinkModel;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherSettingModel;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherStatusHistoryModel;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherModel;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherTypeModel;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories\EloquentVoucherAllocationRepository;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories\EloquentVoucherApprovalRepository;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories\EloquentVoucherDocumentLinkRepository;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories\EloquentVoucherFinancePostingLinkRepository;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories\EloquentVoucherLineRepository;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories\EloquentVoucherMetadataDefinitionRepository;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories\EloquentVoucherMetadataValueRepository;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories\EloquentVoucherPaymentLinkRepository;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories\EloquentVoucherRepository;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories\EloquentVoucherSettingRepository;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories\EloquentVoucherStatusHistoryRepository;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories\EloquentVoucherTypeRepository;

final class VoucherServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/voucher.php', 'voucher');

        foreach (
            [
                VoucherTypeServiceInterface::class => VoucherTypeService::class,
                VoucherManagementServiceInterface::class => VoucherManagementService::class,
                VoucherWorkflowServiceInterface::class => VoucherWorkflowService::class,
                VoucherUtilityServiceInterface::class => VoucherUtilityService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(VoucherRepositoryInterface::class, function (): VoucherRepositoryInterface {
            return new EloquentVoucherRepository(new VoucherModel());
        });

        $this->app->singleton(VoucherSettingRepositoryInterface::class, function (): VoucherSettingRepositoryInterface {
            return new EloquentVoucherSettingRepository(new VoucherSettingModel());
        });

        $this->app->singleton(VoucherTypeRepositoryInterface::class, function (): VoucherTypeRepositoryInterface {
            return new EloquentVoucherTypeRepository(new VoucherTypeModel());
        });

        $this->app->singleton(VoucherLineRepositoryInterface::class, function (): VoucherLineRepositoryInterface {
            return new EloquentVoucherLineRepository(new VoucherLineModel());
        });

        $this->app->singleton(VoucherAllocationRepositoryInterface::class, function (): VoucherAllocationRepositoryInterface {
            return new EloquentVoucherAllocationRepository(new VoucherAllocationModel());
        });

        $this->app->singleton(VoucherApprovalRepositoryInterface::class, function (): VoucherApprovalRepositoryInterface {
            return new EloquentVoucherApprovalRepository(new VoucherApprovalModel());
        });

        $this->app->singleton(
            VoucherStatusHistoryRepositoryInterface::class,
            function (): VoucherStatusHistoryRepositoryInterface {
                return new EloquentVoucherStatusHistoryRepository(new VoucherStatusHistoryModel());
            }
        );

        $this->app->singleton(
            VoucherDocumentLinkRepositoryInterface::class,
            function (): VoucherDocumentLinkRepositoryInterface {
                return new EloquentVoucherDocumentLinkRepository(new VoucherDocumentLinkModel());
            }
        );

        $this->app->singleton(VoucherPaymentLinkRepositoryInterface::class, function (): VoucherPaymentLinkRepositoryInterface {
            return new EloquentVoucherPaymentLinkRepository(new VoucherPaymentLinkModel());
        });

        $this->app->singleton(
            VoucherFinancePostingLinkRepositoryInterface::class,
            function (): VoucherFinancePostingLinkRepositoryInterface {
                return new EloquentVoucherFinancePostingLinkRepository(new VoucherFinancePostingLinkModel());
            }
        );

        $this->app->singleton(
            VoucherMetadataDefinitionRepositoryInterface::class,
            function (): VoucherMetadataDefinitionRepositoryInterface {
                return new EloquentVoucherMetadataDefinitionRepository(new VoucherMetadataDefinitionModel());
            }
        );

        $this->app->singleton(
            VoucherMetadataValueRepositoryInterface::class,
            function (): VoucherMetadataValueRepositoryInterface {
                return new EloquentVoucherMetadataValueRepository(new VoucherMetadataValueModel());
            }
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Eloquent/Migrations');
    }
}
