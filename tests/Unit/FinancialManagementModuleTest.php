<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Modules\Accounting\Application\DTOs\FinancialReportData;
use Modules\Accounting\Application\Services\BankAccountService;
use Modules\Accounting\Application\Services\BulkReclassifyTransactionsService;
use Modules\Accounting\Application\Services\CategorizeTransactionService;
use Modules\Accounting\Application\Services\GenerateFinancialReportService;
use Modules\Accounting\Application\Services\ImportBankTransactionsService;
use Modules\Accounting\Domain\Entities\BankAccount;
use Modules\Accounting\Domain\Entities\BankTransaction;
use Modules\Accounting\Domain\Entities\Budget;
use Modules\Accounting\Domain\Entities\ExpenseCategory;
use Modules\Accounting\Domain\Entities\TransactionRule;
use Modules\Accounting\Domain\Exceptions\BankAccountNotFoundException;
use Modules\Accounting\Domain\Exceptions\BankTransactionNotFoundException;
use Modules\Accounting\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\BankAccountRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\BankTransactionRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\TransactionRuleRepositoryInterface;

class FinancialManagementModuleTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────────────
    // Factory helpers
    // ──────────────────────────────────────────────────────────────────────

    private function makeBankAccount(array $overrides = []): BankAccount
    {
        return new BankAccount(
            $overrides['id']              ?? 1,
            $overrides['tenant_id']       ?? 1,
            $overrides['account_id']      ?? 10,
            $overrides['name']            ?? 'Main Checking',
            $overrides['bank_name']       ?? 'First National Bank',
            array_key_exists('account_number', $overrides) ? $overrides['account_number'] : '****1234',
            $overrides['account_type']    ?? 'checking',
            $overrides['currency']        ?? 'USD',
            $overrides['current_balance'] ?? 5000.0,
            $overrides['is_active']       ?? true,
            $overrides['description']     ?? null,
            $overrides['last_synced_at']  ?? null,
            $overrides['created_at']      ?? null,
            $overrides['updated_at']      ?? null,
        );
    }

    private function makeBankTransaction(array $overrides = []): BankTransaction
    {
        return new BankTransaction(
            $overrides['id']                  ?? 1,
            $overrides['tenant_id']           ?? 1,
            $overrides['bank_account_id']     ?? 1,
            $overrides['transaction_date']    ?? new \DateTimeImmutable('2024-01-15'),
            $overrides['amount']              ?? 250.0,
            $overrides['description']         ?? 'Office Supplies - Amazon',
            $overrides['type']                ?? 'debit',
            $overrides['status']              ?? 'pending',
            $overrides['expense_category_id'] ?? null,
            $overrides['account_id']          ?? null,
            $overrides['journal_entry_id']    ?? null,
            array_key_exists('reference', $overrides) ? $overrides['reference'] : 'TXN-REF-001',
            $overrides['source']              ?? 'import',
            $overrides['metadata']            ?? [],
            $overrides['created_at']          ?? null,
            $overrides['updated_at']          ?? null,
        );
    }

    private function makeExpenseCategory(array $overrides = []): ExpenseCategory
    {
        return new ExpenseCategory(
            $overrides['id']          ?? 1,
            $overrides['tenant_id']   ?? 1,
            $overrides['name']        ?? 'Office Supplies',
            $overrides['code']        ?? 'EXP-OFFICE',
            $overrides['parent_id']   ?? null,
            array_key_exists('account_id', $overrides) ? $overrides['account_id'] : 5,
            $overrides['is_active']   ?? true,
            $overrides['description'] ?? 'General office supplies',
            $overrides['created_at']  ?? null,
            $overrides['updated_at']  ?? null,
        );
    }

    private function makeTransactionRule(array $overrides = []): TransactionRule
    {
        return new TransactionRule(
            $overrides['id']          ?? 1,
            $overrides['tenant_id']   ?? 1,
            $overrides['name']        ?? 'Amazon Rule',
            $overrides['is_active']   ?? true,
            $overrides['priority']    ?? 1,
            $overrides['conditions']  ?? [['field' => 'description', 'operator' => 'contains', 'value' => 'amazon']],
            $overrides['actions']     ?? ['expense_category_id' => 3, 'account_id' => 7],
            $overrides['apply_to']    ?? 'all',
            $overrides['match_count'] ?? 0,
            $overrides['created_at']  ?? null,
            $overrides['updated_at']  ?? null,
        );
    }

    private function makeBudget(array $overrides = []): Budget
    {
        return new Budget(
            $overrides['id']                  ?? 1,
            $overrides['tenant_id']           ?? 1,
            $overrides['account_id']          ?? null,
            $overrides['expense_category_id'] ?? 1,
            $overrides['name']                ?? 'Q1 Office Budget',
            $overrides['period_start']        ?? new \DateTimeImmutable('2024-01-01'),
            $overrides['period_end']          ?? new \DateTimeImmutable('2024-03-31'),
            $overrides['amount']              ?? 2000.0,
            $overrides['spent_amount']        ?? 500.0,
            $overrides['currency']            ?? 'USD',
            $overrides['notes']               ?? null,
            $overrides['created_at']          ?? null,
            $overrides['updated_at']          ?? null,
        );
    }

    // ──────────────────────────────────────────────────────────────────────
    // BankAccount entity tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_bank_account_creation_with_defaults(): void
    {
        $account = $this->makeBankAccount();

        $this->assertEquals(1, $account->getId());
        $this->assertEquals(1, $account->getTenantId());
        $this->assertEquals(10, $account->getAccountId());
        $this->assertEquals('Main Checking', $account->getName());
        $this->assertEquals('First National Bank', $account->getBankName());
        $this->assertEquals('****1234', $account->getAccountNumber());
        $this->assertEquals('checking', $account->getAccountType());
        $this->assertEquals('USD', $account->getCurrency());
        $this->assertEquals(5000.0, $account->getCurrentBalance());
        $this->assertTrue($account->isActive());
    }

    public function test_bank_account_is_credit_card(): void
    {
        $account = $this->makeBankAccount(['account_type' => 'credit_card']);
        $this->assertTrue($account->isCreditCard());
    }

    public function test_bank_account_is_not_credit_card_for_checking(): void
    {
        $account = $this->makeBankAccount(['account_type' => 'checking']);
        $this->assertFalse($account->isCreditCard());
    }

    public function test_bank_account_update_balance(): void
    {
        $account = $this->makeBankAccount(['current_balance' => 1000.0]);
        $account->updateBalance(1500.0);
        $this->assertEquals(1500.0, $account->getCurrentBalance());
    }

    public function test_bank_account_deactivate(): void
    {
        $account = $this->makeBankAccount(['is_active' => true]);
        $account->deactivate();
        $this->assertFalse($account->isActive());
    }

    public function test_bank_account_with_null_account_number(): void
    {
        $account = $this->makeBankAccount(['account_number' => null]);
        $this->assertNull($account->getAccountNumber());
    }

    public function test_bank_account_inactive_by_default(): void
    {
        $account = $this->makeBankAccount(['is_active' => false]);
        $this->assertFalse($account->isActive());
    }

    public function test_bank_account_with_last_synced_at(): void
    {
        $syncedAt = new \DateTimeImmutable('2024-06-01 10:00:00');
        $account  = $this->makeBankAccount(['last_synced_at' => $syncedAt]);
        $this->assertEquals($syncedAt, $account->getLastSyncedAt());
    }

    public function test_bank_account_savings_type(): void
    {
        $account = $this->makeBankAccount(['account_type' => 'savings']);
        $this->assertEquals('savings', $account->getAccountType());
        $this->assertFalse($account->isCreditCard());
    }

    public function test_bank_account_with_description(): void
    {
        $account = $this->makeBankAccount(['description' => 'Primary operating account']);
        $this->assertEquals('Primary operating account', $account->getDescription());
    }

    // ──────────────────────────────────────────────────────────────────────
    // BankTransaction entity tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_bank_transaction_creation(): void
    {
        $txn = $this->makeBankTransaction();

        $this->assertEquals(1, $txn->getId());
        $this->assertEquals(1, $txn->getTenantId());
        $this->assertEquals(1, $txn->getBankAccountId());
        $this->assertEquals(250.0, $txn->getAmount());
        $this->assertEquals('Office Supplies - Amazon', $txn->getDescription());
        $this->assertEquals('debit', $txn->getType());
        $this->assertEquals('pending', $txn->getStatus());
        $this->assertEquals('import', $txn->getSource());
    }

    public function test_bank_transaction_is_pending(): void
    {
        $txn = $this->makeBankTransaction(['status' => 'pending']);
        $this->assertTrue($txn->isPending());
    }

    public function test_bank_transaction_is_not_pending_when_categorized(): void
    {
        $txn = $this->makeBankTransaction(['status' => 'categorized']);
        $this->assertFalse($txn->isPending());
    }

    public function test_bank_transaction_is_debit(): void
    {
        $txn = $this->makeBankTransaction(['type' => 'debit']);
        $this->assertTrue($txn->isDebit());
        $this->assertFalse($txn->isCredit());
    }

    public function test_bank_transaction_is_credit(): void
    {
        $txn = $this->makeBankTransaction(['type' => 'credit']);
        $this->assertTrue($txn->isCredit());
        $this->assertFalse($txn->isDebit());
    }

    public function test_bank_transaction_categorize(): void
    {
        $txn = $this->makeBankTransaction();
        $txn->categorize(3, 7);

        $this->assertEquals(3, $txn->getExpenseCategoryId());
        $this->assertEquals(7, $txn->getAccountId());
        $this->assertEquals('categorized', $txn->getStatus());
        $this->assertFalse($txn->isPending());
    }

    public function test_bank_transaction_reconcile(): void
    {
        $txn = $this->makeBankTransaction(['status' => 'categorized']);
        $txn->reconcile();
        $this->assertEquals('reconciled', $txn->getStatus());
    }

    public function test_bank_transaction_exclude(): void
    {
        $txn = $this->makeBankTransaction();
        $txn->exclude();
        $this->assertEquals('excluded', $txn->getStatus());
    }

    public function test_bank_transaction_reclassify(): void
    {
        $txn = $this->makeBankTransaction(['expense_category_id' => 1, 'account_id' => 2]);
        $txn->reclassify(5, 9);

        $this->assertEquals(5, $txn->getExpenseCategoryId());
        $this->assertEquals(9, $txn->getAccountId());
    }

    public function test_bank_transaction_metadata(): void
    {
        $meta = ['bank_ref' => 'BNK-12345', 'import_batch' => 'BATCH-001'];
        $txn  = $this->makeBankTransaction(['metadata' => $meta]);
        $this->assertEquals($meta, $txn->getMetadata());
    }

    public function test_bank_transaction_with_null_reference(): void
    {
        $txn = $this->makeBankTransaction(['reference' => null]);
        $this->assertNull($txn->getReference());
    }

    // ──────────────────────────────────────────────────────────────────────
    // ExpenseCategory entity tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_expense_category_creation(): void
    {
        $cat = $this->makeExpenseCategory();

        $this->assertEquals(1, $cat->getId());
        $this->assertEquals('Office Supplies', $cat->getName());
        $this->assertEquals('EXP-OFFICE', $cat->getCode());
        $this->assertTrue($cat->isActive());
        $this->assertNull($cat->getParentId());
    }

    public function test_expense_category_is_top_level(): void
    {
        $cat = $this->makeExpenseCategory(['parent_id' => null]);
        $this->assertTrue($cat->isTopLevel());
    }

    public function test_expense_category_is_not_top_level(): void
    {
        $cat = $this->makeExpenseCategory(['parent_id' => 5]);
        $this->assertFalse($cat->isTopLevel());
        $this->assertEquals(5, $cat->getParentId());
    }

    public function test_expense_category_deactivate(): void
    {
        $cat = $this->makeExpenseCategory(['is_active' => true]);
        $cat->deactivate();
        $this->assertFalse($cat->isActive());
    }

    public function test_expense_category_with_description(): void
    {
        $cat = $this->makeExpenseCategory(['description' => 'Stationery and printer supplies']);
        $this->assertEquals('Stationery and printer supplies', $cat->getDescription());
    }

    public function test_expense_category_null_account_id(): void
    {
        $cat = $this->makeExpenseCategory(['account_id' => null]);
        $this->assertNull($cat->getAccountId());
    }

    // ──────────────────────────────────────────────────────────────────────
    // TransactionRule entity tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_transaction_rule_creation(): void
    {
        $rule = $this->makeTransactionRule();

        $this->assertEquals(1, $rule->getId());
        $this->assertEquals('Amazon Rule', $rule->getName());
        $this->assertTrue($rule->isActive());
        $this->assertEquals(1, $rule->getPriority());
        $this->assertEquals('all', $rule->getApplyTo());
        $this->assertEquals(0, $rule->getMatchCount());
    }

    public function test_transaction_rule_increment_match_count(): void
    {
        $rule = $this->makeTransactionRule(['match_count' => 5]);
        $rule->incrementMatchCount();
        $this->assertEquals(6, $rule->getMatchCount());
    }

    public function test_transaction_rule_activate(): void
    {
        $rule = $this->makeTransactionRule(['is_active' => false]);
        $rule->activate();
        $this->assertTrue($rule->isActive());
    }

    public function test_transaction_rule_deactivate(): void
    {
        $rule = $this->makeTransactionRule(['is_active' => true]);
        $rule->deactivate();
        $this->assertFalse($rule->isActive());
    }

    public function test_transaction_rule_matches_description_contains(): void
    {
        $rule = $this->makeTransactionRule([
            'conditions' => [['field' => 'description', 'operator' => 'contains', 'value' => 'amazon']],
            'apply_to'   => 'all',
        ]);
        $txn = $this->makeBankTransaction(['description' => 'Office Supplies - Amazon', 'type' => 'debit']);
        $this->assertTrue($rule->matches($txn));
    }

    public function test_transaction_rule_no_match_when_inactive(): void
    {
        $rule = $this->makeTransactionRule(['is_active' => false]);
        $txn  = $this->makeBankTransaction(['description' => 'Office Supplies - Amazon']);
        $this->assertFalse($rule->matches($txn));
    }

    public function test_transaction_rule_apply_to_debit_only_matches_debit(): void
    {
        $rule = $this->makeTransactionRule([
            'conditions' => [['field' => 'description', 'operator' => 'contains', 'value' => 'amazon']],
            'apply_to'   => 'debit',
        ]);
        $debitTxn  = $this->makeBankTransaction(['type' => 'debit', 'description' => 'Amazon purchase']);
        $creditTxn = $this->makeBankTransaction(['type' => 'credit', 'description' => 'Amazon purchase']);

        $this->assertTrue($rule->matches($debitTxn));
        $this->assertFalse($rule->matches($creditTxn));
    }

    public function test_transaction_rule_apply_to_credit_only_matches_credit(): void
    {
        $rule = $this->makeTransactionRule([
            'conditions' => [['field' => 'description', 'operator' => 'contains', 'value' => 'refund']],
            'apply_to'   => 'credit',
        ]);
        $creditTxn = $this->makeBankTransaction(['type' => 'credit', 'description' => 'Amazon refund']);
        $debitTxn  = $this->makeBankTransaction(['type' => 'debit', 'description' => 'Amazon refund']);

        $this->assertTrue($rule->matches($creditTxn));
        $this->assertFalse($rule->matches($debitTxn));
    }

    public function test_transaction_rule_starts_with_operator(): void
    {
        $rule = $this->makeTransactionRule([
            'conditions' => [['field' => 'description', 'operator' => 'starts_with', 'value' => 'payroll']],
            'apply_to'   => 'all',
        ]);
        $matching    = $this->makeBankTransaction(['description' => 'Payroll - March 2024']);
        $nonMatching = $this->makeBankTransaction(['description' => 'Monthly Payroll - March 2024']);

        $this->assertTrue($rule->matches($matching));
        $this->assertFalse($rule->matches($nonMatching));
    }

    public function test_transaction_rule_ends_with_operator(): void
    {
        $rule = $this->makeTransactionRule([
            'conditions' => [['field' => 'description', 'operator' => 'ends_with', 'value' => 'inc']],
            'apply_to'   => 'all',
        ]);
        $txn = $this->makeBankTransaction(['description' => 'Payment to Acme Inc']);
        $this->assertTrue($rule->matches($txn));
    }

    public function test_transaction_rule_equals_operator(): void
    {
        $rule = $this->makeTransactionRule([
            'conditions' => [['field' => 'description', 'operator' => 'equals', 'value' => 'rent payment']],
            'apply_to'   => 'all',
        ]);
        $matching    = $this->makeBankTransaction(['description' => 'Rent Payment']);
        $nonMatching = $this->makeBankTransaction(['description' => 'Office Rent Payment']);

        $this->assertTrue($rule->matches($matching));
        $this->assertFalse($rule->matches($nonMatching));
    }

    public function test_transaction_rule_greater_than_operator(): void
    {
        $rule = $this->makeTransactionRule([
            'conditions' => [['field' => 'amount', 'operator' => 'greater_than', 'value' => 1000]],
            'apply_to'   => 'all',
        ]);
        $highTxn = $this->makeBankTransaction(['amount' => 1500.0]);
        $lowTxn  = $this->makeBankTransaction(['amount' => 500.0]);

        $this->assertTrue($rule->matches($highTxn));
        $this->assertFalse($rule->matches($lowTxn));
    }

    public function test_transaction_rule_less_than_operator(): void
    {
        $rule = $this->makeTransactionRule([
            'conditions' => [['field' => 'amount', 'operator' => 'less_than', 'value' => 100]],
            'apply_to'   => 'all',
        ]);
        $lowTxn  = $this->makeBankTransaction(['amount' => 50.0]);
        $highTxn = $this->makeBankTransaction(['amount' => 200.0]);

        $this->assertTrue($rule->matches($lowTxn));
        $this->assertFalse($rule->matches($highTxn));
    }

    public function test_transaction_rule_no_match_for_unknown_operator(): void
    {
        $rule = $this->makeTransactionRule([
            'conditions' => [['field' => 'description', 'operator' => 'regex', 'value' => '/amazon/i']],
            'apply_to'   => 'all',
        ]);
        $txn = $this->makeBankTransaction(['description' => 'Amazon order']);
        $this->assertFalse($rule->matches($txn));
    }

    public function test_transaction_rule_multiple_conditions_all_must_match(): void
    {
        $rule = $this->makeTransactionRule([
            'conditions' => [
                ['field' => 'description', 'operator' => 'contains', 'value' => 'amazon'],
                ['field' => 'amount', 'operator' => 'greater_than', 'value' => 100],
            ],
            'apply_to' => 'all',
        ]);
        $matchingTxn    = $this->makeBankTransaction(['description' => 'Amazon order', 'amount' => 200.0]);
        $nonMatchingTxn = $this->makeBankTransaction(['description' => 'Amazon order', 'amount' => 50.0]);

        $this->assertTrue($rule->matches($matchingTxn));
        $this->assertFalse($rule->matches($nonMatchingTxn));
    }

    // ──────────────────────────────────────────────────────────────────────
    // Budget entity tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_budget_creation(): void
    {
        $budget = $this->makeBudget();

        $this->assertEquals(1, $budget->getId());
        $this->assertEquals('Q1 Office Budget', $budget->getName());
        $this->assertEquals(2000.0, $budget->getAmount());
        $this->assertEquals(500.0, $budget->getSpentAmount());
        $this->assertEquals('USD', $budget->getCurrency());
    }

    public function test_budget_remaining_amount(): void
    {
        $budget = $this->makeBudget(['amount' => 2000.0, 'spent_amount' => 750.0]);
        $this->assertEquals(1250.0, $budget->getRemainingAmount());
    }

    public function test_budget_utilization_percent(): void
    {
        $budget = $this->makeBudget(['amount' => 2000.0, 'spent_amount' => 500.0]);
        $this->assertEquals(25.0, $budget->getUtilizationPercent());
    }

    public function test_budget_utilization_percent_zero_amount(): void
    {
        $budget = $this->makeBudget(['amount' => 0.0, 'spent_amount' => 0.0]);
        $this->assertEquals(0.0, $budget->getUtilizationPercent());
    }

    public function test_budget_is_over_budget(): void
    {
        $budget = $this->makeBudget(['amount' => 1000.0, 'spent_amount' => 1200.0]);
        $this->assertTrue($budget->isOverBudget());
    }

    public function test_budget_is_not_over_budget(): void
    {
        $budget = $this->makeBudget(['amount' => 1000.0, 'spent_amount' => 800.0]);
        $this->assertFalse($budget->isOverBudget());
    }

    public function test_budget_record_spend(): void
    {
        $budget = $this->makeBudget(['spent_amount' => 500.0]);
        $budget->recordSpend(300.0);
        $this->assertEquals(800.0, $budget->getSpentAmount());
    }

    public function test_budget_record_spend_can_exceed_limit(): void
    {
        $budget = $this->makeBudget(['amount' => 1000.0, 'spent_amount' => 950.0]);
        $budget->recordSpend(200.0);
        $this->assertEquals(1150.0, $budget->getSpentAmount());
        $this->assertTrue($budget->isOverBudget());
    }

    public function test_budget_full_utilization(): void
    {
        $budget = $this->makeBudget(['amount' => 1000.0, 'spent_amount' => 1000.0]);
        $this->assertEquals(100.0, $budget->getUtilizationPercent());
        $this->assertFalse($budget->isOverBudget());
    }

    // ──────────────────────────────────────────────────────────────────────
    // FinancialReportData DTO tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_financial_report_data_balance_sheet_is_balanced(): void
    {
        $report = new FinancialReportData(
            reportType:       'balance_sheet',
            generatedAt:      new \DateTimeImmutable(),
            sections:         [],
            totalAssets:      10000.0,
            totalLiabilities: 6000.0,
            totalEquity:      4000.0,
        );
        $this->assertTrue($report->isBalanced());
    }

    public function test_financial_report_data_balance_sheet_is_not_balanced(): void
    {
        $report = new FinancialReportData(
            reportType:       'balance_sheet',
            generatedAt:      new \DateTimeImmutable(),
            sections:         [],
            totalAssets:      10000.0,
            totalLiabilities: 6000.0,
            totalEquity:      3000.0, // does not balance
        );
        $this->assertFalse($report->isBalanced());
    }

    public function test_financial_report_data_profit_loss_always_balanced(): void
    {
        $report = new FinancialReportData(
            reportType:    'profit_loss',
            generatedAt:   new \DateTimeImmutable(),
            sections:      [],
            totalRevenue:  50000.0,
            totalExpenses: 35000.0,
            netIncome:     15000.0,
        );
        $this->assertTrue($report->isBalanced());
    }

    public function test_financial_report_data_net_income_stored(): void
    {
        $report = new FinancialReportData(
            reportType:    'profit_loss',
            generatedAt:   new \DateTimeImmutable(),
            sections:      ['revenue' => [], 'expenses' => []],
            totalRevenue:  80000.0,
            totalExpenses: 55000.0,
            netIncome:     25000.0,
        );
        $this->assertEquals(25000.0, $report->netIncome);
        $this->assertEquals(80000.0, $report->totalRevenue);
        $this->assertEquals(55000.0, $report->totalExpenses);
    }

    public function test_financial_report_data_default_values(): void
    {
        $report = new FinancialReportData(
            reportType:  'balance_sheet',
            generatedAt: new \DateTimeImmutable(),
            sections:    [],
        );
        $this->assertEquals(0.0, $report->totalAssets);
        $this->assertEquals(0.0, $report->totalLiabilities);
        $this->assertEquals(0.0, $report->totalEquity);
        $this->assertEquals(0.0, $report->totalRevenue);
        $this->assertEquals(0.0, $report->totalExpenses);
        $this->assertEquals(0.0, $report->netIncome);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Exception tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_bank_account_not_found_exception_message(): void
    {
        $e = new BankAccountNotFoundException(42);
        $this->assertStringContainsString('42', $e->getMessage());
        $this->assertStringContainsString('Bank account', $e->getMessage());
        $this->assertInstanceOf(\RuntimeException::class, $e);
    }

    public function test_bank_transaction_not_found_exception_message(): void
    {
        $e = new BankTransactionNotFoundException(99);
        $this->assertStringContainsString('99', $e->getMessage());
        $this->assertStringContainsString('Bank transaction', $e->getMessage());
        $this->assertInstanceOf(\RuntimeException::class, $e);
    }

    // ──────────────────────────────────────────────────────────────────────
    // BankAccountService tests
    // ──────────────────────────────────────────────────────────────────────

    private function makeBankAccountRepoMock(): MockObject&BankAccountRepositoryInterface
    {
        return $this->createMock(BankAccountRepositoryInterface::class);
    }

    public function test_bank_account_service_find_by_id_returns_account(): void
    {
        $account = $this->makeBankAccount();
        $repo    = $this->makeBankAccountRepoMock();
        $repo->expects($this->once())->method('findById')->with(1)->willReturn($account);

        $service = new BankAccountService($repo);
        $result  = $service->findById(1);

        $this->assertSame($account, $result);
    }

    public function test_bank_account_service_find_by_id_throws_when_not_found(): void
    {
        $repo = $this->makeBankAccountRepoMock();
        $repo->method('findById')->willReturn(null);

        $service = new BankAccountService($repo);
        $this->expectException(BankAccountNotFoundException::class);
        $service->findById(999);
    }

    public function test_bank_account_service_find_by_tenant(): void
    {
        $accounts = [$this->makeBankAccount(), $this->makeBankAccount(['id' => 2])];
        $repo     = $this->makeBankAccountRepoMock();
        $repo->expects($this->once())->method('findByTenant')->with(1)->willReturn($accounts);

        $service = new BankAccountService($repo);
        $result  = $service->findByTenant(1);

        $this->assertCount(2, $result);
    }

    public function test_bank_account_service_create(): void
    {
        $data    = ['name' => 'Savings', 'bank_name' => 'Chase', 'currency' => 'USD'];
        $account = $this->makeBankAccount(['name' => 'Savings']);
        $repo    = $this->makeBankAccountRepoMock();
        $repo->expects($this->once())->method('create')->with($data)->willReturn($account);

        $service = new BankAccountService($repo);
        $result  = $service->create($data);

        $this->assertSame($account, $result);
    }

    public function test_bank_account_service_update_returns_updated_account(): void
    {
        $account = $this->makeBankAccount(['name' => 'Updated Checking']);
        $repo    = $this->makeBankAccountRepoMock();
        $repo->expects($this->once())->method('update')->with(1, ['name' => 'Updated Checking'])->willReturn($account);

        $service = new BankAccountService($repo);
        $result  = $service->update(1, ['name' => 'Updated Checking']);

        $this->assertEquals('Updated Checking', $result->getName());
    }

    public function test_bank_account_service_update_throws_when_not_found(): void
    {
        $repo = $this->makeBankAccountRepoMock();
        $repo->method('update')->willReturn(null);

        $service = new BankAccountService($repo);
        $this->expectException(BankAccountNotFoundException::class);
        $service->update(999, ['name' => 'Ghost']);
    }

    public function test_bank_account_service_delete_returns_true(): void
    {
        $account = $this->makeBankAccount();
        $repo    = $this->makeBankAccountRepoMock();
        $repo->method('findById')->willReturn($account);
        $repo->expects($this->once())->method('delete')->with(1)->willReturn(true);

        $service = new BankAccountService($repo);
        $this->assertTrue($service->delete(1));
    }

    public function test_bank_account_service_delete_throws_when_not_found(): void
    {
        $repo = $this->makeBankAccountRepoMock();
        $repo->method('findById')->willReturn(null);

        $service = new BankAccountService($repo);
        $this->expectException(BankAccountNotFoundException::class);
        $service->delete(404);
    }

    // ──────────────────────────────────────────────────────────────────────
    // CategorizeTransactionService tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_categorize_transaction_service_execute_returns_transaction(): void
    {
        $txn     = $this->makeBankTransaction();
        $updated = $this->makeBankTransaction(['status' => 'categorized', 'expense_category_id' => 3, 'account_id' => 7]);
        $txnRepo  = $this->createMock(BankTransactionRepositoryInterface::class);
        $ruleRepo = $this->createMock(TransactionRuleRepositoryInterface::class);

        $txnRepo->method('findById')->with(1)->willReturn($txn);
        $txnRepo->method('update')->willReturn($updated);

        $service = new CategorizeTransactionService($txnRepo, $ruleRepo);
        $result  = $service->execute(1, 3, 7);

        $this->assertEquals('categorized', $result->getStatus());
    }

    public function test_categorize_transaction_service_throws_when_transaction_not_found(): void
    {
        $txnRepo  = $this->createMock(BankTransactionRepositoryInterface::class);
        $ruleRepo = $this->createMock(TransactionRuleRepositoryInterface::class);
        $txnRepo->method('findById')->willReturn(null);

        $service = new CategorizeTransactionService($txnRepo, $ruleRepo);
        $this->expectException(BankTransactionNotFoundException::class);
        $service->execute(999, 3, 7);
    }

    public function test_categorize_transaction_auto_apply_rules_returns_count(): void
    {
        $rule = $this->makeTransactionRule([
            'conditions' => [['field' => 'description', 'operator' => 'contains', 'value' => 'amazon']],
            'actions'    => ['expense_category_id' => 3, 'account_id' => 7],
            'apply_to'   => 'all',
        ]);
        $txn1 = $this->makeBankTransaction(['id' => 1, 'description' => 'Amazon order']);
        $txn2 = $this->makeBankTransaction(['id' => 2, 'description' => 'Grocery store']);

        $txnRepo  = $this->createMock(BankTransactionRepositoryInterface::class);
        $ruleRepo = $this->createMock(TransactionRuleRepositoryInterface::class);

        $ruleRepo->method('findActiveByTenant')->with(1)->willReturn([$rule]);
        $txnRepo->method('findPendingByTenant')->with(1)->willReturn([$txn1, $txn2]);
        $txnRepo->method('update')->willReturn(null);
        $ruleRepo->expects($this->once())->method('incrementMatchCount');

        $service = new CategorizeTransactionService($txnRepo, $ruleRepo);
        $count   = $service->autoApplyRules(1);

        $this->assertEquals(1, $count);
    }

    public function test_categorize_transaction_auto_apply_no_rules_returns_zero(): void
    {
        $txnRepo  = $this->createMock(BankTransactionRepositoryInterface::class);
        $ruleRepo = $this->createMock(TransactionRuleRepositoryInterface::class);

        $ruleRepo->method('findActiveByTenant')->willReturn([]);
        $txnRepo->method('findPendingByTenant')->willReturn([
            $this->makeBankTransaction(),
        ]);

        $service = new CategorizeTransactionService($txnRepo, $ruleRepo);
        $this->assertEquals(0, $service->autoApplyRules(1));
    }

    public function test_categorize_transaction_auto_apply_skips_rule_with_missing_actions(): void
    {
        $rule = $this->makeTransactionRule([
            'conditions' => [['field' => 'description', 'operator' => 'contains', 'value' => 'amazon']],
            'actions'    => [], // missing category/account
        ]);
        $txn     = $this->makeBankTransaction(['description' => 'Amazon order']);
        $txnRepo  = $this->createMock(BankTransactionRepositoryInterface::class);
        $ruleRepo = $this->createMock(TransactionRuleRepositoryInterface::class);

        $ruleRepo->method('findActiveByTenant')->willReturn([$rule]);
        $txnRepo->method('findPendingByTenant')->willReturn([$txn]);

        $service = new CategorizeTransactionService($txnRepo, $ruleRepo);
        $this->assertEquals(0, $service->autoApplyRules(1));
    }

    // ──────────────────────────────────────────────────────────────────────
    // BulkReclassifyTransactionsService tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_bulk_reclassify_returns_affected_count(): void
    {
        $txnRepo = $this->createMock(BankTransactionRepositoryInterface::class);
        $txnRepo->expects($this->once())
            ->method('updateBatch')
            ->with([1, 2, 3], ['expense_category_id' => 5, 'account_id' => 8, 'status' => 'categorized'])
            ->willReturn(3);

        $service = new BulkReclassifyTransactionsService($txnRepo);
        $this->assertEquals(3, $service->execute([1, 2, 3], 5, 8));
    }

    public function test_bulk_reclassify_returns_zero_for_empty_ids(): void
    {
        $txnRepo = $this->createMock(BankTransactionRepositoryInterface::class);
        $txnRepo->expects($this->never())->method('updateBatch');

        $service = new BulkReclassifyTransactionsService($txnRepo);
        $this->assertEquals(0, $service->execute([], 5, 8));
    }

    // ──────────────────────────────────────────────────────────────────────
    // ImportBankTransactionsService tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_import_bank_transactions_returns_count(): void
    {
        $account     = $this->makeBankAccount(['id' => 1, 'tenant_id' => 1]);
        $bankAccRepo = $this->createMock(BankAccountRepositoryInterface::class);
        $txnRepo     = $this->createMock(BankTransactionRepositoryInterface::class);

        $bankAccRepo->method('findById')->with(1)->willReturn($account);
        $txnRepo->expects($this->once())->method('createBatch')->willReturn(2);

        $service = new ImportBankTransactionsService($bankAccRepo, $txnRepo);
        $result  = $service->execute(1, [
            ['amount' => 100.0, 'description' => 'Txn 1', 'type' => 'debit', 'transaction_date' => '2024-01-01'],
            ['amount' => 200.0, 'description' => 'Txn 2', 'type' => 'credit', 'transaction_date' => '2024-01-02'],
        ]);

        $this->assertEquals(2, $result);
    }

    public function test_import_bank_transactions_throws_when_account_not_found(): void
    {
        $bankAccRepo = $this->createMock(BankAccountRepositoryInterface::class);
        $txnRepo     = $this->createMock(BankTransactionRepositoryInterface::class);
        $bankAccRepo->method('findById')->willReturn(null);

        $service = new ImportBankTransactionsService($bankAccRepo, $txnRepo);
        $this->expectException(BankAccountNotFoundException::class);
        $service->execute(999, [['amount' => 50.0]]);
    }

    public function test_import_bank_transactions_enriches_records_with_tenant_and_status(): void
    {
        $account     = $this->makeBankAccount(['id' => 1, 'tenant_id' => 7]);
        $bankAccRepo = $this->createMock(BankAccountRepositoryInterface::class);
        $txnRepo     = $this->createMock(BankTransactionRepositoryInterface::class);

        $bankAccRepo->method('findById')->willReturn($account);

        $capturedRecords = null;
        $txnRepo->expects($this->once())
            ->method('createBatch')
            ->willReturnCallback(function (array $records) use (&$capturedRecords) {
                $capturedRecords = $records;
                return count($records);
            });

        $service = new ImportBankTransactionsService($bankAccRepo, $txnRepo);
        $service->execute(1, [
            ['amount' => 150.0, 'description' => 'Test transaction', 'type' => 'debit'],
        ]);

        $this->assertNotNull($capturedRecords);
        $this->assertEquals(1, $capturedRecords[0]['bank_account_id']);
        $this->assertEquals(7, $capturedRecords[0]['tenant_id']);
        $this->assertEquals('pending', $capturedRecords[0]['status']);
        $this->assertEquals('import', $capturedRecords[0]['source']);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GenerateFinancialReportService tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_generate_balance_sheet_returns_financial_report_data(): void
    {
        $accountRepo = $this->createMock(AccountRepositoryInterface::class);
        $paginator   = $this->createMock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
        $paginator->method('items')->willReturn([]);
        $accountRepo->method('findByTenant')->willReturn($paginator);

        $service = new GenerateFinancialReportService($accountRepo);
        $report  = $service->balanceSheet(1, new \DateTimeImmutable());

        $this->assertEquals('balance_sheet', $report->reportType);
        $this->assertInstanceOf(\DateTimeInterface::class, $report->generatedAt);
    }

    public function test_generate_profit_loss_returns_financial_report_data(): void
    {
        $accountRepo = $this->createMock(AccountRepositoryInterface::class);
        $paginator   = $this->createMock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
        $paginator->method('items')->willReturn([]);
        $accountRepo->method('findByTenant')->willReturn($paginator);

        $service = new GenerateFinancialReportService($accountRepo);
        $report  = $service->profitAndLoss(1, new \DateTimeImmutable('2024-01-01'), new \DateTimeImmutable('2024-12-31'));

        $this->assertEquals('profit_loss', $report->reportType);
        $this->assertEquals(0.0, $report->netIncome);
    }
}
