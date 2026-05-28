<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Finance\Application\Contracts\Services\FinancePostingServiceInterface;
use Modules\Finance\Application\Contracts\Services\FiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\Services\PaymentTermServiceInterface;
use Modules\Finance\Application\Contracts\Services\TaxCalculationServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEngines\PostJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEngines\ReverseJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\Accounts\CreateAccountServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\Accounts\DeleteAccountServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\Accounts\GetAccountServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\Accounts\ListAccountsServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\Accounts\UpdateAccountServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\ApTransactions\CreateApTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\ApTransactions\DeleteApTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\ApTransactions\GetApTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\ApTransactions\ListApTransactionsServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\ApTransactions\UpdateApTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\ArTransactions\CreateArTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\ArTransactions\DeleteArTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\ArTransactions\GetArTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\ArTransactions\ListArTransactionsServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\ArTransactions\UpdateArTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankAccounts\CreateBankAccountServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankAccounts\DeleteBankAccountServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankAccounts\GetBankAccountServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankAccounts\ListBankAccountsServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankAccounts\UpdateBankAccountServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankCategoryRules\CreateBankCategoryRuleServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankCategoryRules\DeleteBankCategoryRuleServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankCategoryRules\GetBankCategoryRuleServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankCategoryRules\ListBankCategoryRulesServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankCategoryRules\UpdateBankCategoryRuleServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankReconciliations\CreateBankReconciliationServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankReconciliations\DeleteBankReconciliationServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankReconciliations\GetBankReconciliationServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankReconciliations\ListBankReconciliationsServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankReconciliations\UpdateBankReconciliationServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankTransactions\CreateBankTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankTransactions\DeleteBankTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankTransactions\GetBankTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankTransactions\ListBankTransactionsServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BankTransactions\UpdateBankTransactionServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BudgetLines\CreateBudgetLineServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BudgetLines\DeleteBudgetLineServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BudgetLines\GetBudgetLineServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BudgetLines\ListBudgetLinesServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\BudgetLines\UpdateBudgetLineServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\Budgets\CreateBudgetServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\Budgets\DeleteBudgetServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\Budgets\GetBudgetServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\Budgets\ListBudgetsServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\Budgets\UpdateBudgetServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\CostCenters\CreateCostCenterServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\CostCenters\DeleteCostCenterServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\CostCenters\GetCostCenterServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\CostCenters\ListCostCentersServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\CostCenters\UpdateCostCenterServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FiscalPeriods\CreateFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FiscalPeriods\DeleteFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FiscalPeriods\GetFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FiscalPeriods\ListFiscalPeriodsServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FiscalPeriods\UpdateFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FiscalYears\CreateFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FiscalYears\DeleteFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FiscalYears\GetFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FiscalYears\ListFiscalYearsServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FiscalYears\UpdateFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEntries\CreateJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEntries\DeleteJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEntries\GetJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEntries\ListJournalEntriesServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEntries\UpdateJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEntryLines\CreateJournalEntryLineServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEntryLines\DeleteJournalEntryLineServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEntryLines\GetJournalEntryLineServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEntryLines\ListJournalEntryLinesServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEntryLines\UpdateJournalEntryLineServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\PaymentTerms\CreatePaymentTermServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\PaymentTerms\DeletePaymentTermServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\PaymentTerms\GetPaymentTermServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\PaymentTerms\ListPaymentTermsServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\PaymentTerms\UpdatePaymentTermServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxGroups\CreateTaxGroupServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxGroups\DeleteTaxGroupServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxGroups\GetTaxGroupServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxGroups\ListTaxGroupsServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxGroups\UpdateTaxGroupServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxRates\CreateTaxRateServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxRates\DeleteTaxRateServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxRates\GetTaxRateServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxRates\ListTaxRatesServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxRates\UpdateTaxRateServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxRules\CreateTaxRuleServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxRules\DeleteTaxRuleServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxRules\GetTaxRuleServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxRules\ListTaxRulesServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxRules\UpdateTaxRuleServiceInterface;
use Modules\Finance\Application\Repositories\AccountRepositoryInterface;
use Modules\Finance\Application\Repositories\ApTransactionRepositoryInterface;
use Modules\Finance\Application\Repositories\ArTransactionRepositoryInterface;
use Modules\Finance\Application\Repositories\BankAccountRepositoryInterface;
use Modules\Finance\Application\Repositories\BankCategoryRuleRepositoryInterface;
use Modules\Finance\Application\Repositories\BankReconciliationRepositoryInterface;
use Modules\Finance\Application\Repositories\BankTransactionRepositoryInterface;
use Modules\Finance\Application\Repositories\BudgetLineRepositoryInterface;
use Modules\Finance\Application\Repositories\BudgetRepositoryInterface;
use Modules\Finance\Application\Repositories\CostCenterRepositoryInterface;
use Modules\Finance\Application\Repositories\FiscalPeriodRepositoryInterface;
use Modules\Finance\Application\Repositories\FiscalYearRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryLineRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryRepositoryInterface;
use Modules\Finance\Application\Repositories\PaymentTermRepositoryInterface;
use Modules\Finance\Application\Repositories\TaxGroupRepositoryInterface;
use Modules\Finance\Application\Repositories\TaxRateRepositoryInterface;
use Modules\Finance\Application\Repositories\TaxRuleRepositoryInterface;
use Modules\Finance\Application\UseCases\Accounts\CreateAccountService;
use Modules\Finance\Application\UseCases\Accounts\DeleteAccountService;
use Modules\Finance\Application\UseCases\Accounts\GetAccountService;
use Modules\Finance\Application\UseCases\Accounts\ListAccountsService;
use Modules\Finance\Application\UseCases\Accounts\UpdateAccountService;
use Modules\Finance\Application\UseCases\ApTransactions\CreateApTransactionService;
use Modules\Finance\Application\UseCases\ApTransactions\DeleteApTransactionService;
use Modules\Finance\Application\UseCases\ApTransactions\GetApTransactionService;
use Modules\Finance\Application\UseCases\ApTransactions\ListApTransactionsService;
use Modules\Finance\Application\UseCases\ApTransactions\UpdateApTransactionService;
use Modules\Finance\Application\UseCases\ArTransactions\CreateArTransactionService;
use Modules\Finance\Application\UseCases\ArTransactions\DeleteArTransactionService;
use Modules\Finance\Application\UseCases\ArTransactions\GetArTransactionService;
use Modules\Finance\Application\UseCases\ArTransactions\ListArTransactionsService;
use Modules\Finance\Application\UseCases\ArTransactions\UpdateArTransactionService;
use Modules\Finance\Application\UseCases\BankAccounts\CreateBankAccountService;
use Modules\Finance\Application\UseCases\BankAccounts\DeleteBankAccountService;
use Modules\Finance\Application\UseCases\BankAccounts\GetBankAccountService;
use Modules\Finance\Application\UseCases\BankAccounts\ListBankAccountsService;
use Modules\Finance\Application\UseCases\BankAccounts\UpdateBankAccountService;
use Modules\Finance\Application\UseCases\BankCategoryRules\CreateBankCategoryRuleService;
use Modules\Finance\Application\UseCases\BankCategoryRules\DeleteBankCategoryRuleService;
use Modules\Finance\Application\UseCases\BankCategoryRules\GetBankCategoryRuleService;
use Modules\Finance\Application\UseCases\BankCategoryRules\ListBankCategoryRulesService;
use Modules\Finance\Application\UseCases\BankCategoryRules\UpdateBankCategoryRuleService;
use Modules\Finance\Application\UseCases\BankReconciliations\CreateBankReconciliationService;
use Modules\Finance\Application\UseCases\BankReconciliations\DeleteBankReconciliationService;
use Modules\Finance\Application\UseCases\BankReconciliations\GetBankReconciliationService;
use Modules\Finance\Application\UseCases\BankReconciliations\ListBankReconciliationsService;
use Modules\Finance\Application\UseCases\BankReconciliations\UpdateBankReconciliationService;
use Modules\Finance\Application\UseCases\BankTransactions\CreateBankTransactionService;
use Modules\Finance\Application\UseCases\BankTransactions\DeleteBankTransactionService;
use Modules\Finance\Application\UseCases\BankTransactions\GetBankTransactionService;
use Modules\Finance\Application\UseCases\BankTransactions\ListBankTransactionsService;
use Modules\Finance\Application\UseCases\BankTransactions\UpdateBankTransactionService;
use Modules\Finance\Application\UseCases\BudgetLines\CreateBudgetLineService;
use Modules\Finance\Application\UseCases\BudgetLines\DeleteBudgetLineService;
use Modules\Finance\Application\UseCases\BudgetLines\GetBudgetLineService;
use Modules\Finance\Application\UseCases\BudgetLines\ListBudgetLinesService;
use Modules\Finance\Application\UseCases\BudgetLines\UpdateBudgetLineService;
use Modules\Finance\Application\UseCases\Budgets\CreateBudgetService;
use Modules\Finance\Application\UseCases\Budgets\DeleteBudgetService;
use Modules\Finance\Application\UseCases\Budgets\GetBudgetService;
use Modules\Finance\Application\UseCases\Budgets\ListBudgetsService;
use Modules\Finance\Application\UseCases\Budgets\UpdateBudgetService;
use Modules\Finance\Application\UseCases\CostCenters\CreateCostCenterService;
use Modules\Finance\Application\UseCases\CostCenters\DeleteCostCenterService;
use Modules\Finance\Application\UseCases\CostCenters\GetCostCenterService;
use Modules\Finance\Application\UseCases\CostCenters\ListCostCentersService;
use Modules\Finance\Application\UseCases\CostCenters\UpdateCostCenterService;
use Modules\Finance\Application\UseCases\FiscalPeriods\CreateFiscalPeriodService;
use Modules\Finance\Application\UseCases\FiscalPeriods\DeleteFiscalPeriodService;
use Modules\Finance\Application\UseCases\FiscalPeriods\GetFiscalPeriodService;
use Modules\Finance\Application\UseCases\FiscalPeriods\ListFiscalPeriodsService;
use Modules\Finance\Application\UseCases\FiscalPeriods\UpdateFiscalPeriodService;
use Modules\Finance\Application\UseCases\FiscalYears\CreateFiscalYearService;
use Modules\Finance\Application\UseCases\FiscalYears\DeleteFiscalYearService;
use Modules\Finance\Application\UseCases\FiscalYears\GetFiscalYearService;
use Modules\Finance\Application\UseCases\FiscalYears\ListFiscalYearsService;
use Modules\Finance\Application\UseCases\FiscalYears\UpdateFiscalYearService;
use Modules\Finance\Application\UseCases\JournalEntries\CreateJournalEntryService;
use Modules\Finance\Application\UseCases\JournalEntries\DeleteJournalEntryService;
use Modules\Finance\Application\UseCases\JournalEntries\GetJournalEntryService;
use Modules\Finance\Application\UseCases\JournalEntries\ListJournalEntriesService;
use Modules\Finance\Application\UseCases\JournalEntries\UpdateJournalEntryService;
use Modules\Finance\Application\UseCases\JournalEntryLines\CreateJournalEntryLineService;
use Modules\Finance\Application\UseCases\JournalEntryLines\DeleteJournalEntryLineService;
use Modules\Finance\Application\UseCases\JournalEntryLines\GetJournalEntryLineService;
use Modules\Finance\Application\UseCases\JournalEntryLines\ListJournalEntryLinesService;
use Modules\Finance\Application\UseCases\JournalEntryLines\UpdateJournalEntryLineService;
use Modules\Finance\Application\UseCases\JournalEngines\PostJournalEntryService;
use Modules\Finance\Application\UseCases\JournalEngines\ReverseJournalEntryService;
use Modules\Finance\Application\UseCases\PaymentTerms\CreatePaymentTermService;
use Modules\Finance\Application\UseCases\PaymentTerms\DeletePaymentTermService;
use Modules\Finance\Application\UseCases\PaymentTerms\GetPaymentTermService;
use Modules\Finance\Application\UseCases\PaymentTerms\ListPaymentTermsService;
use Modules\Finance\Application\UseCases\PaymentTerms\UpdatePaymentTermService;
use Modules\Finance\Application\UseCases\TaxGroups\CreateTaxGroupService;
use Modules\Finance\Application\UseCases\TaxGroups\DeleteTaxGroupService;
use Modules\Finance\Application\UseCases\TaxGroups\GetTaxGroupService;
use Modules\Finance\Application\UseCases\TaxGroups\ListTaxGroupsService;
use Modules\Finance\Application\UseCases\TaxGroups\UpdateTaxGroupService;
use Modules\Finance\Application\UseCases\TaxRates\CreateTaxRateService;
use Modules\Finance\Application\UseCases\TaxRates\DeleteTaxRateService;
use Modules\Finance\Application\UseCases\TaxRates\GetTaxRateService;
use Modules\Finance\Application\UseCases\TaxRates\ListTaxRatesService;
use Modules\Finance\Application\UseCases\TaxRates\UpdateTaxRateService;
use Modules\Finance\Application\UseCases\TaxRules\CreateTaxRuleService;
use Modules\Finance\Application\UseCases\TaxRules\DeleteTaxRuleService;
use Modules\Finance\Application\UseCases\TaxRules\GetTaxRuleService;
use Modules\Finance\Application\UseCases\TaxRules\ListTaxRulesService;
use Modules\Finance\Application\UseCases\TaxRules\UpdateTaxRuleService;
use Modules\Finance\Application\Services\FinancePostingService;
use Modules\Finance\Application\Services\FiscalPeriodService;
use Modules\Finance\Application\Services\PaymentTermService;
use Modules\Finance\Application\Services\TaxCalculationService;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\ApTransactionModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\ArTransactionModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankAccountModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankCategoryRuleModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankReconciliationModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankTransactionModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BudgetLineModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BudgetModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\CostCenterModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\FiscalPeriodModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\FiscalYearModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryLineModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\PaymentTermModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxGroupModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxRateModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxRuleModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentAccountRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentApTransactionRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentArTransactionRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentBankAccountRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentBankCategoryRuleRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentBankReconciliationRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentBankTransactionRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentBudgetLineRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentBudgetRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentCostCenterRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentFiscalPeriodRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentFiscalYearRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentJournalEntryLineRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentJournalEntryRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentTermRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaxGroupRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaxRateRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaxRuleRepository;

final class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/finance.php', 'finance');

        foreach (
            [
                ListAccountsServiceInterface::class => ListAccountsService::class,
                GetAccountServiceInterface::class => GetAccountService::class,
                CreateAccountServiceInterface::class => CreateAccountService::class,
                UpdateAccountServiceInterface::class => UpdateAccountService::class,
                DeleteAccountServiceInterface::class => DeleteAccountService::class,
                ListFiscalYearsServiceInterface::class => ListFiscalYearsService::class,
                GetFiscalYearServiceInterface::class => GetFiscalYearService::class,
                CreateFiscalYearServiceInterface::class => CreateFiscalYearService::class,
                UpdateFiscalYearServiceInterface::class => UpdateFiscalYearService::class,
                DeleteFiscalYearServiceInterface::class => DeleteFiscalYearService::class,
                ListFiscalPeriodsServiceInterface::class => ListFiscalPeriodsService::class,
                GetFiscalPeriodServiceInterface::class => GetFiscalPeriodService::class,
                CreateFiscalPeriodServiceInterface::class => CreateFiscalPeriodService::class,
                UpdateFiscalPeriodServiceInterface::class => UpdateFiscalPeriodService::class,
                DeleteFiscalPeriodServiceInterface::class => DeleteFiscalPeriodService::class,
                ListPaymentTermsServiceInterface::class => ListPaymentTermsService::class,
                GetPaymentTermServiceInterface::class => GetPaymentTermService::class,
                CreatePaymentTermServiceInterface::class => CreatePaymentTermService::class,
                UpdatePaymentTermServiceInterface::class => UpdatePaymentTermService::class,
                DeletePaymentTermServiceInterface::class => DeletePaymentTermService::class,
                ListTaxGroupsServiceInterface::class => ListTaxGroupsService::class,
                GetTaxGroupServiceInterface::class => GetTaxGroupService::class,
                CreateTaxGroupServiceInterface::class => CreateTaxGroupService::class,
                UpdateTaxGroupServiceInterface::class => UpdateTaxGroupService::class,
                DeleteTaxGroupServiceInterface::class => DeleteTaxGroupService::class,
                ListTaxRatesServiceInterface::class => ListTaxRatesService::class,
                GetTaxRateServiceInterface::class => GetTaxRateService::class,
                CreateTaxRateServiceInterface::class => CreateTaxRateService::class,
                UpdateTaxRateServiceInterface::class => UpdateTaxRateService::class,
                DeleteTaxRateServiceInterface::class => DeleteTaxRateService::class,
                ListTaxRulesServiceInterface::class => ListTaxRulesService::class,
                GetTaxRuleServiceInterface::class => GetTaxRuleService::class,
                CreateTaxRuleServiceInterface::class => CreateTaxRuleService::class,
                UpdateTaxRuleServiceInterface::class => UpdateTaxRuleService::class,
                DeleteTaxRuleServiceInterface::class => DeleteTaxRuleService::class,
                ListApTransactionsServiceInterface::class => ListApTransactionsService::class,
                GetApTransactionServiceInterface::class => GetApTransactionService::class,
                CreateApTransactionServiceInterface::class => CreateApTransactionService::class,
                UpdateApTransactionServiceInterface::class => UpdateApTransactionService::class,
                DeleteApTransactionServiceInterface::class => DeleteApTransactionService::class,
                ListArTransactionsServiceInterface::class => ListArTransactionsService::class,
                GetArTransactionServiceInterface::class => GetArTransactionService::class,
                CreateArTransactionServiceInterface::class => CreateArTransactionService::class,
                UpdateArTransactionServiceInterface::class => UpdateArTransactionService::class,
                DeleteArTransactionServiceInterface::class => DeleteArTransactionService::class,
                ListCostCentersServiceInterface::class => ListCostCentersService::class,
                GetCostCenterServiceInterface::class => GetCostCenterService::class,
                CreateCostCenterServiceInterface::class => CreateCostCenterService::class,
                UpdateCostCenterServiceInterface::class => UpdateCostCenterService::class,
                DeleteCostCenterServiceInterface::class => DeleteCostCenterService::class,
                ListJournalEntriesServiceInterface::class => ListJournalEntriesService::class,
                GetJournalEntryServiceInterface::class => GetJournalEntryService::class,
                CreateJournalEntryServiceInterface::class => CreateJournalEntryService::class,
                UpdateJournalEntryServiceInterface::class => UpdateJournalEntryService::class,
                DeleteJournalEntryServiceInterface::class => DeleteJournalEntryService::class,
                ListJournalEntryLinesServiceInterface::class => ListJournalEntryLinesService::class,
                GetJournalEntryLineServiceInterface::class => GetJournalEntryLineService::class,
                CreateJournalEntryLineServiceInterface::class => CreateJournalEntryLineService::class,
                UpdateJournalEntryLineServiceInterface::class => UpdateJournalEntryLineService::class,
                DeleteJournalEntryLineServiceInterface::class => DeleteJournalEntryLineService::class,
                ListBudgetsServiceInterface::class => ListBudgetsService::class,
                GetBudgetServiceInterface::class => GetBudgetService::class,
                CreateBudgetServiceInterface::class => CreateBudgetService::class,
                UpdateBudgetServiceInterface::class => UpdateBudgetService::class,
                DeleteBudgetServiceInterface::class => DeleteBudgetService::class,
                ListBudgetLinesServiceInterface::class => ListBudgetLinesService::class,
                GetBudgetLineServiceInterface::class => GetBudgetLineService::class,
                CreateBudgetLineServiceInterface::class => CreateBudgetLineService::class,
                UpdateBudgetLineServiceInterface::class => UpdateBudgetLineService::class,
                DeleteBudgetLineServiceInterface::class => DeleteBudgetLineService::class,
                ListBankAccountsServiceInterface::class => ListBankAccountsService::class,
                GetBankAccountServiceInterface::class => GetBankAccountService::class,
                CreateBankAccountServiceInterface::class => CreateBankAccountService::class,
                UpdateBankAccountServiceInterface::class => UpdateBankAccountService::class,
                DeleteBankAccountServiceInterface::class => DeleteBankAccountService::class,
                ListBankCategoryRulesServiceInterface::class => ListBankCategoryRulesService::class,
                GetBankCategoryRuleServiceInterface::class => GetBankCategoryRuleService::class,
                CreateBankCategoryRuleServiceInterface::class => CreateBankCategoryRuleService::class,
                UpdateBankCategoryRuleServiceInterface::class => UpdateBankCategoryRuleService::class,
                DeleteBankCategoryRuleServiceInterface::class => DeleteBankCategoryRuleService::class,
                ListBankTransactionsServiceInterface::class => ListBankTransactionsService::class,
                GetBankTransactionServiceInterface::class => GetBankTransactionService::class,
                CreateBankTransactionServiceInterface::class => CreateBankTransactionService::class,
                UpdateBankTransactionServiceInterface::class => UpdateBankTransactionService::class,
                DeleteBankTransactionServiceInterface::class => DeleteBankTransactionService::class,
                ListBankReconciliationsServiceInterface::class => ListBankReconciliationsService::class,
                GetBankReconciliationServiceInterface::class => GetBankReconciliationService::class,
                CreateBankReconciliationServiceInterface::class => CreateBankReconciliationService::class,
                UpdateBankReconciliationServiceInterface::class => UpdateBankReconciliationService::class,
                DeleteBankReconciliationServiceInterface::class => DeleteBankReconciliationService::class,
                PostJournalEntryServiceInterface::class => PostJournalEntryService::class,
                ReverseJournalEntryServiceInterface::class => ReverseJournalEntryService::class,
                FiscalPeriodServiceInterface::class => FiscalPeriodService::class,
                FinancePostingServiceInterface::class => FinancePostingService::class,
                TaxCalculationServiceInterface::class => TaxCalculationService::class,
                PaymentTermServiceInterface::class => PaymentTermService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(AccountRepositoryInterface::class, function (): AccountRepositoryInterface {
            return new EloquentAccountRepository($this->app->make(AccountModel::class));
        });

        $this->app->singleton(FiscalYearRepositoryInterface::class, function (): FiscalYearRepositoryInterface {
            return new EloquentFiscalYearRepository($this->app->make(FiscalYearModel::class));
        });

        $this->app->singleton(FiscalPeriodRepositoryInterface::class, function (): FiscalPeriodRepositoryInterface {
            return new EloquentFiscalPeriodRepository($this->app->make(FiscalPeriodModel::class));
        });

        $this->app->singleton(PaymentTermRepositoryInterface::class, function (): PaymentTermRepositoryInterface {
            return new EloquentPaymentTermRepository($this->app->make(PaymentTermModel::class));
        });

        $this->app->singleton(TaxGroupRepositoryInterface::class, function (): TaxGroupRepositoryInterface {
            return new EloquentTaxGroupRepository($this->app->make(TaxGroupModel::class));
        });

        $this->app->singleton(TaxRateRepositoryInterface::class, function (): TaxRateRepositoryInterface {
            return new EloquentTaxRateRepository($this->app->make(TaxRateModel::class));
        });

        $this->app->singleton(TaxRuleRepositoryInterface::class, function (): TaxRuleRepositoryInterface {
            return new EloquentTaxRuleRepository($this->app->make(TaxRuleModel::class));
        });

        $this->app->singleton(ApTransactionRepositoryInterface::class, function (): ApTransactionRepositoryInterface {
            return new EloquentApTransactionRepository($this->app->make(ApTransactionModel::class));
        });

        $this->app->singleton(ArTransactionRepositoryInterface::class, function (): ArTransactionRepositoryInterface {
            return new EloquentArTransactionRepository($this->app->make(ArTransactionModel::class));
        });

        $this->app->singleton(CostCenterRepositoryInterface::class, function (): CostCenterRepositoryInterface {
            return new EloquentCostCenterRepository($this->app->make(CostCenterModel::class));
        });

        $this->app->singleton(JournalEntryRepositoryInterface::class, function (): JournalEntryRepositoryInterface {
            return new EloquentJournalEntryRepository($this->app->make(JournalEntryModel::class));
        });

        $this->app->singleton(
            JournalEntryLineRepositoryInterface::class,
            function (): JournalEntryLineRepositoryInterface {
                return new EloquentJournalEntryLineRepository($this->app->make(JournalEntryLineModel::class));
            },
        );

        $this->app->singleton(BudgetRepositoryInterface::class, function (): BudgetRepositoryInterface {
            return new EloquentBudgetRepository($this->app->make(BudgetModel::class));
        });

        $this->app->singleton(BudgetLineRepositoryInterface::class, function (): BudgetLineRepositoryInterface {
            return new EloquentBudgetLineRepository($this->app->make(BudgetLineModel::class));
        });

        $this->app->singleton(BankAccountRepositoryInterface::class, function (): BankAccountRepositoryInterface {
            return new EloquentBankAccountRepository($this->app->make(BankAccountModel::class));
        });

        $this->app->singleton(
            BankCategoryRuleRepositoryInterface::class,
            function (): BankCategoryRuleRepositoryInterface {
                return new EloquentBankCategoryRuleRepository($this->app->make(BankCategoryRuleModel::class));
            },
        );

        $this->app->singleton(
            BankTransactionRepositoryInterface::class,
            function (): BankTransactionRepositoryInterface {
                return new EloquentBankTransactionRepository($this->app->make(BankTransactionModel::class));
            },
        );

        $this->app->singleton(
            BankReconciliationRepositoryInterface::class,
            function (): BankReconciliationRepositoryInterface {
                return new EloquentBankReconciliationRepository($this->app->make(BankReconciliationModel::class));
            },
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
