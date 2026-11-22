<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\DTOs\UpdatePaymentData;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentInstrumentStatus;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentLine;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Services\PaymentDraftDeletionService;
use Modules\Payment\Services\PaymentUpdateService;
use Tests\TestCase;

final class PaymentCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_payment_can_be_updated(): void
    {
        [$payment, $method1, $method2] = $this->fixture();

        $updateService = app(PaymentUpdateService::class);
        $updated = $this->withTenantExecutionContext((int) $payment->tenant_id, fn () => $updateService->update(
            $payment,
            new UpdatePaymentData(
                expectedVersion: (int) $payment->row_version,
                paymentDate: '2026-11-20',
                partyType: 'customer',
                partyId: (int) $payment->party_id,
                currencyId: null,
                exchangeRate: '1.000000',
                referenceNumber: 'REF-UPDATED-999',
                chequeNumber: null,
                chequeDate: null,
                payeeName: null,
                notes: 'Updated notes',
                updatedBy: null,
                lines: [
                    new PaymentLineData(
                        paymentMethodId: (int) $method2->id,
                        amount: '25000.000000',
                        referenceNumber: 'LINE-REF-2',
                    ),
                ],
                allocations: [],
            )
        ));

        self::assertSame('2026-11-20', $updated->payment_date->toDateString());
        self::assertSame('REF-UPDATED-999', $updated->reference_number);
        self::assertSame('Updated notes', $updated->notes);
        self::assertSame('25000.000000', (string) $updated->total_amount);
        self::assertSame('25000.000000', (string) $updated->unapplied_amount);
        self::assertSame(2, (int) $updated->row_version);
        self::assertCount(1, $updated->lines);
        self::assertSame((int) $method2->id, (int) $updated->lines->first()->payment_method_id);
    }

    public function test_updating_approved_or_posted_payment_is_rejected(): void
    {
        [$payment, $method1] = $this->fixture();
        $this->withTenantExecutionContext((int) $payment->tenant_id, function () use ($payment): void {
            $payment->forceFill(['document_status' => PaymentDocumentStatus::Approved->value])->save();
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only draft payments can be modified.');

        $updateService = app(PaymentUpdateService::class);
        $this->withTenantExecutionContext((int) $payment->tenant_id, fn () => $updateService->update(
            $payment,
            new UpdatePaymentData(
                expectedVersion: (int) $payment->row_version,
                paymentDate: '2026-11-20',
                partyType: 'customer',
                partyId: (int) $payment->party_id,
                currencyId: null,
                exchangeRate: '1.000000',
                referenceNumber: 'REF-ERR',
                chequeNumber: null,
                chequeDate: null,
                payeeName: null,
                notes: null,
                updatedBy: null,
                lines: [
                    new PaymentLineData(
                        paymentMethodId: (int) $method1->id,
                        amount: '10000.000000',
                    ),
                ],
                allocations: [],
            )
        ));
    }

    public function test_updating_payment_with_stale_version_is_rejected(): void
    {
        [$payment, $method1] = $this->fixture();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment was changed by another request. Reload it before updating.');

        $updateService = app(PaymentUpdateService::class);
        $this->withTenantExecutionContext((int) $payment->tenant_id, fn () => $updateService->update(
            $payment,
            new UpdatePaymentData(
                expectedVersion: 999,
                paymentDate: '2026-11-20',
                partyType: 'customer',
                partyId: (int) $payment->party_id,
                currencyId: null,
                exchangeRate: '1.000000',
                referenceNumber: 'REF-ERR',
                chequeNumber: null,
                chequeDate: null,
                payeeName: null,
                notes: null,
                updatedBy: null,
                lines: [
                    new PaymentLineData(
                        paymentMethodId: (int) $method1->id,
                        amount: '10000.000000',
                    ),
                ],
                allocations: [],
            )
        ));
    }

    public function test_draft_payment_can_be_deleted(): void
    {
        [$payment] = $this->fixture();
        $paymentId = $payment->id;

        $deletionService = app(PaymentDraftDeletionService::class);
        $this->withTenantExecutionContext((int) $payment->tenant_id, fn () => $deletionService->delete(
            $payment,
            (int) $payment->row_version,
        ));

        self::assertSoftDeleted('payments', ['id' => $paymentId]);
        self::assertDatabaseMissing('payment_lines', ['payment_id' => $paymentId]);
    }

    public function test_deleting_approved_payment_is_rejected(): void
    {
        [$payment] = $this->fixture();
        $this->withTenantExecutionContext((int) $payment->tenant_id, function () use ($payment): void {
            $payment->forceFill(['document_status' => PaymentDocumentStatus::Approved->value])->save();
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only draft payments can be deleted.');

        $deletionService = app(PaymentDraftDeletionService::class);
        $this->withTenantExecutionContext((int) $payment->tenant_id, fn () => $deletionService->delete(
            $payment,
            (int) $payment->row_version,
        ));
    }

    public function test_deleting_payment_with_stale_version_is_rejected(): void
    {
        [$payment] = $this->fixture();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment was changed by another request. Reload it before deleting.');

        $deletionService = app(PaymentDraftDeletionService::class);
        $this->withTenantExecutionContext((int) $payment->tenant_id, fn () => $deletionService->delete(
            $payment,
            999,
        ));
    }

    public function test_draft_payment_deletion_records_lifecycle_audit_trail(): void
    {
        [$payment] = $this->fixture();
        $paymentId = $payment->id;

        $userId = DB::table('users')->insertGetId([
            'tenant_id' => $payment->tenant_id,
            'first_name' => 'Operator',
            'last_name' => 'Alice',
            'email' => 'alice-'.Str::random(6).'@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $deletionService = app(PaymentDraftDeletionService::class);
        $this->withTenantExecutionContext((int) $payment->tenant_id, fn () => $deletionService->delete(
            $payment,
            (int) $payment->row_version,
            actorId: $userId,
            reason: 'Cancelled duplicate draft entry',
        ));

        self::assertSoftDeleted('payments', ['id' => $paymentId]);
        self::assertDatabaseHas('payment_lifecycle_events', [
            'payment_id' => $paymentId,
            'dimension' => 'document',
            'to_state' => 'deleted',
            'changed_by' => $userId,
            'reason' => 'Cancelled duplicate draft entry',
        ]);
    }

    public function test_draft_payment_update_clearing_allocations_syncs_allocation_status(): void
    {
        [$payment, $method1] = $this->fixture();

        $updateService = app(PaymentUpdateService::class);
        $updated = $this->withTenantExecutionContext((int) $payment->tenant_id, fn () => $updateService->update(
            $payment,
            new UpdatePaymentData(
                expectedVersion: (int) $payment->row_version,
                paymentDate: '2026-11-22',
                partyType: 'customer',
                partyId: (int) $payment->party_id,
                currencyId: null,
                exchangeRate: '1.000000',
                referenceNumber: 'REF-CLEARED',
                chequeNumber: null,
                chequeDate: null,
                payeeName: null,
                notes: 'Allocations cleared',
                updatedBy: 99,
                lines: [
                    new PaymentLineData(
                        paymentMethodId: (int) $method1->id,
                        amount: '15000.000000',
                    ),
                ],
                allocations: [],
            )
        ));

        self::assertSame(PaymentAllocationState::Unallocated->value, $updated->allocation_status->value ?? $updated->allocation_status);
        self::assertSame('15000.000000', (string) $updated->unapplied_amount);
        self::assertSame('0.000000', (string) $updated->allocated_amount);
    }

    public function test_payment_method_cannot_be_deleted_when_referenced_by_expenses(): void
    {
        $tenantId = $this->tenantId();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $method = PaymentMethod::query()->create([
                'tenant_id' => $tenantId,
                'code' => 'EXP-CASH',
                'name' => 'Expense Cash Method',
                'method_type' => PaymentMethodType::Cash,
                'requires_reference' => false,
                'requires_instrument_details' => false,
                'is_active' => true,
            ]);

            $orgId = (int) \Tests\Support\OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Expense Branch',
                'code' => 'EXPB-'.Str::upper(Str::random(4)),
            ]);

            $expenseTypeId = DB::table('expense_types')->insertGetId([
                'tenant_id' => $tenantId,
                'code' => 'OFFICE-SUPPLIES',
                'name' => 'Office Supplies',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('expenses')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expense_type_id' => $expenseTypeId,
                'expense_number' => 'EXP-'.Str::upper(Str::random(6)),
                'expense_date' => '2026-11-22',
                'amount' => '1500.000000',
                'expense_type_code_snapshot' => 'OFFICE-SUPPLIES',
                'expense_type_name_snapshot' => 'Office Supplies',
                'payment_method_id_snapshot' => $method->id,
                'payment_method_code_snapshot' => (string) $method->code,
                'payment_method_name_snapshot' => (string) $method->name,
                'payment_method_type_snapshot' => 'cash',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $service = app(\Modules\Payment\Services\PaymentMethodService::class);

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Payment methods used by expenses cannot be deleted; deactivate the method instead.');

            $service->delete($method);
        });
    }

    public function test_unreferenced_payment_method_can_be_deleted(): void
    {
        $tenantId = $this->tenantId();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $method = PaymentMethod::query()->create([
                'tenant_id' => $tenantId,
                'code' => 'SOLO-MTH',
                'name' => 'Solo Payment Method',
                'method_type' => PaymentMethodType::Cash,
                'requires_reference' => false,
                'requires_instrument_details' => false,
                'is_active' => true,
            ]);

            $service = app(\Modules\Payment\Services\PaymentMethodService::class);
            $service->delete($method);

            $this->assertSoftDeleted('payment_methods', ['id' => $method->id]);
        });
    }

    private function fixture(): array
    {
        $tenantId = $this->tenantId();

        return $this->withTenantExecutionContext($tenantId, function () use ($tenantId): array {
            $customerId = DB::table('customers')->insertGetId([
                'tenant_id' => $tenantId,
                'customer_type' => 'individual',
                'customer_number' => 'CUST-001',
                'code' => 'CUST-001',
                'name' => 'Alice Walker',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $method1 = PaymentMethod::query()->create([
                'tenant_id' => $tenantId,
                'code' => 'CASH-01',
                'name' => 'Cash in Hand',
                'method_type' => PaymentMethodType::Cash,
                'requires_reference' => false,
                'requires_instrument_details' => false,
                'is_active' => true,
            ]);

            $method2 = PaymentMethod::query()->create([
                'tenant_id' => $tenantId,
                'code' => 'BANK-01',
                'name' => 'Bank Transfer',
                'method_type' => PaymentMethodType::BankTransfer,
                'requires_reference' => true,
                'requires_instrument_details' => false,
                'is_active' => true,
            ]);

            $payment = new Payment();
            $payment->forceFill([
                'tenant_id' => $tenantId,
                'payment_number' => 'PAY-DRAFT-001',
                'payment_type' => PaymentType::CustomerReceipt->value,
                'direction' => PaymentDirection::Inbound->value,
                'document_status' => PaymentDocumentStatus::Draft->value,
                'allocation_status' => PaymentAllocationState::Unallocated->value,
                'posting_status' => PaymentPostingStatus::NotPosted->value,
                'instrument_status' => PaymentInstrumentStatus::Pending->value,
                'party_type' => 'customer',
                'party_id' => $customerId,
                'party_name_snapshot' => 'Alice Walker',
                'party_number_snapshot' => 'CUST-001',
                'payment_date' => '2026-11-15',
                'exchange_rate' => '1.000000',
                'reference_number' => 'REF-ORIGINAL-001',
                'total_amount' => '15000.000000',
                'allocated_amount' => '0.000000',
                'unapplied_amount' => '15000.000000',
                'refunded_amount' => '0.000000',
                'row_version' => 1,
            ]);
            $payment->save();

            $line = new PaymentLine();
            $line->forceFill([
                'tenant_id' => $tenantId,
                'payment_id' => $payment->id,
                'line_number' => 1,
                'payment_method_id' => $method1->id,
                'payment_method_code_snapshot' => (string) $method1->code,
                'payment_method_name_snapshot' => (string) $method1->name,
                'payment_method_type_snapshot' => 'cash',
                'requires_reference_snapshot' => false,
                'requires_instrument_details_snapshot' => false,
                'amount' => '15000.000000',
                'cleared_amount' => '0.000000',
                'status' => 'pending',
            ]);
            $line->save();

            return [$payment, $method1, $method2];
        });
    }

    private function tenantId(): int
    {
        $suffix = Str::upper(Str::random(8));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-CRUD-'.$suffix,
            'name' => 'Crud Tenant '.$suffix,
            'slug' => 'crud-tenant-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
