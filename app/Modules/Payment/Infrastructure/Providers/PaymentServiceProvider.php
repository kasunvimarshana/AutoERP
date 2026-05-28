<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Payment\Application\Contracts\Services\AdvancePaymentAllocationServiceInterface;
use Modules\Payment\Application\Contracts\Services\AdvancePaymentServiceInterface;
use Modules\Payment\Application\Contracts\Services\PaymentAllocationServiceInterface;
use Modules\Payment\Application\Contracts\Services\PaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentEngines\AllocatePaymentDocumentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentEngines\SettlePaymentStatusServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentEngines\UnallocatePaymentDocumentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\AdvancePaymentAllocations\{
    CreateAdvancePaymentAllocationServiceInterface,
    DeleteAdvancePaymentAllocationServiceInterface,
    GetAdvancePaymentAllocationServiceInterface,
    ListAdvancePaymentAllocationsServiceInterface,
    UpdateAdvancePaymentAllocationServiceInterface,
};
use Modules\Payment\Application\Contracts\UseCases\AdvancePayments\CreateAdvancePaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\AdvancePayments\DeleteAdvancePaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\AdvancePayments\GetAdvancePaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\AdvancePayments\ListAdvancePaymentsServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\AdvancePayments\UpdateAdvancePaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\CashRegisters\CreateCashRegisterServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\CashRegisters\DeleteCashRegisterServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\CashRegisters\GetCashRegisterServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\CashRegisters\ListCashRegistersServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\CashRegisters\UpdateCashRegisterServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Checks\CreateCheckServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Checks\DeleteCheckServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Checks\GetCheckServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Checks\ListChecksServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Checks\UpdateCheckServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentAllocations\CreatePaymentAllocationServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentAllocations\DeletePaymentAllocationServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentAllocations\GetPaymentAllocationServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentAllocations\ListPaymentAllocationsServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentAllocations\UpdatePaymentAllocationServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentGroups\CreatePaymentGroupServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentGroups\DeletePaymentGroupServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentGroups\GetPaymentGroupServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentGroups\ListPaymentGroupsServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentGroups\UpdatePaymentGroupServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentMethods\CreatePaymentMethodServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentMethods\DeletePaymentMethodServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentMethods\GetPaymentMethodServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentMethods\ListPaymentMethodsServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentMethods\UpdatePaymentMethodServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Payments\CreatePaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Payments\DeletePaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Payments\GetPaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Payments\ListPaymentsServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Payments\UpdatePaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\WriteOffs\CreateWriteOffServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\WriteOffs\DeleteWriteOffServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\WriteOffs\GetWriteOffServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\WriteOffs\ListWriteOffsServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\WriteOffs\UpdateWriteOffServiceInterface;
use Modules\Payment\Application\Repositories\AdvancePaymentAllocationRepositoryInterface;
use Modules\Payment\Application\Repositories\AdvancePaymentRepositoryInterface;
use Modules\Payment\Application\Repositories\CashRegisterRepositoryInterface;
use Modules\Payment\Application\Repositories\CheckRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentAllocationRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentGroupRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentMethodRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Application\Repositories\WriteOffRepositoryInterface;
use Modules\Payment\Application\UseCases\AdvancePaymentAllocations\CreateAdvancePaymentAllocationService;
use Modules\Payment\Application\UseCases\AdvancePaymentAllocations\DeleteAdvancePaymentAllocationService;
use Modules\Payment\Application\UseCases\AdvancePaymentAllocations\GetAdvancePaymentAllocationService;
use Modules\Payment\Application\UseCases\AdvancePaymentAllocations\ListAdvancePaymentAllocationsService;
use Modules\Payment\Application\UseCases\AdvancePaymentAllocations\UpdateAdvancePaymentAllocationService;
use Modules\Payment\Application\UseCases\AdvancePayments\CreateAdvancePaymentService;
use Modules\Payment\Application\UseCases\AdvancePayments\DeleteAdvancePaymentService;
use Modules\Payment\Application\UseCases\AdvancePayments\GetAdvancePaymentService;
use Modules\Payment\Application\UseCases\AdvancePayments\ListAdvancePaymentsService;
use Modules\Payment\Application\UseCases\AdvancePayments\UpdateAdvancePaymentService;
use Modules\Payment\Application\UseCases\CashRegisters\CreateCashRegisterService;
use Modules\Payment\Application\UseCases\CashRegisters\DeleteCashRegisterService;
use Modules\Payment\Application\UseCases\CashRegisters\GetCashRegisterService;
use Modules\Payment\Application\UseCases\CashRegisters\ListCashRegistersService;
use Modules\Payment\Application\UseCases\CashRegisters\UpdateCashRegisterService;
use Modules\Payment\Application\UseCases\Checks\CreateCheckService;
use Modules\Payment\Application\UseCases\Checks\DeleteCheckService;
use Modules\Payment\Application\UseCases\Checks\GetCheckService;
use Modules\Payment\Application\UseCases\Checks\ListChecksService;
use Modules\Payment\Application\UseCases\Checks\UpdateCheckService;
use Modules\Payment\Application\UseCases\PaymentAllocations\CreatePaymentAllocationService;
use Modules\Payment\Application\UseCases\PaymentAllocations\DeletePaymentAllocationService;
use Modules\Payment\Application\UseCases\PaymentAllocations\GetPaymentAllocationService;
use Modules\Payment\Application\UseCases\PaymentAllocations\ListPaymentAllocationsService;
use Modules\Payment\Application\UseCases\PaymentAllocations\UpdatePaymentAllocationService;
use Modules\Payment\Application\UseCases\PaymentEngines\AllocatePaymentDocumentService;
use Modules\Payment\Application\UseCases\PaymentEngines\SettlePaymentStatusService;
use Modules\Payment\Application\UseCases\PaymentEngines\UnallocatePaymentDocumentService;
use Modules\Payment\Application\UseCases\PaymentGroups\CreatePaymentGroupService;
use Modules\Payment\Application\UseCases\PaymentGroups\DeletePaymentGroupService;
use Modules\Payment\Application\UseCases\PaymentGroups\GetPaymentGroupService;
use Modules\Payment\Application\UseCases\PaymentGroups\ListPaymentGroupsService;
use Modules\Payment\Application\UseCases\PaymentGroups\UpdatePaymentGroupService;
use Modules\Payment\Application\UseCases\PaymentMethods\CreatePaymentMethodService;
use Modules\Payment\Application\UseCases\PaymentMethods\DeletePaymentMethodService;
use Modules\Payment\Application\UseCases\PaymentMethods\GetPaymentMethodService;
use Modules\Payment\Application\UseCases\PaymentMethods\ListPaymentMethodsService;
use Modules\Payment\Application\UseCases\PaymentMethods\UpdatePaymentMethodService;
use Modules\Payment\Application\UseCases\Payments\CreatePaymentService;
use Modules\Payment\Application\UseCases\Payments\DeletePaymentService;
use Modules\Payment\Application\UseCases\Payments\GetPaymentService;
use Modules\Payment\Application\UseCases\Payments\ListPaymentsService;
use Modules\Payment\Application\UseCases\Payments\UpdatePaymentService;
use Modules\Payment\Application\UseCases\WriteOffs\CreateWriteOffService;
use Modules\Payment\Application\UseCases\WriteOffs\DeleteWriteOffService;
use Modules\Payment\Application\UseCases\WriteOffs\GetWriteOffService;
use Modules\Payment\Application\UseCases\WriteOffs\ListWriteOffsService;
use Modules\Payment\Application\UseCases\WriteOffs\UpdateWriteOffService;
use Modules\Payment\Application\Services\AdvancePaymentAllocationService;
use Modules\Payment\Application\Services\AdvancePaymentService;
use Modules\Payment\Application\Services\PaymentAllocationService;
use Modules\Payment\Application\Services\PaymentService;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\AdvancePaymentAllocationModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\AdvancePaymentModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\CashRegisterModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\CheckModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentAllocationModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentGroupModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentMethodModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\WriteOffModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentAdvancePaymentAllocationRepository;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentAdvancePaymentRepository;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentCashRegisterRepository;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentCheckRepository;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentAllocationRepository;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentGroupRepository;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentMethodRepository;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentRepository;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentWriteOffRepository;

final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/payment.php', 'payment');

        foreach (
            [
                ListPaymentMethodsServiceInterface::class => ListPaymentMethodsService::class,
                GetPaymentMethodServiceInterface::class => GetPaymentMethodService::class,
                CreatePaymentMethodServiceInterface::class => CreatePaymentMethodService::class,
                UpdatePaymentMethodServiceInterface::class => UpdatePaymentMethodService::class,
                DeletePaymentMethodServiceInterface::class => DeletePaymentMethodService::class,
                ListPaymentGroupsServiceInterface::class => ListPaymentGroupsService::class,
                GetPaymentGroupServiceInterface::class => GetPaymentGroupService::class,
                CreatePaymentGroupServiceInterface::class => CreatePaymentGroupService::class,
                UpdatePaymentGroupServiceInterface::class => UpdatePaymentGroupService::class,
                DeletePaymentGroupServiceInterface::class => DeletePaymentGroupService::class,
                ListPaymentsServiceInterface::class => ListPaymentsService::class,
                GetPaymentServiceInterface::class => GetPaymentService::class,
                CreatePaymentServiceInterface::class => CreatePaymentService::class,
                UpdatePaymentServiceInterface::class => UpdatePaymentService::class,
                DeletePaymentServiceInterface::class => DeletePaymentService::class,
                ListPaymentAllocationsServiceInterface::class => ListPaymentAllocationsService::class,
                GetPaymentAllocationServiceInterface::class => GetPaymentAllocationService::class,
                CreatePaymentAllocationServiceInterface::class => CreatePaymentAllocationService::class,
                UpdatePaymentAllocationServiceInterface::class => UpdatePaymentAllocationService::class,
                DeletePaymentAllocationServiceInterface::class => DeletePaymentAllocationService::class,
                ListCashRegistersServiceInterface::class => ListCashRegistersService::class,
                GetCashRegisterServiceInterface::class => GetCashRegisterService::class,
                CreateCashRegisterServiceInterface::class => CreateCashRegisterService::class,
                UpdateCashRegisterServiceInterface::class => UpdateCashRegisterService::class,
                DeleteCashRegisterServiceInterface::class => DeleteCashRegisterService::class,
                ListChecksServiceInterface::class => ListChecksService::class,
                GetCheckServiceInterface::class => GetCheckService::class,
                CreateCheckServiceInterface::class => CreateCheckService::class,
                UpdateCheckServiceInterface::class => UpdateCheckService::class,
                DeleteCheckServiceInterface::class => DeleteCheckService::class,
                ListAdvancePaymentsServiceInterface::class => ListAdvancePaymentsService::class,
                GetAdvancePaymentServiceInterface::class => GetAdvancePaymentService::class,
                CreateAdvancePaymentServiceInterface::class => CreateAdvancePaymentService::class,
                UpdateAdvancePaymentServiceInterface::class => UpdateAdvancePaymentService::class,
                DeleteAdvancePaymentServiceInterface::class => DeleteAdvancePaymentService::class,
                ListAdvancePaymentAllocationsServiceInterface::class => ListAdvancePaymentAllocationsService::class,
                GetAdvancePaymentAllocationServiceInterface::class => GetAdvancePaymentAllocationService::class,
                CreateAdvancePaymentAllocationServiceInterface::class => CreateAdvancePaymentAllocationService::class,
                UpdateAdvancePaymentAllocationServiceInterface::class => UpdateAdvancePaymentAllocationService::class,
                DeleteAdvancePaymentAllocationServiceInterface::class => DeleteAdvancePaymentAllocationService::class,
                ListWriteOffsServiceInterface::class => ListWriteOffsService::class,
                GetWriteOffServiceInterface::class => GetWriteOffService::class,
                CreateWriteOffServiceInterface::class => CreateWriteOffService::class,
                UpdateWriteOffServiceInterface::class => UpdateWriteOffService::class,
                DeleteWriteOffServiceInterface::class => DeleteWriteOffService::class,
                AllocatePaymentDocumentServiceInterface::class => AllocatePaymentDocumentService::class,
                UnallocatePaymentDocumentServiceInterface::class => UnallocatePaymentDocumentService::class,
                SettlePaymentStatusServiceInterface::class => SettlePaymentStatusService::class,
                PaymentServiceInterface::class => PaymentService::class,
                PaymentAllocationServiceInterface::class => PaymentAllocationService::class,
                AdvancePaymentServiceInterface::class => AdvancePaymentService::class,
                AdvancePaymentAllocationServiceInterface::class => AdvancePaymentAllocationService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(PaymentMethodRepositoryInterface::class, function (): PaymentMethodRepositoryInterface {
            return new EloquentPaymentMethodRepository(new PaymentMethodModel());
        });
        $this->app->singleton(PaymentGroupRepositoryInterface::class, function (): PaymentGroupRepositoryInterface {
            return new EloquentPaymentGroupRepository(new PaymentGroupModel());
        });
        $this->app->singleton(PaymentRepositoryInterface::class, function (): PaymentRepositoryInterface {
            return new EloquentPaymentRepository(new PaymentModel());
        });
        $this->app->singleton(
            PaymentAllocationRepositoryInterface::class,
            function (): PaymentAllocationRepositoryInterface {
                return new EloquentPaymentAllocationRepository(new PaymentAllocationModel());
            },
        );
        $this->app->singleton(CashRegisterRepositoryInterface::class, function (): CashRegisterRepositoryInterface {
            return new EloquentCashRegisterRepository(new CashRegisterModel());
        });
        $this->app->singleton(CheckRepositoryInterface::class, function (): CheckRepositoryInterface {
            return new EloquentCheckRepository(new CheckModel());
        });
        $this->app->singleton(AdvancePaymentRepositoryInterface::class, function (): AdvancePaymentRepositoryInterface {
            return new EloquentAdvancePaymentRepository(new AdvancePaymentModel());
        });
        $this->app->singleton(
            AdvancePaymentAllocationRepositoryInterface::class,
            function (): AdvancePaymentAllocationRepositoryInterface {
                return new EloquentAdvancePaymentAllocationRepository(new AdvancePaymentAllocationModel());
            },
        );
        $this->app->singleton(WriteOffRepositoryInterface::class, function (): WriteOffRepositoryInterface {
            return new EloquentWriteOffRepository(new WriteOffModel());
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Eloquent/Migrations');
    }
}
