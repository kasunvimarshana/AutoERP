<?php

declare(strict_types=1);

namespace Tests\Feature;

use DateTimeImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
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
use Modules\Finance\Application\Contracts\DeleteJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\DeleteNumberingSequenceServiceInterface;
use Modules\Finance\Application\Contracts\DeletePaymentAllocationServiceInterface;
use Modules\Finance\Application\Contracts\DeletePaymentMethodServiceInterface;
use Modules\Finance\Application\Contracts\DeletePaymentServiceInterface;
use Modules\Finance\Application\Contracts\DeletePaymentTermServiceInterface;
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
use Modules\Finance\Application\Contracts\RejectApprovalRequestServiceInterface;
use Modules\Finance\Application\Contracts\ReconcileApTransactionServiceInterface;
use Modules\Finance\Application\Contracts\ReconcileArTransactionServiceInterface;
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
use Modules\Finance\Application\Contracts\UpdateJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\UpdateNumberingSequenceServiceInterface;
use Modules\Finance\Application\Contracts\UpdatePaymentMethodServiceInterface;
use Modules\Finance\Application\Contracts\UpdatePaymentServiceInterface;
use Modules\Finance\Application\Contracts\UpdatePaymentTermServiceInterface;
use Modules\Finance\Application\Contracts\VoidCreditMemoServiceInterface;
use Modules\Finance\Application\Contracts\VoidPaymentServiceInterface;
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
use Modules\Finance\Domain\Entities\JournalEntry;
use Modules\Finance\Domain\Entities\NumberingSequence;
use Modules\Finance\Domain\Entities\Payment;
use Modules\Finance\Domain\Entities\PaymentAllocation;
use Modules\Finance\Domain\Entities\PaymentMethod;
use Modules\Finance\Domain\Entities\PaymentTerm;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

class FinanceEndpointsAuthenticatedTest extends TestCase
{
    private UserModel $authUser;

    private Account $account;

    private PaymentMethod $paymentMethod;

    private PaymentTerm $paymentTerm;

    private CostCenter $costCenter;

    private Payment $payment;

    private PaymentAllocation $paymentAllocation;

    private JournalEntry $journalEntry;

    private CreditMemo $creditMemo;

    private BankAccount $bankAccount;

    private BankCategoryRule $bankCategoryRule;

    private BankTransaction $bankTransaction;

    private BankReconciliation $bankReconciliation;

    private ApprovalWorkflowConfig $approvalWorkflowConfig;

    private ApprovalRequest $approvalRequest;

    private ArTransaction $arTransaction;

    private ApTransaction $apTransaction;

    private NumberingSequence $numberingSequence;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authUser = new UserModel([
            'name'     => 'Finance Admin',
            'email'    => 'finance-admin@example.com',
            'password' => 'hashed',
        ]);
        $this->authUser->setAttribute('id', 99);
        $this->authUser->setAttribute('tenant_id', 1);

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturn(1);
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        $now = new DateTimeImmutable('2026-01-15 10:00:00');

        // Create all test entities
        $this->account = new Account(
            tenantId: 1,
            code: '1000',
            name: 'Cash',
            type: 'asset',
            normalBalance: 'debit',
            id: 1,
        );

        $this->paymentMethod = new PaymentMethod(
            tenantId: 1,
            name: 'Bank Transfer',
            id: 1,
        );

        $this->paymentTerm = new PaymentTerm(
            tenantId: 1,
            name: '30 Days',
            id: 1,
        );

        $this->costCenter = new CostCenter(
            tenantId: 1,
            code: 'CC001',
            name: 'Operations',
            id: 1,
        );

        $this->payment = new Payment(
            tenantId: 1,
            paymentNumber: 'PAY-001',
            direction: 'inbound',
            partyType: 'customer',
            partyId: 2,
            paymentMethodId: 1,
            accountId: 1,
            amount: 100.00,
            currencyId: 1,
            paymentDate: $now,
            id: 1,
        );

        $this->paymentAllocation = new PaymentAllocation(
            paymentId: 1,
            invoiceType: 'sales_invoice',
            invoiceId: 1,
            allocatedAmount: 100.00,
            tenantId: 1,
            id: 1,
        );

        $this->journalEntry = new JournalEntry(
            tenantId: 1,
            fiscalPeriodId: 1,
            entryDate: $now,
            createdBy: 99,
            id: 1,
        );

        $this->creditMemo = new CreditMemo(
            tenantId: 1,
            partyId: 2,
            partyType: 'customer',
            creditMemoNumber: 'CM-001',
            amount: 50.00,
            issuedDate: $now,
            id: 1,
        );

        $this->bankAccount = new BankAccount(
            tenantId: 1,
            accountId: 1,
            name: 'Primary Bank Account',
            bankName: 'Example Bank',
            accountNumber: '1234567890',
            currencyId: 1,
            id: 1,
        );

        $this->bankCategoryRule = new BankCategoryRule(
            tenantId: 1,
            name: 'Salary Rule',
            conditions: ['keyword' => 'salary'],
            accountId: 1,
            id: 1,
        );

        $this->bankTransaction = new BankTransaction(
            tenantId: 1,
            bankAccountId: 1,
            description: 'Deposit',
            amount: 1000.00,
            type: 'credit',
            transactionDate: $now,
            id: 1,
        );

        $this->bankReconciliation = new BankReconciliation(
            tenantId: 1,
            bankAccountId: 1,
            periodStart: $now,
            periodEnd: $now,
            openingBalance: 5000.00,
            closingBalance: 6000.00,
            id: 1,
        );

        $this->approvalWorkflowConfig = new ApprovalWorkflowConfig(
            tenantId: 1,
            module: 'finance',
            entityType: 'payment',
            name: 'Payment Approval',
            steps: [['step' => 1, 'role' => 'manager']],
            id: 1,
        );

        $this->approvalRequest = new ApprovalRequest(
            tenantId: 1,
            workflowConfigId: 1,
            entityType: 'payment',
            entityId: 1,
            requestedByUserId: 99,
            id: 1,
        );

        $this->arTransaction = new ArTransaction(
            tenantId: 1,
            customerId: 2,
            accountId: 1,
            transactionType: 'invoice',
            amount: 100.00,
            balanceAfter: 100.00,
            transactionDate: $now,
            currencyId: 1,
            id: 1,
        );

        $this->apTransaction = new ApTransaction(
            tenantId: 1,
            supplierId: 2,
            accountId: 1,
            transactionType: 'invoice',
            amount: 100.00,
            balanceAfter: 100.00,
            transactionDate: $now,
            currencyId: 1,
            id: 1,
        );

        $this->numberingSequence = new NumberingSequence(
            tenantId: 1,
            module: 'finance',
            documentType: 'invoice',
            id: 1,
        );
    }

    // -------------------------------------------------------------------------
    // Account
    // -------------------------------------------------------------------------

    public function test_account_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->account]);

        $findService = $this->createMock(FindAccountServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindAccountServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/accounts?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_account_store_returns_created(): void
    {
        $createService = $this->createMock(CreateAccountServiceInterface::class);
        $createService->method('execute')->willReturn($this->account);
        $this->app->instance(CreateAccountServiceInterface::class, $createService);

        $findService = $this->createMock(FindAccountServiceInterface::class);
        $this->app->instance(FindAccountServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/accounts', [
                'tenant_id'     => 1,
                'code'          => '1000',
                'name'          => 'Cash',
                'type'          => 'asset',
                'normal_balance' => 'debit',
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_account_show_returns_entity(): void
    {
        $findService = $this->createMock(FindAccountServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->account);
        $this->app->instance(FindAccountServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/accounts/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_account_update_returns_entity(): void
    {
        $findService = $this->createMock(FindAccountServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->account);
        $this->app->instance(FindAccountServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdateAccountServiceInterface::class);
        $updateService->method('execute')->willReturn($this->account);
        $this->app->instance(UpdateAccountServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/accounts/1', [
                'row_version'   => 1,
                'tenant_id'     => 1,
                'code'          => '1000',
                'name'          => 'Cash',
                'type'          => 'asset',
                'normal_balance' => 'debit',
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_account_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindAccountServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->account);
        $this->app->instance(FindAccountServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeleteAccountServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeleteAccountServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/accounts/1');

        $response->assertOk()->assertJsonPath('message', 'Account deleted successfully');
    }

    // -------------------------------------------------------------------------
    // PaymentMethod
    // -------------------------------------------------------------------------

    public function test_payment_method_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->paymentMethod]);

        $findService = $this->createMock(FindPaymentMethodServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindPaymentMethodServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/payment-methods?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_payment_method_store_returns_created(): void
    {
        $createService = $this->createMock(CreatePaymentMethodServiceInterface::class);
        $createService->method('execute')->willReturn($this->paymentMethod);
        $this->app->instance(CreatePaymentMethodServiceInterface::class, $createService);

        $findService = $this->createMock(FindPaymentMethodServiceInterface::class);
        $this->app->instance(FindPaymentMethodServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/payment-methods', [
                'tenant_id' => 1,
                'name'      => 'Bank Transfer',
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_payment_method_show_returns_entity(): void
    {
        $findService = $this->createMock(FindPaymentMethodServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->paymentMethod);
        $this->app->instance(FindPaymentMethodServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/payment-methods/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_payment_method_update_returns_entity(): void
    {
        $findService = $this->createMock(FindPaymentMethodServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->paymentMethod);
        $this->app->instance(FindPaymentMethodServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdatePaymentMethodServiceInterface::class);
        $updateService->method('execute')->willReturn($this->paymentMethod);
        $this->app->instance(UpdatePaymentMethodServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/payment-methods/1', [
                'row_version' => 1,
                'tenant_id' => 1,
                'name'      => 'Bank Transfer',
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_payment_method_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindPaymentMethodServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->paymentMethod);
        $this->app->instance(FindPaymentMethodServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeletePaymentMethodServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeletePaymentMethodServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/payment-methods/1');

        $response->assertOk()->assertJsonPath('message', 'Payment method deleted successfully');
    }

    // -------------------------------------------------------------------------
    // PaymentTerm
    // -------------------------------------------------------------------------

    public function test_payment_term_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->paymentTerm]);

        $findService = $this->createMock(FindPaymentTermServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindPaymentTermServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/payment-terms?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_payment_term_store_returns_created(): void
    {
        $createService = $this->createMock(CreatePaymentTermServiceInterface::class);
        $createService->method('execute')->willReturn($this->paymentTerm);
        $this->app->instance(CreatePaymentTermServiceInterface::class, $createService);

        $findService = $this->createMock(FindPaymentTermServiceInterface::class);
        $this->app->instance(FindPaymentTermServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/payment-terms', [
                'tenant_id' => 1,
                'name'      => '30 Days',
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_payment_term_show_returns_entity(): void
    {
        $findService = $this->createMock(FindPaymentTermServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->paymentTerm);
        $this->app->instance(FindPaymentTermServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/payment-terms/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_payment_term_update_returns_entity(): void
    {
        $findService = $this->createMock(FindPaymentTermServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->paymentTerm);
        $this->app->instance(FindPaymentTermServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdatePaymentTermServiceInterface::class);
        $updateService->method('execute')->willReturn($this->paymentTerm);
        $this->app->instance(UpdatePaymentTermServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/payment-terms/1', [
                'row_version' => 1,
                'tenant_id' => 1,
                'name'      => '30 Days',
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_payment_term_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindPaymentTermServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->paymentTerm);
        $this->app->instance(FindPaymentTermServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeletePaymentTermServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeletePaymentTermServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/payment-terms/1');

        $response->assertOk()->assertJsonPath('message', 'Payment term deleted successfully');
    }

    // -------------------------------------------------------------------------
    // CostCenter
    // -------------------------------------------------------------------------

    public function test_cost_center_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->costCenter]);

        $findService = $this->createMock(FindCostCenterServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindCostCenterServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/cost-centers?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_cost_center_store_returns_created(): void
    {
        $createService = $this->createMock(CreateCostCenterServiceInterface::class);
        $createService->method('execute')->willReturn($this->costCenter);
        $this->app->instance(CreateCostCenterServiceInterface::class, $createService);

        $findService = $this->createMock(FindCostCenterServiceInterface::class);
        $this->app->instance(FindCostCenterServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/cost-centers', [
                'tenant_id' => 1,
                'code'      => 'CC001',
                'name'      => 'Operations',
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_cost_center_show_returns_entity(): void
    {
        $findService = $this->createMock(FindCostCenterServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->costCenter);
        $this->app->instance(FindCostCenterServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/cost-centers/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_cost_center_update_returns_entity(): void
    {
        $findService = $this->createMock(FindCostCenterServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->costCenter);
        $this->app->instance(FindCostCenterServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdateCostCenterServiceInterface::class);
        $updateService->method('execute')->willReturn($this->costCenter);
        $this->app->instance(UpdateCostCenterServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/cost-centers/1', [
                'row_version' => 1,
                'tenant_id' => 1,
                'code'      => 'CC001',
                'name'      => 'Operations',
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_cost_center_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindCostCenterServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->costCenter);
        $this->app->instance(FindCostCenterServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeleteCostCenterServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeleteCostCenterServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/cost-centers/1');

        $response->assertOk()->assertJsonPath('message', 'Cost center deleted successfully');
    }

    // -------------------------------------------------------------------------
    // Payment
    // -------------------------------------------------------------------------

    public function test_payment_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->payment]);

        $findService = $this->createMock(FindPaymentServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindPaymentServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/payments?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_payment_store_returns_created(): void
    {
        $createService = $this->createMock(CreatePaymentServiceInterface::class);
        $createService->method('execute')->willReturn($this->payment);
        $this->app->instance(CreatePaymentServiceInterface::class, $createService);

        $findService = $this->createMock(FindPaymentServiceInterface::class);
        $this->app->instance(FindPaymentServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/payments', [
                'tenant_id'         => 1,
                'payment_number'    => 'PAY-001',
                'direction'         => 'inbound',
                'party_type'        => 'customer',
                'party_id'          => 2,
                'payment_method_id' => 1,
                'account_id'        => 1,
                'amount'            => 100.00,
                'currency_id'       => 1,
                'payment_date'      => '2026-01-15',
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_payment_show_returns_entity(): void
    {
        $findService = $this->createMock(FindPaymentServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->payment);
        $this->app->instance(FindPaymentServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/payments/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_payment_update_returns_entity(): void
    {
        $findService = $this->createMock(FindPaymentServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->payment);
        $this->app->instance(FindPaymentServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdatePaymentServiceInterface::class);
        $updateService->method('execute')->willReturn($this->payment);
        $this->app->instance(UpdatePaymentServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/payments/1', [
                'row_version'       => 1,
                'tenant_id'         => 1,
                'payment_number'    => 'PAY-001',
                'direction'         => 'inbound',
                'party_type'        => 'customer',
                'party_id'          => 2,
                'payment_method_id' => 1,
                'account_id'        => 1,
                'amount'            => 100.00,
                'currency_id'       => 1,
                'payment_date'      => '2026-01-15',
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_payment_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindPaymentServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->payment);
        $this->app->instance(FindPaymentServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeletePaymentServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeletePaymentServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/payments/1');

        $response->assertOk()->assertJsonPath('message', 'Payment deleted successfully');
    }

    public function test_payment_post_returns_entity(): void
    {
        $findService = $this->createMock(FindPaymentServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->payment);
        $this->app->instance(FindPaymentServiceInterface::class, $findService);

        $postService = $this->createMock(PostPaymentServiceInterface::class);
        $postService->method('execute')->willReturn($this->payment);
        $this->app->instance(PostPaymentServiceInterface::class, $postService);

        $response = $this->actingAsUser()
            ->postJson('/api/payments/1/post');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_payment_void_returns_entity(): void
    {
        $findService = $this->createMock(FindPaymentServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->payment);
        $this->app->instance(FindPaymentServiceInterface::class, $findService);

        $voidService = $this->createMock(VoidPaymentServiceInterface::class);
        $voidService->method('execute')->willReturn($this->payment);
        $this->app->instance(VoidPaymentServiceInterface::class, $voidService);

        $response = $this->actingAsUser()
            ->postJson('/api/payments/1/void');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    // -------------------------------------------------------------------------
    // PaymentAllocation
    // -------------------------------------------------------------------------

    public function test_payment_allocation_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->paymentAllocation]);

        $findService = $this->createMock(FindPaymentAllocationServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindPaymentAllocationServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/payment-allocations?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_payment_allocation_store_returns_created(): void
    {
        $createService = $this->createMock(CreatePaymentAllocationServiceInterface::class);
        $createService->method('execute')->willReturn($this->paymentAllocation);
        $this->app->instance(CreatePaymentAllocationServiceInterface::class, $createService);

        $findService = $this->createMock(FindPaymentAllocationServiceInterface::class);
        $this->app->instance(FindPaymentAllocationServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/payment-allocations', [
                'payment_id'       => 1,
                'invoice_type'     => 'sales_invoice',
                'invoice_id'       => 1,
                'allocated_amount' => 100.00,
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_payment_allocation_show_returns_entity(): void
    {
        $findService = $this->createMock(FindPaymentAllocationServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->paymentAllocation);
        $this->app->instance(FindPaymentAllocationServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/payment-allocations/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_payment_allocation_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindPaymentAllocationServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->paymentAllocation);
        $this->app->instance(FindPaymentAllocationServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeletePaymentAllocationServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeletePaymentAllocationServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/payment-allocations/1');

        $response->assertOk()->assertJsonPath('message', 'Payment allocation deleted successfully');
    }

    // -------------------------------------------------------------------------
    // JournalEntry
    // -------------------------------------------------------------------------

    public function test_journal_entry_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->journalEntry]);

        $findService = $this->createMock(FindJournalEntryServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindJournalEntryServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/journal-entries?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_journal_entry_store_returns_created(): void
    {
        $createService = $this->createMock(CreateJournalEntryServiceInterface::class);
        $createService->method('execute')->willReturn($this->journalEntry);
        $this->app->instance(CreateJournalEntryServiceInterface::class, $createService);

        $findService = $this->createMock(FindJournalEntryServiceInterface::class);
        $this->app->instance(FindJournalEntryServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/journal-entries', [
                'tenant_id'       => 1,
                'fiscal_period_id' => 1,
                'entry_date'      => '2026-01-15',
                'created_by'      => 99,
                'lines'           => [
                    ['account_id' => 1, 'debit_amount' => 100.00],
                    ['account_id' => 2, 'credit_amount' => 100.00],
                ],
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_journal_entry_show_returns_entity(): void
    {
        $findService = $this->createMock(FindJournalEntryServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->journalEntry);
        $this->app->instance(FindJournalEntryServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/journal-entries/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_journal_entry_update_returns_entity(): void
    {
        $findService = $this->createMock(FindJournalEntryServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->journalEntry);
        $this->app->instance(FindJournalEntryServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdateJournalEntryServiceInterface::class);
        $updateService->method('execute')->willReturn($this->journalEntry);
        $this->app->instance(UpdateJournalEntryServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/journal-entries/1', [
                'row_version'      => 1,
                'tenant_id'        => 1,
                'fiscal_period_id' => 1,
                'entry_date'       => '2026-01-15',
                'created_by'       => 99,
                'lines'            => [
                    ['account_id' => 1, 'debit_amount' => 100.00],
                    ['account_id' => 2, 'credit_amount' => 100.00],
                ],
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_journal_entry_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindJournalEntryServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->journalEntry);
        $this->app->instance(FindJournalEntryServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeleteJournalEntryServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeleteJournalEntryServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/journal-entries/1');

        $response->assertOk()->assertJsonPath('message', 'Journal entry deleted successfully');
    }

    public function test_journal_entry_post_returns_entity(): void
    {
        $findService = $this->createMock(FindJournalEntryServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->journalEntry);
        $this->app->instance(FindJournalEntryServiceInterface::class, $findService);

        $postService = $this->createMock(PostJournalEntryServiceInterface::class);
        $postService->method('execute')->willReturn($this->journalEntry);
        $this->app->instance(PostJournalEntryServiceInterface::class, $postService);

        $response = $this->actingAsUser()
            ->postJson('/api/journal-entries/1/post', [
                'posted_by' => 99,
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    // -------------------------------------------------------------------------
    // CreditMemo
    // -------------------------------------------------------------------------

    public function test_credit_memo_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->creditMemo]);

        $findService = $this->createMock(FindCreditMemoServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindCreditMemoServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/credit-memos?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_credit_memo_store_returns_created(): void
    {
        $createService = $this->createMock(CreateCreditMemoServiceInterface::class);
        $createService->method('execute')->willReturn($this->creditMemo);
        $this->app->instance(CreateCreditMemoServiceInterface::class, $createService);

        $findService = $this->createMock(FindCreditMemoServiceInterface::class);
        $this->app->instance(FindCreditMemoServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/credit-memos', [
                'tenant_id'            => 1,
                'party_id'             => 2,
                'party_type'           => 'customer',
                'credit_memo_number'   => 'CM-001',
                'amount'               => 50.00,
                'issued_date'          => '2026-01-15',
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_credit_memo_show_returns_entity(): void
    {
        $findService = $this->createMock(FindCreditMemoServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->creditMemo);
        $this->app->instance(FindCreditMemoServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/credit-memos/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_credit_memo_update_returns_entity(): void
    {
        $findService = $this->createMock(FindCreditMemoServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->creditMemo);
        $this->app->instance(FindCreditMemoServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdateCreditMemoServiceInterface::class);
        $updateService->method('execute')->willReturn($this->creditMemo);
        $this->app->instance(UpdateCreditMemoServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/credit-memos/1', [
                'row_version'          => 1,
                'tenant_id'            => 1,
                'party_id'             => 2,
                'party_type'           => 'customer',
                'credit_memo_number'   => 'CM-001',
                'amount'               => 50.00,
                'issued_date'          => '2026-01-15',
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_credit_memo_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindCreditMemoServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->creditMemo);
        $this->app->instance(FindCreditMemoServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeleteCreditMemoServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeleteCreditMemoServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/credit-memos/1');

        $response->assertOk()->assertJsonPath('message', 'Credit memo deleted successfully');
    }

    public function test_credit_memo_issue_returns_entity(): void
    {
        $findService = $this->createMock(FindCreditMemoServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->creditMemo);
        $this->app->instance(FindCreditMemoServiceInterface::class, $findService);

        $issueService = $this->createMock(IssueCreditMemoServiceInterface::class);
        $issueService->method('execute')->willReturn($this->creditMemo);
        $this->app->instance(IssueCreditMemoServiceInterface::class, $issueService);

        $response = $this->actingAsUser()
            ->postJson('/api/credit-memos/1/issue');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_credit_memo_apply_returns_entity(): void
    {
        $findService = $this->createMock(FindCreditMemoServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->creditMemo);
        $this->app->instance(FindCreditMemoServiceInterface::class, $findService);

        $applyService = $this->createMock(ApplyCreditMemoServiceInterface::class);
        $applyService->method('execute')->willReturn($this->creditMemo);
        $this->app->instance(ApplyCreditMemoServiceInterface::class, $applyService);

        $response = $this->actingAsUser()
            ->postJson('/api/credit-memos/1/apply', [
                'invoice_id'   => 1,
                'invoice_type' => 'sales_invoice',
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_credit_memo_void_returns_entity(): void
    {
        $findService = $this->createMock(FindCreditMemoServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->creditMemo);
        $this->app->instance(FindCreditMemoServiceInterface::class, $findService);

        $voidService = $this->createMock(VoidCreditMemoServiceInterface::class);
        $voidService->method('execute')->willReturn($this->creditMemo);
        $this->app->instance(VoidCreditMemoServiceInterface::class, $voidService);

        $response = $this->actingAsUser()
            ->postJson('/api/credit-memos/1/void');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    // -------------------------------------------------------------------------
    // BankAccount
    // -------------------------------------------------------------------------

    public function test_bank_account_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->bankAccount]);

        $findService = $this->createMock(FindBankAccountServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindBankAccountServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/bank-accounts?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_bank_account_store_returns_created(): void
    {
        $createService = $this->createMock(CreateBankAccountServiceInterface::class);
        $createService->method('execute')->willReturn($this->bankAccount);
        $this->app->instance(CreateBankAccountServiceInterface::class, $createService);

        $findService = $this->createMock(FindBankAccountServiceInterface::class);
        $this->app->instance(FindBankAccountServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/bank-accounts', [
                'tenant_id'     => 1,
                'account_id'    => 1,
                'name'          => 'Primary Bank Account',
                'bank_name'     => 'Example Bank',
                'account_number' => '1234567890',
                'currency_id'   => 1,
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_bank_account_show_returns_entity(): void
    {
        $findService = $this->createMock(FindBankAccountServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->bankAccount);
        $this->app->instance(FindBankAccountServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/bank-accounts/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_bank_account_update_returns_entity(): void
    {
        $findService = $this->createMock(FindBankAccountServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->bankAccount);
        $this->app->instance(FindBankAccountServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdateBankAccountServiceInterface::class);
        $updateService->method('execute')->willReturn($this->bankAccount);
        $this->app->instance(UpdateBankAccountServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/bank-accounts/1', [
                'row_version'   => 1,
                'tenant_id'     => 1,
                'account_id'    => 1,
                'name'          => 'Primary Bank Account',
                'bank_name'     => 'Example Bank',
                'account_number' => '1234567890',
                'currency_id'   => 1,
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_bank_account_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindBankAccountServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->bankAccount);
        $this->app->instance(FindBankAccountServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeleteBankAccountServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeleteBankAccountServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/bank-accounts/1');

        $response->assertOk()->assertJsonPath('message', 'Bank account deleted successfully');
    }

    // -------------------------------------------------------------------------
    // BankCategoryRule
    // -------------------------------------------------------------------------

    public function test_bank_category_rule_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->bankCategoryRule]);

        $findService = $this->createMock(FindBankCategoryRuleServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindBankCategoryRuleServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/bank-category-rules?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_bank_category_rule_store_returns_created(): void
    {
        $createService = $this->createMock(CreateBankCategoryRuleServiceInterface::class);
        $createService->method('execute')->willReturn($this->bankCategoryRule);
        $this->app->instance(CreateBankCategoryRuleServiceInterface::class, $createService);

        $findService = $this->createMock(FindBankCategoryRuleServiceInterface::class);
        $this->app->instance(FindBankCategoryRuleServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/bank-category-rules', [
                'tenant_id'  => 1,
                'name'       => 'Salary Rule',
                'conditions' => ['keyword' => 'salary'],
                'account_id' => 1,
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_bank_category_rule_show_returns_entity(): void
    {
        $findService = $this->createMock(FindBankCategoryRuleServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->bankCategoryRule);
        $this->app->instance(FindBankCategoryRuleServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/bank-category-rules/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_bank_category_rule_update_returns_entity(): void
    {
        $findService = $this->createMock(FindBankCategoryRuleServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->bankCategoryRule);
        $this->app->instance(FindBankCategoryRuleServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdateBankCategoryRuleServiceInterface::class);
        $updateService->method('execute')->willReturn($this->bankCategoryRule);
        $this->app->instance(UpdateBankCategoryRuleServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/bank-category-rules/1', [
                'row_version' => 1,
                'tenant_id'  => 1,
                'name'       => 'Salary Rule',
                'conditions' => ['keyword' => 'salary'],
                'account_id' => 1,
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_bank_category_rule_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindBankCategoryRuleServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->bankCategoryRule);
        $this->app->instance(FindBankCategoryRuleServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeleteBankCategoryRuleServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeleteBankCategoryRuleServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/bank-category-rules/1');

        $response->assertOk()->assertJsonPath('message', 'Bank category rule deleted successfully');
    }

    // -------------------------------------------------------------------------
    // BankTransaction
    // -------------------------------------------------------------------------

    public function test_bank_transaction_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->bankTransaction]);

        $findService = $this->createMock(FindBankTransactionServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindBankTransactionServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/bank-transactions?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_bank_transaction_store_returns_created(): void
    {
        $createService = $this->createMock(CreateBankTransactionServiceInterface::class);
        $createService->method('execute')->willReturn($this->bankTransaction);
        $this->app->instance(CreateBankTransactionServiceInterface::class, $createService);

        $findService = $this->createMock(FindBankTransactionServiceInterface::class);
        $this->app->instance(FindBankTransactionServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/bank-transactions', [
                'tenant_id'       => 1,
                'bank_account_id' => 1,
                'description'     => 'Deposit',
                'amount'          => 1000.00,
                'type'            => 'credit',
                'transaction_date' => '2026-01-15',
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_bank_transaction_show_returns_entity(): void
    {
        $findService = $this->createMock(FindBankTransactionServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->bankTransaction);
        $this->app->instance(FindBankTransactionServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/bank-transactions/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_bank_transaction_update_returns_entity(): void
    {
        $findService = $this->createMock(FindBankTransactionServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->bankTransaction);
        $this->app->instance(FindBankTransactionServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdateBankTransactionServiceInterface::class);
        $updateService->method('execute')->willReturn($this->bankTransaction);
        $this->app->instance(UpdateBankTransactionServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/bank-transactions/1', [
                'row_version'      => 1,
                'tenant_id'       => 1,
                'bank_account_id' => 1,
                'description'     => 'Deposit',
                'amount'          => 1000.00,
                'type'            => 'credit',
                'transaction_date' => '2026-01-15',
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_bank_transaction_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindBankTransactionServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->bankTransaction);
        $this->app->instance(FindBankTransactionServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeleteBankTransactionServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeleteBankTransactionServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/bank-transactions/1');

        $response->assertOk()->assertJsonPath('message', 'Bank transaction deleted successfully');
    }

    public function test_bank_transaction_categorize_returns_entity(): void
    {
        $findService = $this->createMock(FindBankTransactionServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->bankTransaction);
        $this->app->instance(FindBankTransactionServiceInterface::class, $findService);

        $categorizeService = $this->createMock(CategorizeBankTransactionServiceInterface::class);
        $categorizeService->method('execute')->willReturn($this->bankTransaction);
        $this->app->instance(CategorizeBankTransactionServiceInterface::class, $categorizeService);

        $response = $this->actingAsUser()
            ->postJson('/api/bank-transactions/1/categorize', [
                'category_rule_id' => 1,
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    // -------------------------------------------------------------------------
    // BankReconciliation
    // -------------------------------------------------------------------------

    public function test_bank_reconciliation_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->bankReconciliation]);

        $findService = $this->createMock(FindBankReconciliationServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindBankReconciliationServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/bank-reconciliations?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_bank_reconciliation_store_returns_created(): void
    {
        $createService = $this->createMock(CreateBankReconciliationServiceInterface::class);
        $createService->method('execute')->willReturn($this->bankReconciliation);
        $this->app->instance(CreateBankReconciliationServiceInterface::class, $createService);

        $findService = $this->createMock(FindBankReconciliationServiceInterface::class);
        $this->app->instance(FindBankReconciliationServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/bank-reconciliations', [
                'tenant_id'      => 1,
                'bank_account_id' => 1,
                'period_start'   => '2026-01-01',
                'period_end'     => '2026-01-31',
                'opening_balance' => 5000.00,
                'closing_balance' => 6000.00,
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_bank_reconciliation_show_returns_entity(): void
    {
        $findService = $this->createMock(FindBankReconciliationServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->bankReconciliation);
        $this->app->instance(FindBankReconciliationServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/bank-reconciliations/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_bank_reconciliation_update_returns_entity(): void
    {
        $findService = $this->createMock(FindBankReconciliationServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->bankReconciliation);
        $this->app->instance(FindBankReconciliationServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdateBankReconciliationServiceInterface::class);
        $updateService->method('execute')->willReturn($this->bankReconciliation);
        $this->app->instance(UpdateBankReconciliationServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/bank-reconciliations/1', [
                'row_version'     => 1,
                'tenant_id'      => 1,
                'bank_account_id' => 1,
                'period_start'   => '2026-01-01',
                'period_end'     => '2026-01-31',
                'opening_balance' => 5000.00,
                'closing_balance' => 6000.00,
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_bank_reconciliation_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindBankReconciliationServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->bankReconciliation);
        $this->app->instance(FindBankReconciliationServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeleteBankReconciliationServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeleteBankReconciliationServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/bank-reconciliations/1');

        $response->assertOk()->assertJsonPath('message', 'Bank reconciliation deleted successfully');
    }

    public function test_bank_reconciliation_complete_returns_entity(): void
    {
        $findService = $this->createMock(FindBankReconciliationServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->bankReconciliation);
        $this->app->instance(FindBankReconciliationServiceInterface::class, $findService);

        $completeService = $this->createMock(CompleteBankReconciliationServiceInterface::class);
        $completeService->method('execute')->willReturn($this->bankReconciliation);
        $this->app->instance(CompleteBankReconciliationServiceInterface::class, $completeService);

        $response = $this->actingAsUser()
            ->postJson('/api/bank-reconciliations/1/complete', [
                'completed_by' => 99,
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    // -------------------------------------------------------------------------
    // ApprovalWorkflowConfig
    // -------------------------------------------------------------------------

    public function test_approval_workflow_config_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->approvalWorkflowConfig]);

        $findService = $this->createMock(FindApprovalWorkflowConfigServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindApprovalWorkflowConfigServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/approval-workflow-configs?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_approval_workflow_config_store_returns_created(): void
    {
        $createService = $this->createMock(CreateApprovalWorkflowConfigServiceInterface::class);
        $createService->method('execute')->willReturn($this->approvalWorkflowConfig);
        $this->app->instance(CreateApprovalWorkflowConfigServiceInterface::class, $createService);

        $findService = $this->createMock(FindApprovalWorkflowConfigServiceInterface::class);
        $this->app->instance(FindApprovalWorkflowConfigServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/approval-workflow-configs', [
                'tenant_id'   => 1,
                'module'      => 'finance',
                'entity_type' => 'payment',
                'name'        => 'Payment Approval',
                'steps'       => [['step' => 1, 'role' => 'manager']],
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_approval_workflow_config_show_returns_entity(): void
    {
        $findService = $this->createMock(FindApprovalWorkflowConfigServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->approvalWorkflowConfig);
        $this->app->instance(FindApprovalWorkflowConfigServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/approval-workflow-configs/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_approval_workflow_config_update_returns_entity(): void
    {
        $findService = $this->createMock(FindApprovalWorkflowConfigServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->approvalWorkflowConfig);
        $this->app->instance(FindApprovalWorkflowConfigServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdateApprovalWorkflowConfigServiceInterface::class);
        $updateService->method('execute')->willReturn($this->approvalWorkflowConfig);
        $this->app->instance(UpdateApprovalWorkflowConfigServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/approval-workflow-configs/1', [
                'row_version' => 1,
                'tenant_id'   => 1,
                'module'      => 'finance',
                'entity_type' => 'payment',
                'name'        => 'Payment Approval',
                'steps'       => [['step' => 1, 'role' => 'manager']],
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_approval_workflow_config_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindApprovalWorkflowConfigServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->approvalWorkflowConfig);
        $this->app->instance(FindApprovalWorkflowConfigServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeleteApprovalWorkflowConfigServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeleteApprovalWorkflowConfigServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/approval-workflow-configs/1');

        $response->assertOk()->assertJsonPath('message', 'Approval workflow config deleted successfully');
    }

    // -------------------------------------------------------------------------
    // ApprovalRequest
    // -------------------------------------------------------------------------

    public function test_approval_request_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->approvalRequest]);

        $findService = $this->createMock(FindApprovalRequestServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindApprovalRequestServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/approval-requests?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_approval_request_store_returns_created(): void
    {
        $createService = $this->createMock(CreateApprovalRequestServiceInterface::class);
        $createService->method('execute')->willReturn($this->approvalRequest);
        $this->app->instance(CreateApprovalRequestServiceInterface::class, $createService);

        $findService = $this->createMock(FindApprovalRequestServiceInterface::class);
        $this->app->instance(FindApprovalRequestServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/approval-requests', [
                'tenant_id'           => 1,
                'workflow_config_id'  => 1,
                'entity_type'         => 'payment',
                'entity_id'           => 1,
                'requested_by_user_id' => 99,
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_approval_request_show_returns_entity(): void
    {
        $findService = $this->createMock(FindApprovalRequestServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->approvalRequest);
        $this->app->instance(FindApprovalRequestServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/approval-requests/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_approval_request_update_returns_entity(): void
    {
        $findService = $this->createMock(FindApprovalRequestServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->approvalRequest);
        $this->app->instance(FindApprovalRequestServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdateApprovalRequestServiceInterface::class);
        $updateService->method('execute')->willReturn($this->approvalRequest);
        $this->app->instance(UpdateApprovalRequestServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/approval-requests/1', [
                'row_version'           => 1,
                'tenant_id'             => 1,
                'workflow_config_id'    => 1,
                'entity_type'           => 'payment',
                'entity_id'             => 1,
                'requested_by_user_id'  => 99,
                'status'                => 'pending',
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_approval_request_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindApprovalRequestServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->approvalRequest);
        $this->app->instance(FindApprovalRequestServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeleteApprovalRequestServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeleteApprovalRequestServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/approval-requests/1');

        $response->assertOk()->assertJsonPath('message', 'Approval request deleted successfully');
    }

    public function test_approval_request_approve_returns_entity(): void
    {
        $findService = $this->createMock(FindApprovalRequestServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->approvalRequest);
        $this->app->instance(FindApprovalRequestServiceInterface::class, $findService);

        $approveService = $this->createMock(ApproveApprovalRequestServiceInterface::class);
        $approveService->method('execute')->willReturn($this->approvalRequest);
        $this->app->instance(ApproveApprovalRequestServiceInterface::class, $approveService);

        $response = $this->actingAsUser()
            ->postJson('/api/approval-requests/1/approve', [
                'resolved_by_user_id' => 99,
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_approval_request_reject_returns_entity(): void
    {
        $findService = $this->createMock(FindApprovalRequestServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->approvalRequest);
        $this->app->instance(FindApprovalRequestServiceInterface::class, $findService);

        $rejectService = $this->createMock(RejectApprovalRequestServiceInterface::class);
        $rejectService->method('execute')->willReturn($this->approvalRequest);
        $this->app->instance(RejectApprovalRequestServiceInterface::class, $rejectService);

        $response = $this->actingAsUser()
            ->postJson('/api/approval-requests/1/reject', [
                'resolved_by_user_id' => 99,
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_approval_request_cancel_returns_entity(): void
    {
        $findService = $this->createMock(FindApprovalRequestServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->approvalRequest);
        $this->app->instance(FindApprovalRequestServiceInterface::class, $findService);

        $cancelService = $this->createMock(CancelApprovalRequestServiceInterface::class);
        $cancelService->method('execute')->willReturn($this->approvalRequest);
        $this->app->instance(CancelApprovalRequestServiceInterface::class, $cancelService);

        $response = $this->actingAsUser()
            ->postJson('/api/approval-requests/1/cancel');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    // -------------------------------------------------------------------------
    // ArTransaction
    // -------------------------------------------------------------------------

    public function test_ar_transaction_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->arTransaction]);

        $findService = $this->createMock(FindArTransactionServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindArTransactionServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/ar-transactions?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_ar_transaction_store_returns_created(): void
    {
        $createService = $this->createMock(CreateArTransactionServiceInterface::class);
        $createService->method('execute')->willReturn($this->arTransaction);
        $this->app->instance(CreateArTransactionServiceInterface::class, $createService);

        $findService = $this->createMock(FindArTransactionServiceInterface::class);
        $this->app->instance(FindArTransactionServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/ar-transactions', [
                'tenant_id'         => 1,
                'customer_id'       => 2,
                'account_id'        => 1,
                'transaction_type'  => 'invoice',
                'amount'            => 100.00,
                'balance_after'     => 100.00,
                'transaction_date'  => '2026-01-15',
                'currency_id'       => 1,
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_ar_transaction_show_returns_entity(): void
    {
        $findService = $this->createMock(FindArTransactionServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->arTransaction);
        $this->app->instance(FindArTransactionServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/ar-transactions/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_ar_transaction_update_returns_entity(): void
    {
        $findService = $this->createMock(FindArTransactionServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->arTransaction);
        $this->app->instance(FindArTransactionServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdateArTransactionServiceInterface::class);
        $updateService->method('execute')->willReturn($this->arTransaction);
        $this->app->instance(UpdateArTransactionServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/ar-transactions/1', [
                'row_version'       => 1,
                'tenant_id'         => 1,
                'customer_id'       => 2,
                'account_id'        => 1,
                'transaction_type'  => 'invoice',
                'amount'            => 100.00,
                'balance_after'     => 100.00,
                'transaction_date'  => '2026-01-15',
                'currency_id'       => 1,
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_ar_transaction_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindArTransactionServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->arTransaction);
        $this->app->instance(FindArTransactionServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeleteArTransactionServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeleteArTransactionServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/ar-transactions/1');

        $response->assertOk()->assertJsonPath('message', 'AR transaction deleted successfully');
    }

    public function test_ar_transaction_reconcile_returns_entity(): void
    {
        $findService = $this->createMock(FindArTransactionServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->arTransaction);
        $this->app->instance(FindArTransactionServiceInterface::class, $findService);

        $reconcileService = $this->createMock(ReconcileArTransactionServiceInterface::class);
        $reconcileService->method('execute')->willReturn($this->arTransaction);
        $this->app->instance(ReconcileArTransactionServiceInterface::class, $reconcileService);

        $response = $this->actingAsUser()
            ->postJson('/api/ar-transactions/1/reconcile');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    // -------------------------------------------------------------------------
    // ApTransaction
    // -------------------------------------------------------------------------

    public function test_ap_transaction_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->apTransaction]);

        $findService = $this->createMock(FindApTransactionServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindApTransactionServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/ap-transactions?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_ap_transaction_store_returns_created(): void
    {
        $createService = $this->createMock(CreateApTransactionServiceInterface::class);
        $createService->method('execute')->willReturn($this->apTransaction);
        $this->app->instance(CreateApTransactionServiceInterface::class, $createService);

        $findService = $this->createMock(FindApTransactionServiceInterface::class);
        $this->app->instance(FindApTransactionServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/ap-transactions', [
                'tenant_id'         => 1,
                'supplier_id'       => 2,
                'account_id'        => 1,
                'transaction_type'  => 'bill',
                'amount'            => 100.00,
                'balance_after'     => 100.00,
                'transaction_date'  => '2026-01-15',
                'currency_id'       => 1,
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_ap_transaction_show_returns_entity(): void
    {
        $findService = $this->createMock(FindApTransactionServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->apTransaction);
        $this->app->instance(FindApTransactionServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/ap-transactions/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_ap_transaction_update_returns_entity(): void
    {
        $findService = $this->createMock(FindApTransactionServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->apTransaction);
        $this->app->instance(FindApTransactionServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdateApTransactionServiceInterface::class);
        $updateService->method('execute')->willReturn($this->apTransaction);
        $this->app->instance(UpdateApTransactionServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/ap-transactions/1', [
                'row_version'       => 1,
                'tenant_id'         => 1,
                'supplier_id'       => 2,
                'account_id'        => 1,
                'transaction_type'  => 'bill',
                'amount'            => 100.00,
                'balance_after'     => 100.00,
                'transaction_date'  => '2026-01-15',
                'currency_id'       => 1,
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_ap_transaction_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindApTransactionServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->apTransaction);
        $this->app->instance(FindApTransactionServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeleteApTransactionServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeleteApTransactionServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/ap-transactions/1');

        $response->assertOk()->assertJsonPath('message', 'AP transaction deleted successfully');
    }

    public function test_ap_transaction_reconcile_returns_entity(): void
    {
        $findService = $this->createMock(FindApTransactionServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->apTransaction);
        $this->app->instance(FindApTransactionServiceInterface::class, $findService);

        $reconcileService = $this->createMock(ReconcileApTransactionServiceInterface::class);
        $reconcileService->method('execute')->willReturn($this->apTransaction);
        $this->app->instance(ReconcileApTransactionServiceInterface::class, $reconcileService);

        $response = $this->actingAsUser()
            ->postJson('/api/ap-transactions/1/reconcile');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    // -------------------------------------------------------------------------
    // NumberingSequence
    // -------------------------------------------------------------------------

    public function test_numbering_sequence_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->numberingSequence]);

        $findService = $this->createMock(FindNumberingSequenceServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindNumberingSequenceServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/numbering-sequences?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_numbering_sequence_store_returns_created(): void
    {
        $createService = $this->createMock(CreateNumberingSequenceServiceInterface::class);
        $createService->method('execute')->willReturn($this->numberingSequence);
        $this->app->instance(CreateNumberingSequenceServiceInterface::class, $createService);

        $findService = $this->createMock(FindNumberingSequenceServiceInterface::class);
        $this->app->instance(FindNumberingSequenceServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/numbering-sequences', [
                'tenant_id'     => 1,
                'module'        => 'finance',
                'document_type' => 'invoice',
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_numbering_sequence_show_returns_entity(): void
    {
        $findService = $this->createMock(FindNumberingSequenceServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->numberingSequence);
        $this->app->instance(FindNumberingSequenceServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/numbering-sequences/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_numbering_sequence_update_returns_entity(): void
    {
        $findService = $this->createMock(FindNumberingSequenceServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->numberingSequence);
        $this->app->instance(FindNumberingSequenceServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdateNumberingSequenceServiceInterface::class);
        $updateService->method('execute')->willReturn($this->numberingSequence);
        $this->app->instance(UpdateNumberingSequenceServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/numbering-sequences/1', [
                'row_version'   => 1,
                'tenant_id'     => 1,
                'module'        => 'finance',
                'document_type' => 'invoice',
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_numbering_sequence_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindNumberingSequenceServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->numberingSequence);
        $this->app->instance(FindNumberingSequenceServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeleteNumberingSequenceServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeleteNumberingSequenceServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/numbering-sequences/1');

        $response->assertOk()->assertJsonPath('message', 'Numbering sequence deleted successfully');
    }

    public function test_numbering_sequence_next_returns_message(): void
    {
        $findService = $this->createMock(FindNumberingSequenceServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->numberingSequence);
        $this->app->instance(FindNumberingSequenceServiceInterface::class, $findService);

        $nextService = $this->createMock(NextNumberingSequenceServiceInterface::class);
        $nextService->method('execute')->willReturn([
            'number' => 'INV-00001',
            'sequence' => $this->numberingSequence,
        ]);
        $this->app->instance(NextNumberingSequenceServiceInterface::class, $nextService);

        $response = $this->actingAsUser()
            ->postJson('/api/numbering-sequences/1/next');

        $response->assertOk();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function actingAsUser(): static
    {
        return $this->withHeader('X-Tenant-ID', '1')->actingAs(
            $this->authUser,
            (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api'))
        );
    }

    private function makePaginator(array $items): LengthAwarePaginator
    {
        return new LengthAwarePaginator($items, count($items), 15, 1);
    }
}
