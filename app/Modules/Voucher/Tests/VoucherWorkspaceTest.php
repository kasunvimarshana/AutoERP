<?php

declare(strict_types=1);

namespace Modules\Voucher\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Finance\DTOs\CreateJournalEntryData;
use Modules\Finance\DTOs\JournalLineData;
use Modules\Finance\Enums\JournalType;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Services\JournalEntryCreationService;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\DTOs\PaymentReversalData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentReversalService;
use Tests\TestCase;

final class VoucherWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_receipt_and_payment_vouchers_resolve_from_payment_sources_with_separate_sequences(): void
    {
        $tenantId = $this->createTenant();

        $receipt = $this->payment($tenantId, PaymentDirection::Inbound, PaymentType::CustomerReceipt, '1000.000000');
        $payment = $this->payment($tenantId, PaymentDirection::Outbound, PaymentType::SupplierPayment, '750.000000');

        $this->assertStringStartsWith('RV-2026-', (string) $receipt->payment_number);
        $this->assertStringStartsWith('PV-2026-', (string) $payment->payment_number);

        $this->getJson('/api/v1/vouchers?'.http_build_query(['tenant_id' => $tenantId]))
            ->assertSuccessful()
            ->assertJsonPath('data.0.voucher_type', 'payment_voucher')
            ->assertJsonPath('data.1.voucher_type', 'receipt_voucher');

        $this->getJson('/api/v1/vouchers/receipt_voucher/'.$receipt->getKey().'?'.http_build_query([
            'tenant_id' => $tenantId,
            'source_kind' => 'payment',
        ]))
            ->assertSuccessful()
            ->assertJsonPath('data.voucher_type', 'receipt_voucher')
            ->assertJsonPath('data.source_module', 'Payment')
            ->assertJsonPath('data.transaction_amount', '1000.000000')
            ->assertJsonPath('data.allocation_status', 'unallocated');

        $this->getJson('/api/v1/vouchers/payment_voucher/'.$payment->getKey().'?'.http_build_query([
            'tenant_id' => $tenantId,
            'source_kind' => 'payment',
        ]))
            ->assertSuccessful()
            ->assertJsonPath('data.voucher_type', 'payment_voucher')
            ->assertJsonPath('data.source_module', 'Payment')
            ->assertJsonPath('data.transaction_amount', '750.000000');
    }

    public function test_finance_voucher_types_resolve_only_from_matching_finance_journal_sources(): void
    {
        [$tenantId, $cashId, $capitalId] = $this->financeContext();

        $journal = $this->journal($tenantId, $cashId, $capitalId, JournalType::General);
        $contra = $this->journal($tenantId, $cashId, $capitalId, JournalType::Contra);
        $adjustment = $this->journal($tenantId, $cashId, $capitalId, JournalType::Adjustment);
        $opening = $this->journal($tenantId, $cashId, $capitalId, JournalType::Opening);

        $this->assertStringStartsWith('JV-2026-', (string) $journal->journal_number);
        $this->assertStringStartsWith('CV-2026-', (string) $contra->journal_number);
        $this->assertStringStartsWith('AV-2026-', (string) $adjustment->journal_number);
        $this->assertStringStartsWith('OBV-2026-', (string) $opening->journal_number);

        $this->getJson('/api/v1/vouchers/contra_voucher/'.$contra->getKey().'?'.http_build_query([
            'tenant_id' => $tenantId,
            'source_kind' => 'finance_journal',
        ]))
            ->assertSuccessful()
            ->assertJsonPath('data.voucher_type', 'contra_voucher')
            ->assertJsonPath('data.source_module', 'Finance')
            ->assertJsonCount(2, 'data.journal_lines');

        $this->getJson('/api/v1/vouchers/contra_voucher/'.$journal->getKey().'?'.http_build_query([
            'tenant_id' => $tenantId,
            'source_kind' => 'finance_journal',
        ]))->assertNotFound();
    }

    public function test_reversal_voucher_resolves_from_payment_reversal_without_duplicate_reversal(): void
    {
        $tenantId = $this->createTenant();
        $payment = $this->payment($tenantId, PaymentDirection::Inbound, PaymentType::CustomerReceipt, '200.000000');

        $reversal = app(PaymentReversalService::class)->reverse(new PaymentReversalData(
            paymentId: (int) $payment->getKey(),
            reversalNumber: '',
            reversalDate: '2026-06-15',
            reason: 'Wrong receipt',
        ));

        $this->assertStringStartsWith('REV-2026-', (string) $reversal->reversal_number);
        $this->assertSame('reversed', (string) $payment->refresh()->document_status->value);

        $this->getJson('/api/v1/vouchers/reversal_voucher/'.$reversal->getKey().'?'.http_build_query([
            'tenant_id' => $tenantId,
            'source_kind' => 'payment_reversal',
        ]))
            ->assertSuccessful()
            ->assertJsonPath('data.voucher_type', 'reversal_voucher')
            ->assertJsonPath('data.source_kind', 'payment_reversal')
            ->assertJsonPath('data.reversal_information.original_number', (string) $payment->payment_number);
    }

    public function test_tenant_scope_and_allowlisted_types_are_enforced(): void
    {
        $tenantId = $this->createTenant();
        $otherTenantId = $this->createTenant('OTHER');
        $receipt = $this->payment($tenantId, PaymentDirection::Inbound, PaymentType::CustomerReceipt, '100.000000');

        $this->getJson('/api/v1/vouchers/receipt_voucher/'.$receipt->getKey().'?'.http_build_query([
            'tenant_id' => $otherTenantId,
            'source_kind' => 'payment',
        ]))->assertNotFound();

        $this->getJson('/api/v1/vouchers/voucher_lines/'.$receipt->getKey().'?'.http_build_query([
            'tenant_id' => $tenantId,
        ]))->assertNotFound();
    }

    private function payment(int $tenantId, PaymentDirection $direction, PaymentType $type, string $amount): Payment
    {
        return app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: $type,
            direction: $direction,
            paymentDate: '2026-06-15',
            partyType: $direction === PaymentDirection::Inbound ? 'customer' : 'supplier',
            payeeName: $direction === PaymentDirection::Inbound ? 'Walk-in customer' : 'Acme Supplier',
            lines: [
                new PaymentLineData(amount: $amount),
            ],
        ));
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function financeContext(): array
    {
        $tenantId = $this->createTenant();
        $assetType = $this->accountType($tenantId, 'ASSET', 'debit');
        $equityType = $this->accountType($tenantId, 'EQUITY', 'credit');
        $cashId = $this->account($tenantId, $assetType, '1010', 'Cash', 'debit');
        $capitalId = $this->account($tenantId, $equityType, '3000', 'Capital', 'credit');
        $yearId = (int) DB::table('finance_fiscal_years')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('finance_fiscal_periods')->insert([
            'tenant_id' => $tenantId,
            'fiscal_year_id' => $yearId,
            'name' => 'June 2026',
            'period_number' => 6,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenantId, $cashId, $capitalId];
    }

    private function journal(int $tenantId, int $debitAccountId, int $creditAccountId, JournalType $type): FinanceJournalEntry
    {
        return app(JournalEntryCreationService::class)->create(new CreateJournalEntryData(
            tenantId: $tenantId,
            journalDate: '2026-06-15',
            journalType: $type,
            description: $type->value.' voucher test',
            lines: [
                new JournalLineData(accountId: $debitAccountId, lineNumber: 1, debit: '500.000000', description: 'Debit'),
                new JournalLineData(accountId: $creditAccountId, lineNumber: 2, credit: '500.000000', description: 'Credit'),
            ],
        ));
    }

    private function createTenant(string $suffix = ''): int
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(6));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-VOU-'.$suffix,
            'name' => 'Voucher Tenant '.$suffix,
            'slug' => 'voucher-tenant-'.Str::lower($suffix),
            'status' => 'active',
            'is_active' => true,
            'is_isolated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function accountType(int $tenantId, string $code, string $normalBalance): int
    {
        return (int) DB::table('finance_account_types')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => $code.'-'.Str::upper(Str::random(4)),
            'name' => $code,
            'normal_balance' => $normalBalance,
            'statement_type' => 'balance_sheet',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function account(int $tenantId, int $typeId, string $code, string $name, string $normalBalance): int
    {
        return (int) DB::table('finance_accounts')->insertGetId([
            'tenant_id' => $tenantId,
            'account_type_id' => $typeId,
            'code' => $code.'-'.Str::upper(Str::random(4)),
            'name' => $name,
            'normal_balance' => $normalBalance,
            'opening_balance' => '0.000000',
            'current_balance' => '0.000000',
            'is_control_account' => false,
            'is_posting_account' => true,
            'is_cash_account' => $code === '1010',
            'is_bank_account' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
