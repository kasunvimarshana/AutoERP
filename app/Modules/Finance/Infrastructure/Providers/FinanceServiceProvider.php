<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Finance\Application\Contracts\ApplyCreditMemoServiceInterface;
use Modules\Finance\Application\Contracts\ApproveApprovalRequestServiceInterface;
use Modules\Finance\Application\Contracts\CancelApprovalRequestServiceInterface;
use Modules\Finance\Application\Contracts\CategorizeBankTransactionServiceInterface;
use Modules\Finance\Application\Contracts\CompleteBankReconciliationServiceInterface;
use Modules\Finance\Application\Contracts\CreateAccountServiceInterface;
use Modules\Finance\Application\Contracts\CreateApprovalRequestServiceInterface;
use Modules\Finance\Application\Contracts\CreateApprovalWorkflowConfigServiceInterface;
use Modules\Finance\Application\Contracts\CreateApTransactionServiceInterface;
use Modules\Finance\Application\Contracts\CreateArTransactionServiceInterface;
use Modules\Finance\Application\Contracts\CreateBankAccountServiceInterface;
use Modules\Finance\Application\Contracts\CreateBankCategoryRuleServiceInterface;
use Modules\Finance\Application\Contracts\CreateBankReconciliationServiceInterface;
use Modules\Finance\Application\Contracts\CreateBankTransactionServiceInterface;
use Modules\Finance\Application\Contracts\CreateCostCenterServiceInterface;
use Modules\Finance\Application\Contracts\CreateCreditMemoServiceInterface;
use Modules\Finance\Application\Contracts\CreateFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\CreateFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\CreateNumberingSequenceServiceInterface;
use Modules\Finance\Application\Contracts\CreatePaymentAllocationServiceInterface;
use Modules\Finance\Application\Contracts\CreatePaymentMethodServiceInterface;
use Modules\Finance\Application\Contracts\CreatePaymentServiceInterface;
use Modules\Finance\Application\Contracts\CreatePaymentTermServiceInterface;
use Modules\Finance\Application\Contracts\DeleteAccountServiceInterface;
use Modules\Finance\Application\Contracts\DeleteApprovalRequestServiceInterface;
use Modules\Finance\Application\Contracts\DeleteApprovalWorkflowConfigServiceInterface;
use Modules\Finance\Application\Contracts\DeleteApTransactionServiceInterface;
use Modules\Finance\Application\Contracts\DeleteArTransactionServiceInterface;
use Modules\Finance\Application\Contracts\DeleteBankAccountServiceInterface;
use Modules\Finance\Application\Contracts\DeleteBankCategoryRuleServiceInterface;
use Modules\Finance\Application\Contracts\DeleteBankReconciliationServiceInterface;
use Modules\Finance\Application\Contracts\DeleteBankTransactionServiceInterface;
use Modules\Finance\Application\Contracts\DeleteCostCenterServiceInterface;
use Modules\Finance\Application\Contracts\DeleteCreditMemoServiceInterface;
use Modules\Finance\Application\Contracts\DeleteFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\DeleteFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\DeleteJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\DeleteNumberingSequenceServiceInterface;
use Modules\Finance\Application\Contracts\DeletePaymentAllocationServiceInterface;
use Modules\Finance\Application\Contracts\DeletePaymentMethodServiceInterface;
use Modules\Finance\Application\Contracts\DeletePaymentServiceInterface;
use Modules\Finance\Application\Contracts\DeletePaymentTermServiceInterface;
use Modules\Finance\Application\Contracts\FinancialReportServiceInterface;
use Modules\Finance\Application\Contracts\FindAccountServiceInterface;
use Modules\Finance\Application\Contracts\FindApprovalRequestServiceInterface;
use Modules\Finance\Application\Contracts\FindApprovalWorkflowConfigServiceInterface;
use Modules\Finance\Application\Contracts\FindApTransactionServiceInterface;
use Modules\Finance\Application\Contracts\FindArTransactionServiceInterface;
use Modules\Finance\Application\Contracts\FindBankAccountServiceInterface;
use Modules\Finance\Application\Contracts\FindBankCategoryRuleServiceInterface;
use Modules\Finance\Application\Contracts\FindBankReconciliationServiceInterface;
use Modules\Finance\Application\Contracts\FindBankTransactionServiceInterface;
use Modules\Finance\Application\Contracts\FindCostCenterServiceInterface;
use Modules\Finance\Application\Contracts\FindCreditMemoServiceInterface;
use Modules\Finance\Application\Contracts\FindFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\FindFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\FindJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\FindNumberingSequenceServiceInterface;
use Modules\Finance\Application\Contracts\FindPaymentAllocationServiceInterface;
use Modules\Finance\Application\Contracts\FindPaymentMethodServiceInterface;
use Modules\Finance\Application\Contracts\FindPaymentServiceInterface;
use Modules\Finance\Application\Contracts\FindPaymentTermServiceInterface;
use Modules\Finance\Application\Contracts\IssueCreditMemoServiceInterface;
use Modules\Finance\Application\Contracts\NextNumberingSequenceServiceInterface;
use Modules\Finance\Application\Contracts\PostJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\PostPaymentServiceInterface;
use Modules\Finance\Application\Contracts\ReconcileApTransactionServiceInterface;
use Modules\Finance\Application\Contracts\ReconcileArTransactionServiceInterface;
use Modules\Finance\Application\Contracts\RejectApprovalRequestServiceInterface;
use Modules\Finance\Application\Contracts\UpdateAccountServiceInterface;
use Modules\Finance\Application\Contracts\UpdateApprovalRequestServiceInterface;
use Modules\Finance\Application\Contracts\UpdateApprovalWorkflowConfigServiceInterface;
use Modules\Finance\Application\Contracts\UpdateApTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UpdateArTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UpdateBankAccountServiceInterface;
use Modules\Finance\Application\Contracts\UpdateBankCategoryRuleServiceInterface;
use Modules\Finance\Application\Contracts\UpdateBankReconciliationServiceInterface;
use Modules\Finance\Application\Contracts\UpdateBankTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UpdateCostCenterServiceInterface;
use Modules\Finance\Application\Contracts\UpdateCreditMemoServiceInterface;
use Modules\Finance\Application\Contracts\UpdateFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\UpdateFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\UpdateJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\UpdateNumberingSequenceServiceInterface;
use Modules\Finance\Application\Contracts\UpdatePaymentMethodServiceInterface;
use Modules\Finance\Application\Contracts\UpdatePaymentServiceInterface;
use Modules\Finance\Application\Contracts\UpdatePaymentTermServiceInterface;
use Modules\Finance\Application\Contracts\VoidCreditMemoServiceInterface;
use Modules\Finance\Application\Contracts\VoidPaymentServiceInterface;
use Modules\Finance\Application\Services\ApplyCreditMemoService;
use Modules\Finance\Application\Services\ApproveApprovalRequestService;
use Modules\Finance\Application\Services\CancelApprovalRequestService;
use Modules\Finance\Application\Services\CategorizeBankTransactionService;
use Modules\Finance\Application\Services\CompleteBankReconciliationService;
use Modules\Finance\Application\Services\CreateAccountService;
use Modules\Finance\Application\Services\CreateApprovalRequestService;
use Modules\Finance\Application\Services\CreateApprovalWorkflowConfigService;
use Modules\Finance\Application\Services\CreateApTransactionService;
use Modules\Finance\Application\Services\CreateArTransactionService;
use Modules\Finance\Application\Services\CreateBankAccountService;
use Modules\Finance\Application\Services\CreateBankCategoryRuleService;
use Modules\Finance\Application\Services\CreateBankReconciliationService;
use Modules\Finance\Application\Services\CreateBankTransactionService;
use Modules\Finance\Application\Services\CreateCostCenterService;
use Modules\Finance\Application\Services\CreateCreditMemoService;
use Modules\Finance\Application\Services\CreateFiscalPeriodService;
use Modules\Finance\Application\Services\CreateFiscalYearService;
use Modules\Finance\Application\Services\CreateJournalEntryService;
use Modules\Finance\Application\Services\CreateNumberingSequenceService;
use Modules\Finance\Application\Services\CreatePaymentAllocationService;
use Modules\Finance\Application\Services\CreatePaymentMethodService;
use Modules\Finance\Application\Services\CreatePaymentService;
use Modules\Finance\Application\Services\CreatePaymentTermService;
use Modules\Finance\Application\Services\DeleteAccountService;
use Modules\Finance\Application\Services\DeleteApprovalRequestService;
use Modules\Finance\Application\Services\DeleteApprovalWorkflowConfigService;
use Modules\Finance\Application\Services\DeleteApTransactionService;
use Modules\Finance\Application\Services\DeleteArTransactionService;
use Modules\Finance\Application\Services\DeleteBankAccountService;
use Modules\Finance\Application\Services\DeleteBankCategoryRuleService;
use Modules\Finance\Application\Services\DeleteBankReconciliationService;
use Modules\Finance\Application\Services\DeleteBankTransactionService;
use Modules\Finance\Application\Services\DeleteCostCenterService;
use Modules\Finance\Application\Services\DeleteCreditMemoService;
use Modules\Finance\Application\Services\DeleteFiscalPeriodService;
use Modules\Finance\Application\Services\DeleteFiscalYearService;
use Modules\Finance\Application\Services\DeleteJournalEntryService;
use Modules\Finance\Application\Services\DeleteNumberingSequenceService;
use Modules\Finance\Application\Services\DeletePaymentAllocationService;
use Modules\Finance\Application\Services\DeletePaymentMethodService;
use Modules\Finance\Application\Services\DeletePaymentService;
use Modules\Finance\Application\Services\DeletePaymentTermService;
use Modules\Finance\Application\Services\FinancialReportService;
use Modules\Finance\Application\Services\FindAccountService;
use Modules\Finance\Application\Services\FindApprovalRequestService;
use Modules\Finance\Application\Services\FindApprovalWorkflowConfigService;
use Modules\Finance\Application\Services\FindApTransactionService;
use Modules\Finance\Application\Services\FindArTransactionService;
use Modules\Finance\Application\Services\FindBankAccountService;
use Modules\Finance\Application\Services\FindBankCategoryRuleService;
use Modules\Finance\Application\Services\FindBankReconciliationService;
use Modules\Finance\Application\Services\FindBankTransactionService;
use Modules\Finance\Application\Services\FindCostCenterService;
use Modules\Finance\Application\Services\FindCreditMemoService;
use Modules\Finance\Application\Services\FindFiscalPeriodService;
use Modules\Finance\Application\Services\FindFiscalYearService;
use Modules\Finance\Application\Services\FindJournalEntryService;
use Modules\Finance\Application\Services\FindNumberingSequenceService;
use Modules\Finance\Application\Services\FindPaymentAllocationService;
use Modules\Finance\Application\Services\FindPaymentMethodService;
use Modules\Finance\Application\Services\FindPaymentService;
use Modules\Finance\Application\Services\FindPaymentTermService;
use Modules\Finance\Application\Services\IssueCreditMemoService;
use Modules\Finance\Application\Services\NextNumberingSequenceService;
use Modules\Finance\Application\Services\PostJournalEntryService;
use Modules\Finance\Application\Services\PostPaymentService;
use Modules\Finance\Application\Services\ReconcileApTransactionService;
use Modules\Finance\Application\Services\ReconcileArTransactionService;
use Modules\Finance\Application\Services\RejectApprovalRequestService;
use Modules\Finance\Application\Services\UpdateAccountService;
use Modules\Finance\Application\Services\UpdateApprovalRequestService;
use Modules\Finance\Application\Services\UpdateApprovalWorkflowConfigService;
use Modules\Finance\Application\Services\UpdateApTransactionService;
use Modules\Finance\Application\Services\UpdateArTransactionService;
use Modules\Finance\Application\Services\UpdateBankAccountService;
use Modules\Finance\Application\Services\UpdateBankCategoryRuleService;
use Modules\Finance\Application\Services\UpdateBankReconciliationService;
use Modules\Finance\Application\Services\UpdateBankTransactionService;
use Modules\Finance\Application\Services\UpdateCostCenterService;
use Modules\Finance\Application\Services\UpdateCreditMemoService;
use Modules\Finance\Application\Services\UpdateFiscalPeriodService;
use Modules\Finance\Application\Services\UpdateFiscalYearService;
use Modules\Finance\Application\Services\UpdateJournalEntryService;
use Modules\Finance\Application\Services\UpdateNumberingSequenceService;
use Modules\Finance\Application\Services\UpdatePaymentMethodService;
use Modules\Finance\Application\Services\UpdatePaymentService;
use Modules\Finance\Application\Services\UpdatePaymentTermService;
use Modules\Finance\Application\Services\VoidCreditMemoService;
use Modules\Finance\Application\Services\VoidPaymentService;
use Modules\Finance\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\ApprovalRequestRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\ApprovalWorkflowConfigRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\ApTransactionRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\ArTransactionRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\BankAccountRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\BankCategoryRuleRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\BankReconciliationRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\BankTransactionRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\CostCenterRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalYearRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\NumberingSequenceRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentAllocationRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentMethodRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentTermRepositoryInterface;
use Modules\Finance\Infrastructure\Listeners\HandlePayrollRunApproved;
use Modules\Finance\Infrastructure\Listeners\HandlePurchaseInvoiceApproved;
use Modules\Finance\Infrastructure\Listeners\HandlePurchasePaymentRecorded;
use Modules\Finance\Infrastructure\Listeners\HandlePurchaseReturnPosted;
use Modules\Finance\Infrastructure\Listeners\HandleCycleCountCompleted;
use Modules\Finance\Infrastructure\Listeners\HandleStockAdjustmentRecorded;
use Modules\Finance\Infrastructure\Listeners\HandleSalesInvoicePosted;
use Modules\Finance\Infrastructure\Listeners\HandleSalesPaymentRecorded;
use Modules\Finance\Infrastructure\Listeners\HandleGoodsReceiptPosted;
use Modules\Finance\Infrastructure\Listeners\HandleSalesReturnReceived;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentAccountRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentApprovalRequestRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentApprovalWorkflowConfigRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentApTransactionRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentArTransactionRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentBankAccountRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentBankCategoryRuleRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentBankReconciliationRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentBankTransactionRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentCostCenterRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentCreditMemoRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentFiscalPeriodRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentFiscalYearRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentJournalEntryRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentNumberingSequenceRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentAllocationRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentMethodRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentTermRepository;
use Modules\HR\Domain\Events\PayrollRunApproved;
use Modules\Inventory\Domain\Events\CycleCountCompleted;
use Modules\Inventory\Domain\Events\StockAdjustmentRecorded;
use Modules\Purchase\Domain\Events\GoodsReceiptPosted;
use Modules\Purchase\Domain\Events\PurchaseInvoiceApproved;
use Modules\Purchase\Domain\Events\PurchasePaymentRecorded;
use Modules\Purchase\Domain\Events\PurchaseReturnPosted;
use Modules\Sales\Domain\Events\SalesInvoicePosted;
use Modules\Sales\Domain\Events\SalesPaymentRecorded;
use Modules\Sales\Domain\Events\SalesReturnReceived;

class FinanceServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $repositoryBindings = [
            AccountRepositoryInterface::class => EloquentAccountRepository::class,
            ApprovalRequestRepositoryInterface::class => EloquentApprovalRequestRepository::class,
            ApprovalWorkflowConfigRepositoryInterface::class => EloquentApprovalWorkflowConfigRepository::class,
            ApTransactionRepositoryInterface::class => EloquentApTransactionRepository::class,
            ArTransactionRepositoryInterface::class => EloquentArTransactionRepository::class,
            BankAccountRepositoryInterface::class => EloquentBankAccountRepository::class,
            BankCategoryRuleRepositoryInterface::class => EloquentBankCategoryRuleRepository::class,
            BankReconciliationRepositoryInterface::class => EloquentBankReconciliationRepository::class,
            BankTransactionRepositoryInterface::class => EloquentBankTransactionRepository::class,
            CostCenterRepositoryInterface::class => EloquentCostCenterRepository::class,
            CreditMemoRepositoryInterface::class => EloquentCreditMemoRepository::class,
            FiscalPeriodRepositoryInterface::class => EloquentFiscalPeriodRepository::class,
            FiscalYearRepositoryInterface::class => EloquentFiscalYearRepository::class,
            JournalEntryRepositoryInterface::class => EloquentJournalEntryRepository::class,
            NumberingSequenceRepositoryInterface::class => EloquentNumberingSequenceRepository::class,
            PaymentAllocationRepositoryInterface::class => EloquentPaymentAllocationRepository::class,
            PaymentMethodRepositoryInterface::class => EloquentPaymentMethodRepository::class,
            PaymentRepositoryInterface::class => EloquentPaymentRepository::class,
            PaymentTermRepositoryInterface::class => EloquentPaymentTermRepository::class,
        ];

        foreach ($repositoryBindings as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }

        $serviceBindings = [
            CreateAccountServiceInterface::class => CreateAccountService::class,
            FindAccountServiceInterface::class => FindAccountService::class,
            UpdateAccountServiceInterface::class => UpdateAccountService::class,
            DeleteAccountServiceInterface::class => DeleteAccountService::class,
            CreateApprovalRequestServiceInterface::class => CreateApprovalRequestService::class,
            FindApprovalRequestServiceInterface::class => FindApprovalRequestService::class,
            UpdateApprovalRequestServiceInterface::class => UpdateApprovalRequestService::class,
            DeleteApprovalRequestServiceInterface::class => DeleteApprovalRequestService::class,
            CreateApprovalWorkflowConfigServiceInterface::class => CreateApprovalWorkflowConfigService::class,
            FindApprovalWorkflowConfigServiceInterface::class => FindApprovalWorkflowConfigService::class,
            UpdateApprovalWorkflowConfigServiceInterface::class => UpdateApprovalWorkflowConfigService::class,
            DeleteApprovalWorkflowConfigServiceInterface::class => DeleteApprovalWorkflowConfigService::class,
            CreateApTransactionServiceInterface::class => CreateApTransactionService::class,
            FindApTransactionServiceInterface::class => FindApTransactionService::class,
            UpdateApTransactionServiceInterface::class => UpdateApTransactionService::class,
            DeleteApTransactionServiceInterface::class => DeleteApTransactionService::class,
            CreateArTransactionServiceInterface::class => CreateArTransactionService::class,
            FindArTransactionServiceInterface::class => FindArTransactionService::class,
            UpdateArTransactionServiceInterface::class => UpdateArTransactionService::class,
            DeleteArTransactionServiceInterface::class => DeleteArTransactionService::class,
            CreateBankAccountServiceInterface::class => CreateBankAccountService::class,
            FindBankAccountServiceInterface::class => FindBankAccountService::class,
            UpdateBankAccountServiceInterface::class => UpdateBankAccountService::class,
            DeleteBankAccountServiceInterface::class => DeleteBankAccountService::class,
            CreateBankCategoryRuleServiceInterface::class => CreateBankCategoryRuleService::class,
            FindBankCategoryRuleServiceInterface::class => FindBankCategoryRuleService::class,
            UpdateBankCategoryRuleServiceInterface::class => UpdateBankCategoryRuleService::class,
            DeleteBankCategoryRuleServiceInterface::class => DeleteBankCategoryRuleService::class,
            CreateBankReconciliationServiceInterface::class => CreateBankReconciliationService::class,
            FindBankReconciliationServiceInterface::class => FindBankReconciliationService::class,
            UpdateBankReconciliationServiceInterface::class => UpdateBankReconciliationService::class,
            DeleteBankReconciliationServiceInterface::class => DeleteBankReconciliationService::class,
            CreateBankTransactionServiceInterface::class => CreateBankTransactionService::class,
            FindBankTransactionServiceInterface::class => FindBankTransactionService::class,
            UpdateBankTransactionServiceInterface::class => UpdateBankTransactionService::class,
            DeleteBankTransactionServiceInterface::class => DeleteBankTransactionService::class,
            CreateCostCenterServiceInterface::class => CreateCostCenterService::class,
            FindCostCenterServiceInterface::class => FindCostCenterService::class,
            UpdateCostCenterServiceInterface::class => UpdateCostCenterService::class,
            DeleteCostCenterServiceInterface::class => DeleteCostCenterService::class,
            CreateCreditMemoServiceInterface::class => CreateCreditMemoService::class,
            FindCreditMemoServiceInterface::class => FindCreditMemoService::class,
            UpdateCreditMemoServiceInterface::class => UpdateCreditMemoService::class,
            DeleteCreditMemoServiceInterface::class => DeleteCreditMemoService::class,
            CreateFiscalYearServiceInterface::class => CreateFiscalYearService::class,
            FindFiscalYearServiceInterface::class => FindFiscalYearService::class,
            UpdateFiscalYearServiceInterface::class => UpdateFiscalYearService::class,
            DeleteFiscalYearServiceInterface::class => DeleteFiscalYearService::class,
            CreateFiscalPeriodServiceInterface::class => CreateFiscalPeriodService::class,
            FindFiscalPeriodServiceInterface::class => FindFiscalPeriodService::class,
            UpdateFiscalPeriodServiceInterface::class => UpdateFiscalPeriodService::class,
            DeleteFiscalPeriodServiceInterface::class => DeleteFiscalPeriodService::class,
            CreateJournalEntryServiceInterface::class => CreateJournalEntryService::class,
            FindJournalEntryServiceInterface::class => FindJournalEntryService::class,
            UpdateJournalEntryServiceInterface::class => UpdateJournalEntryService::class,
            DeleteJournalEntryServiceInterface::class => DeleteJournalEntryService::class,
            PostJournalEntryServiceInterface::class => PostJournalEntryService::class,
            PostPaymentServiceInterface::class => PostPaymentService::class,
            VoidPaymentServiceInterface::class => VoidPaymentService::class,
            IssueCreditMemoServiceInterface::class => IssueCreditMemoService::class,
            ApplyCreditMemoServiceInterface::class => ApplyCreditMemoService::class,
            VoidCreditMemoServiceInterface::class => VoidCreditMemoService::class,
            CompleteBankReconciliationServiceInterface::class => CompleteBankReconciliationService::class,
            ApproveApprovalRequestServiceInterface::class => ApproveApprovalRequestService::class,
            RejectApprovalRequestServiceInterface::class => RejectApprovalRequestService::class,
            CancelApprovalRequestServiceInterface::class => CancelApprovalRequestService::class,
            CategorizeBankTransactionServiceInterface::class => CategorizeBankTransactionService::class,
            ReconcileArTransactionServiceInterface::class => ReconcileArTransactionService::class,
            ReconcileApTransactionServiceInterface::class => ReconcileApTransactionService::class,
            NextNumberingSequenceServiceInterface::class => NextNumberingSequenceService::class,
            CreateNumberingSequenceServiceInterface::class => CreateNumberingSequenceService::class,
            FindNumberingSequenceServiceInterface::class => FindNumberingSequenceService::class,
            UpdateNumberingSequenceServiceInterface::class => UpdateNumberingSequenceService::class,
            DeleteNumberingSequenceServiceInterface::class => DeleteNumberingSequenceService::class,
            CreatePaymentAllocationServiceInterface::class => CreatePaymentAllocationService::class,
            FindPaymentAllocationServiceInterface::class => FindPaymentAllocationService::class,
            DeletePaymentAllocationServiceInterface::class => DeletePaymentAllocationService::class,
            CreatePaymentTermServiceInterface::class => CreatePaymentTermService::class,
            FindPaymentTermServiceInterface::class => FindPaymentTermService::class,
            UpdatePaymentTermServiceInterface::class => UpdatePaymentTermService::class,
            DeletePaymentTermServiceInterface::class => DeletePaymentTermService::class,
            CreatePaymentMethodServiceInterface::class => CreatePaymentMethodService::class,
            FindPaymentMethodServiceInterface::class => FindPaymentMethodService::class,
            UpdatePaymentMethodServiceInterface::class => UpdatePaymentMethodService::class,
            DeletePaymentMethodServiceInterface::class => DeletePaymentMethodService::class,
            CreatePaymentServiceInterface::class => CreatePaymentService::class,
            FindPaymentServiceInterface::class => FindPaymentService::class,
            UpdatePaymentServiceInterface::class => UpdatePaymentService::class,
            DeletePaymentServiceInterface::class => DeletePaymentService::class,
            FinancialReportServiceInterface::class => FinancialReportService::class,
        ];

        foreach ($serviceBindings as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }
    }

    public function boot(): void
    {
        Event::listen(PayrollRunApproved::class, HandlePayrollRunApproved::class);
        Event::listen(GoodsReceiptPosted::class, HandleGoodsReceiptPosted::class);
        Event::listen(PurchaseInvoiceApproved::class, HandlePurchaseInvoiceApproved::class);
        Event::listen(PurchasePaymentRecorded::class, HandlePurchasePaymentRecorded::class);
        Event::listen(PurchaseReturnPosted::class, HandlePurchaseReturnPosted::class);
        Event::listen(CycleCountCompleted::class, HandleCycleCountCompleted::class);
        Event::listen(StockAdjustmentRecorded::class, HandleStockAdjustmentRecorded::class);
        Event::listen(SalesInvoicePosted::class, HandleSalesInvoicePosted::class);
        Event::listen(SalesPaymentRecorded::class, HandleSalesPaymentRecorded::class);
        Event::listen(SalesReturnReceived::class, HandleSalesReturnReceived::class);

        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
