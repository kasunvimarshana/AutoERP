<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Domain\Entities\Account;
use Modules\Finance\Domain\Entities\ApprovalRequest;
use Modules\Finance\Domain\Entities\ApprovalWorkflowConfig;
use Modules\Finance\Domain\Entities\ApTransaction;
use Modules\Finance\Domain\Entities\ArTransaction;
use Modules\Finance\Domain\Entities\BankAccount;
use Modules\Finance\Domain\Entities\BankCategoryRule;
use Modules\Finance\Domain\Entities\BankReconciliation;
use Modules\Finance\Domain\Entities\BankTransaction;
use Modules\Finance\Domain\Entities\CostCenter;
use Modules\Finance\Domain\Entities\CreditMemo;
use Modules\Finance\Domain\Entities\FiscalPeriod;
use Modules\Finance\Domain\Entities\FiscalYear;
use Modules\Finance\Domain\Entities\JournalEntry;
use Modules\Finance\Domain\Entities\JournalEntryLine;
use Modules\Finance\Domain\Entities\NumberingSequence;
use Modules\Finance\Domain\Entities\Payment;
use Modules\Finance\Domain\Entities\PaymentAllocation;
use Modules\Finance\Domain\Entities\PaymentMethod;
use Modules\Finance\Domain\Entities\PaymentTerm;
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
use Tests\TestCase;

class FinanceRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;
    private int $tenant2Id = 2;
    private int $userId = 101;
    private int $user2Id = 102;
    private int $currencyId = 1;
    private int $customerId = 201;
    private int $customer2Id = 202;
    private int $supplierId = 301;
    private int $supplier2Id = 302;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    public function test_account_save_find_and_find_by_tenant_and_code(): void
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);

        $saved = $repository->save(new Account(
            tenantId: $this->tenantId,
            code: '1000',
            name: 'Cash',
            type: 'asset',
            normalBalance: 'debit',
            isActive: true,
        ));

        $found = $repository->find($saved->getId());
        $byCode = $repository->findByTenantAndCode($this->tenantId, '1000');
        $wrongTenant = $repository->findByTenantAndCode($this->tenant2Id, '1000');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('Cash', $found->getName());
        $this->assertNotNull($byCode);
        $this->assertSame($saved->getId(), $byCode->getId());
        $this->assertNull($wrongTenant);
    }

    public function test_fiscal_year_save_find_and_find_by_tenant_and_name(): void
    {
        /** @var FiscalYearRepositoryInterface $repository */
        $repository = app(FiscalYearRepositoryInterface::class);

        $saved = $repository->save(new FiscalYear(
            tenantId: $this->tenantId,
            name: 'FY2026',
            startDate: new \DateTimeImmutable('2026-01-01'),
            endDate: new \DateTimeImmutable('2026-12-31'),
            status: 'open',
        ));

        $found = $repository->find($saved->getId());
        $byName = $repository->findByTenantAndName($this->tenantId, 'FY2026');
        $wrongTenant = $repository->findByTenantAndName($this->tenant2Id, 'FY2026');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('FY2026', $found->getName());
        $this->assertNotNull($byName);
        $this->assertSame($saved->getId(), $byName->getId());
        $this->assertNull($wrongTenant);
    }

    public function test_fiscal_period_save_find_open_period_and_find_by_year_period_number(): void
    {
        /** @var FiscalYearRepositoryInterface $yearRepository */
        $yearRepository = app(FiscalYearRepositoryInterface::class);

        /** @var FiscalPeriodRepositoryInterface $periodRepository */
        $periodRepository = app(FiscalPeriodRepositoryInterface::class);

        $year = $yearRepository->save(new FiscalYear(
            tenantId: $this->tenantId,
            name: 'FY2026',
            startDate: new \DateTimeImmutable('2026-01-01'),
            endDate: new \DateTimeImmutable('2026-12-31'),
            status: 'open',
        ));

        $saved = $periodRepository->save(new FiscalPeriod(
            tenantId: $this->tenantId,
            fiscalYearId: $year->getId(),
            periodNumber: 1,
            name: 'January 2026',
            startDate: new \DateTimeImmutable('2026-01-01'),
            endDate: new \DateTimeImmutable('2026-01-31'),
            status: 'open',
        ));

        $found = $periodRepository->find($saved->getId());
        $open = $periodRepository->findOpenPeriodForDate($this->tenantId, new \DateTimeImmutable('2026-01-15'));
        $byYearAndPeriod = $periodRepository->findByTenantAndYearAndPeriodNumber($this->tenantId, $year->getId(), 1);
        $wrongTenant = $periodRepository->findByTenantAndYearAndPeriodNumber($this->tenant2Id, $year->getId(), 1);

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertNotNull($open);
        $this->assertSame($saved->getId(), $open->getId());
        $this->assertNotNull($byYearAndPeriod);
        $this->assertSame($saved->getId(), $byYearAndPeriod->getId());
        $this->assertNull($wrongTenant);
    }

    public function test_journal_entry_save_and_find_with_lines(): void
    {
        $cashAccount = $this->createAccount($this->tenantId, '1000', 'Cash', 'asset', 'debit');
        $revenueAccount = $this->createAccount($this->tenantId, '4000', 'Revenue', 'revenue', 'credit');

        /** @var FiscalYearRepositoryInterface $yearRepository */
        $yearRepository = app(FiscalYearRepositoryInterface::class);

        /** @var FiscalPeriodRepositoryInterface $periodRepository */
        $periodRepository = app(FiscalPeriodRepositoryInterface::class);

        /** @var JournalEntryRepositoryInterface $repository */
        $repository = app(JournalEntryRepositoryInterface::class);

        $year = $yearRepository->save(new FiscalYear(
            tenantId: $this->tenantId,
            name: 'FY2026',
            startDate: new \DateTimeImmutable('2026-01-01'),
            endDate: new \DateTimeImmutable('2026-12-31'),
        ));

        $period = $periodRepository->save(new FiscalPeriod(
            tenantId: $this->tenantId,
            fiscalYearId: $year->getId(),
            periodNumber: 1,
            name: 'January',
            startDate: new \DateTimeImmutable('2026-01-01'),
            endDate: new \DateTimeImmutable('2026-01-31'),
        ));

        $saved = $repository->save(new JournalEntry(
            tenantId: $this->tenantId,
            fiscalPeriodId: $period->getId(),
            entryDate: new \DateTimeImmutable('2026-01-20'),
            createdBy: $this->userId,
            entryType: 'manual',
            entryNumber: 'JE-0001',
            description: 'Sales posting',
            lines: [
                new JournalEntryLine(
                    accountId: $cashAccount->getId(),
                    debitAmount: 1000.0,
                    creditAmount: 0.0,
                    currencyId: $this->currencyId,
                    exchangeRate: 1.0,
                    baseDebitAmount: 1000.0,
                    baseCreditAmount: 0.0,
                ),
                new JournalEntryLine(
                    accountId: $revenueAccount->getId(),
                    debitAmount: 0.0,
                    creditAmount: 1000.0,
                    currencyId: $this->currencyId,
                    exchangeRate: 1.0,
                    baseDebitAmount: 0.0,
                    baseCreditAmount: 1000.0,
                ),
            ],
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('JE-0001', $found->getEntryNumber());
        $this->assertCount(2, $found->getLines());
    }

    public function test_payment_method_save_and_find(): void
    {
        $cashAccount = $this->createAccount($this->tenantId, '1000', 'Cash', 'asset', 'debit');

        /** @var PaymentMethodRepositoryInterface $repository */
        $repository = app(PaymentMethodRepositoryInterface::class);

        $saved = $repository->save(new PaymentMethod(
            tenantId: $this->tenantId,
            name: 'Bank Transfer',
            type: 'bank_transfer',
            accountId: $cashAccount->getId(),
            isActive: true,
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('Bank Transfer', $found->getName());
    }

    public function test_payment_save_find_by_number_and_idempotency_key(): void
    {
        $cashAccount = $this->createAccount($this->tenantId, '1000', 'Cash', 'asset', 'debit');
        $paymentMethod = $this->createPaymentMethod($this->tenantId, $cashAccount->getId(), 'Bank Transfer');

        /** @var PaymentRepositoryInterface $repository */
        $repository = app(PaymentRepositoryInterface::class);

        $saved = $repository->save(new Payment(
            tenantId: $this->tenantId,
            paymentNumber: 'PAY-0001',
            direction: 'inbound',
            partyType: 'customer',
            partyId: $this->customerId,
            paymentMethodId: $paymentMethod->getId(),
            accountId: $cashAccount->getId(),
            amount: 500.0,
            currencyId: $this->currencyId,
            paymentDate: new \DateTimeImmutable('2026-01-10'),
            exchangeRate: 1.0,
            baseAmount: 500.0,
            status: 'posted',
            idempotencyKey: 'idem-001',
        ));

        $found = $repository->find($saved->getId());
        $byNumber = $repository->findByTenantAndNumber($this->tenantId, 'PAY-0001');
        $byIdempotency = $repository->findByTenantAndIdempotencyKey($this->tenantId, 'idem-001');
        $wrongTenant = $repository->findByTenantAndNumber($this->tenant2Id, 'PAY-0001');

        $this->assertNotNull($found);
        $this->assertNotNull($byNumber);
        $this->assertNotNull($byIdempotency);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame($saved->getId(), $byNumber->getId());
        $this->assertSame($saved->getId(), $byIdempotency->getId());
        $this->assertNull($wrongTenant);
    }

    public function test_payment_term_save_find_and_find_by_tenant_and_name(): void
    {
        /** @var PaymentTermRepositoryInterface $repository */
        $repository = app(PaymentTermRepositoryInterface::class);

        $saved = $repository->save(new PaymentTerm(
            tenantId: $this->tenantId,
            name: 'Net 30',
            days: 30,
            isDefault: true,
            isActive: true,
            description: 'Standard net 30 days',
            discountDays: 10,
            discountRate: 2.5,
        ));

        $found = $repository->find($saved->getId());
        $byName = $repository->findByTenantAndName($this->tenantId, 'Net 30');
        $wrongTenant = $repository->findByTenantAndName($this->tenant2Id, 'Net 30');

        $this->assertNotNull($found);
        $this->assertNotNull($byName);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame($saved->getId(), $byName->getId());
        $this->assertNull($wrongTenant);
    }

    public function test_numbering_sequence_save_find_and_generate_next_number(): void
    {
        /** @var NumberingSequenceRepositoryInterface $repository */
        $repository = app(NumberingSequenceRepositoryInterface::class);

        $saved = $repository->save(new NumberingSequence(
            tenantId: $this->tenantId,
            module: 'finance',
            documentType: 'payment',
            prefix: 'PAY-',
            suffix: null,
            nextNumber: 15,
            padding: 4,
            isActive: true,
        ));

        $found = $repository->find($saved->getId());
        $byKey = $repository->findByTenantModuleAndDocumentType($this->tenantId, 'finance', 'payment');
        $wrongTenant = $repository->findByTenantModuleAndDocumentType($this->tenant2Id, 'finance', 'payment');

        $first = $repository->generateNextNumber($this->tenantId, 'finance', 'payment');
        $second = $repository->generateNextNumber($this->tenantId, 'finance', 'payment');

        $this->assertNotNull($found);
        $this->assertNotNull($byKey);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame($saved->getId(), $byKey->getId());
        $this->assertSame('PAY-0015', $first);
        $this->assertSame('PAY-0016', $second);
        $this->assertNull($wrongTenant);
    }

    public function test_cost_center_save_find_and_find_by_tenant_and_code(): void
    {
        /** @var CostCenterRepositoryInterface $repository */
        $repository = app(CostCenterRepositoryInterface::class);

        $saved = $repository->save(new CostCenter(
            tenantId: $this->tenantId,
            code: 'CC-OPS',
            name: 'Operations',
            description: 'Operations cost center',
            isActive: true,
        ));

        $found = $repository->find($saved->getId());
        $byCode = $repository->findByTenantAndCode($this->tenantId, 'CC-OPS');
        $wrongTenant = $repository->findByTenantAndCode($this->tenant2Id, 'CC-OPS');

        $this->assertNotNull($found);
        $this->assertNotNull($byCode);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame($saved->getId(), $byCode->getId());
        $this->assertNull($wrongTenant);
    }

    public function test_ap_transaction_save_find_and_get_supplier_balance(): void
    {
        $apAccount = $this->createAccount($this->tenantId, '2000', 'Accounts Payable', 'liability', 'credit');

        /** @var ApTransactionRepositoryInterface $repository */
        $repository = app(ApTransactionRepositoryInterface::class);

        $saved = $repository->save(new ApTransaction(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            accountId: $apAccount->getId(),
            transactionType: 'bill',
            amount: 1000.0,
            balanceAfter: 1000.0,
            transactionDate: new \DateTimeImmutable('2026-02-01'),
            currencyId: $this->currencyId,
            dueDate: new \DateTimeImmutable('2026-03-01'),
        ));

        $found = $repository->find($saved->getId());
        $balance = $repository->getSupplierBalance($this->tenantId, $this->supplierId);
        $otherBalance = $repository->getSupplierBalance($this->tenant2Id, $this->supplierId);

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('1000', (string) (float) $balance);
        $this->assertSame('0.000000', $otherBalance);
    }

    public function test_ar_transaction_save_find_and_get_customer_balance(): void
    {
        $arAccount = $this->createAccount($this->tenantId, '1200', 'Accounts Receivable', 'asset', 'debit');

        /** @var ArTransactionRepositoryInterface $repository */
        $repository = app(ArTransactionRepositoryInterface::class);

        $saved = $repository->save(new ArTransaction(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            accountId: $arAccount->getId(),
            transactionType: 'invoice',
            amount: 750.0,
            balanceAfter: 750.0,
            transactionDate: new \DateTimeImmutable('2026-02-02'),
            currencyId: $this->currencyId,
            dueDate: new \DateTimeImmutable('2026-03-02'),
        ));

        $found = $repository->find($saved->getId());
        $balance = $repository->getCustomerBalance($this->tenantId, $this->customerId);
        $otherBalance = $repository->getCustomerBalance($this->tenant2Id, $this->customerId);

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('750', (string) (float) $balance);
        $this->assertSame('0.000000', $otherBalance);
    }

    public function test_bank_account_save_and_find(): void
    {
        $cashAccount = $this->createAccount($this->tenantId, '1000', 'Cash', 'asset', 'debit');

        /** @var BankAccountRepositoryInterface $repository */
        $repository = app(BankAccountRepositoryInterface::class);

        $saved = $repository->save(new BankAccount(
            tenantId: $this->tenantId,
            accountId: $cashAccount->getId(),
            name: 'Primary Bank',
            bankName: 'ABC Bank',
            accountNumber: '1234567890',
            currencyId: $this->currencyId,
            routingNumber: '1100001',
            currentBalance: 5000.0,
            feedProvider: 'manual',
            isActive: true,
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('Primary Bank', $found->getName());
    }

    public function test_bank_category_rule_save_and_find(): void
    {
        $expenseAccount = $this->createAccount($this->tenantId, '6100', 'Office Expense', 'expense', 'debit');
        $cashAccount = $this->createAccount($this->tenantId, '1000', 'Cash', 'asset', 'debit');
        $bankAccount = $this->createBankAccount($this->tenantId, $cashAccount->getId());

        /** @var BankCategoryRuleRepositoryInterface $repository */
        $repository = app(BankCategoryRuleRepositoryInterface::class);

        $saved = $repository->save(new BankCategoryRule(
            tenantId: $this->tenantId,
            name: 'Utilities Rule',
            conditions: ['contains' => 'UTILITY'],
            accountId: $expenseAccount->getId(),
            bankAccountId: $bankAccount->getId(),
            priority: 10,
            descriptionTemplate: 'Auto categorized utility expense',
            isActive: true,
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('Utilities Rule', $found->getName());
    }

    public function test_bank_transaction_save_and_find(): void
    {
        $cashAccount = $this->createAccount($this->tenantId, '1000', 'Cash', 'asset', 'debit');
        $expenseAccount = $this->createAccount($this->tenantId, '6100', 'Expense', 'expense', 'debit');
        $bankAccount = $this->createBankAccount($this->tenantId, $cashAccount->getId());
        $rule = $this->createBankCategoryRule($this->tenantId, $bankAccount->getId(), $expenseAccount->getId());

        /** @var BankTransactionRepositoryInterface $repository */
        $repository = app(BankTransactionRepositoryInterface::class);

        $saved = $repository->save(new BankTransaction(
            tenantId: $this->tenantId,
            bankAccountId: $bankAccount->getId(),
            description: 'Utility payment',
            amount: -120.0,
            type: 'debit',
            transactionDate: new \DateTimeImmutable('2026-02-05'),
            externalId: 'EXT-1001',
            balance: 4880.0,
            status: 'categorized',
            categoryRuleId: $rule->getId(),
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('Utility payment', $found->getDescription());
    }

    public function test_bank_reconciliation_save_and_find(): void
    {
        $cashAccount = $this->createAccount($this->tenantId, '1000', 'Cash', 'asset', 'debit');
        $bankAccount = $this->createBankAccount($this->tenantId, $cashAccount->getId());

        /** @var BankReconciliationRepositoryInterface $repository */
        $repository = app(BankReconciliationRepositoryInterface::class);

        $saved = $repository->save(new BankReconciliation(
            tenantId: $this->tenantId,
            bankAccountId: $bankAccount->getId(),
            periodStart: new \DateTimeImmutable('2026-02-01'),
            periodEnd: new \DateTimeImmutable('2026-02-28'),
            openingBalance: 5000.0,
            closingBalance: 4880.0,
            status: 'completed',
            completedBy: $this->userId,
            completedAt: new \DateTimeImmutable('2026-03-01 09:00:00'),
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('completed', $found->getStatus());
    }

    public function test_credit_memo_save_and_find(): void
    {
        /** @var CreditMemoRepositoryInterface $repository */
        $repository = app(CreditMemoRepositoryInterface::class);

        $saved = $repository->save(new CreditMemo(
            tenantId: $this->tenantId,
            partyId: $this->customerId,
            partyType: 'customer',
            creditMemoNumber: 'CM-0001',
            amount: 250.0,
            issuedDate: new \DateTimeImmutable('2026-02-06'),
            status: 'issued',
            notes: 'Price adjustment',
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('CM-0001', $found->getCreditMemoNumber());
    }

    public function test_payment_allocation_save_and_find(): void
    {
        $cashAccount = $this->createAccount($this->tenantId, '1000', 'Cash', 'asset', 'debit');
        $paymentMethod = $this->createPaymentMethod($this->tenantId, $cashAccount->getId(), 'Bank Transfer');

        /** @var PaymentRepositoryInterface $paymentRepository */
        $paymentRepository = app(PaymentRepositoryInterface::class);

        /** @var PaymentAllocationRepositoryInterface $repository */
        $repository = app(PaymentAllocationRepositoryInterface::class);

        $payment = $paymentRepository->save(new Payment(
            tenantId: $this->tenantId,
            paymentNumber: 'PAY-0002',
            direction: 'inbound',
            partyType: 'customer',
            partyId: $this->customerId,
            paymentMethodId: $paymentMethod->getId(),
            accountId: $cashAccount->getId(),
            amount: 900.0,
            currencyId: $this->currencyId,
            paymentDate: new \DateTimeImmutable('2026-02-10'),
            status: 'posted',
            idempotencyKey: 'idem-002',
        ));

        $saved = $repository->save(new PaymentAllocation(
            paymentId: $payment->getId(),
            invoiceType: 'sales_invoice',
            invoiceId: 7001,
            allocatedAmount: 500.0,
            tenantId: $this->tenantId,
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame($payment->getId(), $found->getPaymentId());
    }

    public function test_approval_workflow_config_save_and_find(): void
    {
        /** @var ApprovalWorkflowConfigRepositoryInterface $repository */
        $repository = app(ApprovalWorkflowConfigRepositoryInterface::class);

        $saved = $repository->save(new ApprovalWorkflowConfig(
            tenantId: $this->tenantId,
            module: 'finance',
            entityType: 'payment',
            name: 'Payment Approval',
            steps: [
                ['order' => 1, 'role' => 'manager'],
                ['order' => 2, 'role' => 'finance_head'],
            ],
            minAmount: 1000.0,
            maxAmount: null,
            isActive: true,
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('Payment Approval', $found->getName());
    }

    public function test_approval_request_save_and_find(): void
    {
        /** @var ApprovalWorkflowConfigRepositoryInterface $workflowRepository */
        $workflowRepository = app(ApprovalWorkflowConfigRepositoryInterface::class);

        /** @var ApprovalRequestRepositoryInterface $repository */
        $repository = app(ApprovalRequestRepositoryInterface::class);

        $workflow = $workflowRepository->save(new ApprovalWorkflowConfig(
            tenantId: $this->tenantId,
            module: 'finance',
            entityType: 'payment',
            name: 'Payment Flow',
            steps: [
                ['order' => 1, 'role' => 'manager'],
            ],
            minAmount: null,
            maxAmount: null,
            isActive: true,
        ));

        $saved = $repository->save(new ApprovalRequest(
            tenantId: $this->tenantId,
            workflowConfigId: $workflow->getId(),
            entityType: 'payment',
            entityId: 5001,
            requestedByUserId: $this->userId,
            status: 'pending',
            currentStepOrder: 1,
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame($workflow->getId(), $found->getWorkflowConfigId());
    }

    private function createAccount(
        int $tenantId,
        string $code,
        string $name,
        string $type,
        string $normalBalance,
    ): Account {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);

        return $repository->save(new Account(
            tenantId: $tenantId,
            code: $code,
            name: $name,
            type: $type,
            normalBalance: $normalBalance,
            isActive: true,
        ));
    }

    private function createPaymentMethod(int $tenantId, int $accountId, string $name): PaymentMethod
    {
        /** @var PaymentMethodRepositoryInterface $repository */
        $repository = app(PaymentMethodRepositoryInterface::class);

        return $repository->save(new PaymentMethod(
            tenantId: $tenantId,
            name: $name,
            type: 'bank_transfer',
            accountId: $accountId,
            isActive: true,
        ));
    }

    private function createBankAccount(int $tenantId, int $accountId): BankAccount
    {
        /** @var BankAccountRepositoryInterface $repository */
        $repository = app(BankAccountRepositoryInterface::class);

        return $repository->save(new BankAccount(
            tenantId: $tenantId,
            accountId: $accountId,
            name: 'Main Bank',
            bankName: 'ABC Bank',
            accountNumber: '123-456-789',
            currencyId: $this->currencyId,
            routingNumber: null,
            currentBalance: 0.0,
            feedProvider: 'manual',
            isActive: true,
        ));
    }

    private function createBankCategoryRule(int $tenantId, int $bankAccountId, int $accountId): BankCategoryRule
    {
        /** @var BankCategoryRuleRepositoryInterface $repository */
        $repository = app(BankCategoryRuleRepositoryInterface::class);

        return $repository->save(new BankCategoryRule(
            tenantId: $tenantId,
            name: 'Default Rule',
            conditions: ['contains' => 'PAYMENT'],
            accountId: $accountId,
            bankAccountId: $bankAccountId,
            priority: 1,
            descriptionTemplate: null,
            isActive: true,
        ));
    }

    private function seedReferenceData(): void
    {
        foreach ([$this->tenantId, $this->tenant2Id] as $tenantId) {
            DB::table('tenants')->insert([
                'id' => $tenantId,
                'name' => 'Tenant '.$tenantId,
                'slug' => 'tenant-'.$tenantId,
                'domain' => null,
                'logo_path' => null,
                'database_config' => null,
                'mail_config' => null,
                'cache_config' => null,
                'queue_config' => null,
                'feature_flags' => null,
                'api_keys' => null,
                'settings' => null,
                'plan' => 'free',
                'tenant_plan_id' => null,
                'status' => 'active',
                'active' => true,
                'trial_ends_at' => null,
                'subscription_ends_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);
        }

        DB::table('currencies')->insert([
            'id' => $this->currencyId,
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            [
                'id' => $this->userId,
                'tenant_id' => $this->tenantId,
                'org_unit_id' => null,
                'first_name' => 'User',
                'last_name' => 'One',
                'email' => 'user1@example.com',
                'password' => bcrypt('password'),
                'status' => 'active',
                'preferences' => null,
                'address' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $this->user2Id,
                'tenant_id' => $this->tenant2Id,
                'org_unit_id' => null,
                'first_name' => 'User',
                'last_name' => 'Two',
                'email' => 'user2@example.com',
                'password' => bcrypt('password'),
                'status' => 'active',
                'preferences' => null,
                'address' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('customers')->insert([
            [
                'id' => $this->customerId,
                'tenant_id' => $this->tenantId,
                'org_unit_id' => null,
                'user_id' => null,
                'customer_code' => 'CUST-001',
                'name' => 'Customer One',
                'type' => 'company',
                'tax_number' => null,
                'registration_number' => null,
                'currency_id' => $this->currencyId,
                'credit_limit' => '0.000000',
                'payment_terms_days' => 30,
                'ar_account_id' => null,
                'status' => 'active',
                'notes' => null,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => $this->customer2Id,
                'tenant_id' => $this->tenant2Id,
                'org_unit_id' => null,
                'user_id' => null,
                'customer_code' => 'CUST-002',
                'name' => 'Customer Two',
                'type' => 'company',
                'tax_number' => null,
                'registration_number' => null,
                'currency_id' => $this->currencyId,
                'credit_limit' => '0.000000',
                'payment_terms_days' => 30,
                'ar_account_id' => null,
                'status' => 'active',
                'notes' => null,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);

        DB::table('suppliers')->insert([
            [
                'id' => $this->supplierId,
                'tenant_id' => $this->tenantId,
                'org_unit_id' => null,
                'user_id' => null,
                'supplier_code' => 'SUP-001',
                'name' => 'Supplier One',
                'type' => 'company',
                'tax_number' => null,
                'registration_number' => null,
                'currency_id' => $this->currencyId,
                'payment_terms_days' => 30,
                'ap_account_id' => null,
                'status' => 'active',
                'notes' => null,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => $this->supplier2Id,
                'tenant_id' => $this->tenant2Id,
                'org_unit_id' => null,
                'user_id' => null,
                'supplier_code' => 'SUP-002',
                'name' => 'Supplier Two',
                'type' => 'company',
                'tax_number' => null,
                'registration_number' => null,
                'currency_id' => $this->currencyId,
                'payment_terms_days' => 30,
                'ap_account_id' => null,
                'status' => 'active',
                'notes' => null,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);
    }
}
