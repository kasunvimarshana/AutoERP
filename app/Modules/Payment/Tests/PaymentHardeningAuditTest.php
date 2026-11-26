<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\ChequePrintStatus;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentInstrumentStatus;
use Modules\Payment\Enums\PaymentMethodDirection;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\ChequePrintLog;
use Modules\Payment\Models\ChequeTemplate;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentAllocation;
use Modules\Payment\Models\PaymentLifecycleEvent;
use Modules\Payment\Models\PaymentLine;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Models\PaymentUnappliedBalance;
use Modules\Payment\Services\InvoicePaymentIntegrationService;
use Modules\Payment\Services\PaymentAllocationReversalService;
use Modules\Payment\Services\PaymentMethodService;
use Modules\Payment\Services\PaymentReceiptService;
use Modules\Payment\Services\PaymentRefundPolicyService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\Support\CurrencyFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class PaymentHardeningAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_method_version_conflict_throws_conflict_http_exception(): void
    {
        [$tenantId, $userId, $orgId, $currencyId] = $this->createTenantAndUser();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $service = app(PaymentMethodService::class);
            $method = $service->create([
                'code' => 'MTH-TEST',
                'name' => 'Test Method',
                'method_type' => 'cash',
                'direction_allowed' => 'both',
            ], $tenantId, null);

            $this->expectException(ConflictHttpException::class);
            $this->expectExceptionMessage('Payment method was changed by someone else. Reload before saving.');

            $service->update($method, [
                'name' => 'Updated Name',
                'expected_version' => 999, // Stale version
            ]);
        });
    }

    public function test_payment_receipt_resolves_vehicle_rental_customer_agreement_reference(): void
    {
        [$tenantId, $userId, $orgId, $currencyId] = $this->createTenantAndUser();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $userId, $currencyId, $orgId): void {
            $customerId = DB::table('customers')->insertGetId([
                'tenant_id' => $tenantId,
                'customer_number' => 'CUST-AUDIT-001',
                'code' => 'CUST-AUDIT-001',
                'name' => 'Audit Rental Customer',
                'customer_type' => 'individual',
                'status' => 'active',
            ]);

            $agreementId = DB::table('vehicle_rental_customer_agreements')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'reference' => 'VRC-AGR-AUDIT-001',
                'customer_id' => $customerId,
                'party_name_snapshot' => 'Audit Rental Customer',
                'currency_id' => $currencyId,
                'currency_code_snapshot' => 'LKR',
                'agreed_on' => '2026-11-01',
                'starts_on' => '2026-11-01',
                'basis' => 'daily',
                'driver_mode' => 'self_drive',
                'terms' => 'Standard rental agreement terms',
                'status' => 'active',
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $method = PaymentMethod::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'CASH-REC',
                'name' => 'Cash',
                'method_type' => PaymentMethodType::Cash,
                'direction_allowed' => PaymentMethodDirection::Both,
                'requires_reference' => false,
                'is_active' => true,
            ]);

            $payment = new Payment;
            $payment->forceFill([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'payment_number' => 'PAY-REC-'.Str::upper(Str::random(6)),
                'payment_type' => PaymentType::CustomerReceipt,
                'direction' => PaymentDirection::Inbound,
                'source_id' => $agreementId,
                'party_type' => 'customer',
                'party_id' => $customerId,
                'party_name_snapshot' => 'Audit Rental Customer',
                'document_status' => PaymentDocumentStatus::Approved,
                'allocation_status' => PaymentAllocationState::Unallocated,
                'posting_status' => PaymentPostingStatus::Posted,
                'instrument_status' => PaymentInstrumentStatus::Cleared,
                'payment_date' => '2026-11-15',
                'currency_id' => $currencyId,
                'exchange_rate' => '1.000000',
                'total_amount' => '5000.000000',
                'allocated_amount' => '0.000000',
                'unapplied_amount' => '5000.000000',
                'refunded_amount' => '0.000000',
                'created_by' => $userId,
            ]);
            $payment->save();

            $line = new PaymentLine;
            $line->forceFill([
                'tenant_id' => $tenantId,
                'payment_id' => $payment->getKey(),
                'line_number' => 1,
                'payment_method_id' => $method->getKey(),
                'payment_method_code_snapshot' => (string) $method->code,
                'payment_method_name_snapshot' => (string) $method->name,
                'payment_method_type_snapshot' => PaymentMethodType::Cash->value,
                'amount' => '5000.000000',
            ]);
            $line->save();

            $service = app(PaymentReceiptService::class);
            $receiptData = $service->getReceiptData($payment);

            self::assertSame('VRC-AGR-AUDIT-001', $receiptData['agreement_reference']);
            self::assertStringNotContainsString('Only Only', $receiptData['amount_in_words']);
            self::assertStringEndsWith('Only', $receiptData['amount_in_words']);
        });
    }

    public function test_payment_line_immutability_on_approved_payment(): void
    {
        [$tenantId, $payment, $line] = $this->createApprovedPaymentFixture();

        $this->withTenantExecutionContext($tenantId, function () use ($line): void {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('Financial attributes of non-draft payment lines are immutable in place.');

            $line->amount = '9999.000000';
            $line->save();
        });
    }

    public function test_payment_line_cannot_be_deleted_on_approved_payment(): void
    {
        [$tenantId, $payment, $line] = $this->createApprovedPaymentFixture();

        $this->withTenantExecutionContext($tenantId, function () use ($line): void {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('Lines of non-draft payments cannot be deleted.');

            $line->delete();
        });
    }

    public function test_payment_allocation_snapshots_are_immutable(): void
    {
        [$tenantId, $payment, $line] = $this->createApprovedPaymentFixture();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $payment): void {
            $invoiceId = (int) DB::table('invoices')->insertGetId([
                'tenant_id' => $tenantId,
                'invoice_number' => 'INV-AUDIT-01',
                'invoice_type' => 'sales',
                'direction' => 'outbound',
                'invoice_date' => '2026-11-01',
                'status' => 'posted',
                'grand_total' => '2000.000000',
                'paid_total' => '0.000000',
                'balance_due' => '2000.000000',
            ]);

            $allocation = new PaymentAllocation;
            $allocation->forceFill([
                'tenant_id' => $tenantId,
                'payment_id' => $payment->getKey(),
                'invoice_id' => $invoiceId,
                'allocation_date' => '2026-11-15',
                'allocated_amount' => '1000.000000',
                'active_identity_slot' => 1,
                'status' => AllocationStatus::Active->value,
                'invoice_number_snapshot' => 'INV-ORIG-01',
                'invoice_exchange_rate_snapshot' => '1.000000',
                'invoice_total' => '2000.000000',
                'invoice_balance_before' => '2000.000000',
                'invoice_balance_after' => '1000.000000',
                'previously_allocated_amount' => '0.000000',
            ]);
            $allocation->save();

            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('Payment allocation amounts and targets are immutable. Reverse the allocation instead.');

            $allocation->invoice_number_snapshot = 'INV-TAMPERED-01';
            $allocation->save();
        });
    }

    public function test_payment_allocation_cannot_be_deleted(): void
    {
        [$tenantId, $payment, $line] = $this->createApprovedPaymentFixture();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $payment): void {
            $invoiceId = (int) DB::table('invoices')->insertGetId([
                'tenant_id' => $tenantId,
                'invoice_number' => 'INV-AUDIT-02',
                'invoice_type' => 'sales',
                'direction' => 'outbound',
                'invoice_date' => '2026-11-01',
                'status' => 'posted',
                'grand_total' => '1000.000000',
                'paid_total' => '0.000000',
                'balance_due' => '1000.000000',
            ]);

            $allocation = new PaymentAllocation;
            $allocation->forceFill([
                'tenant_id' => $tenantId,
                'payment_id' => $payment->getKey(),
                'invoice_id' => $invoiceId,
                'allocation_date' => '2026-11-15',
                'allocated_amount' => '1000.000000',
                'active_identity_slot' => 1,
                'status' => AllocationStatus::Active->value,
                'invoice_number_snapshot' => 'INV-001',
                'invoice_exchange_rate_snapshot' => '1.000000',
                'invoice_total' => '1000.000000',
                'invoice_balance_before' => '1000.000000',
                'invoice_balance_after' => '0.000000',
                'previously_allocated_amount' => '0.000000',
            ]);
            $allocation->save();

            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('cannot be deleted. Use governed reversal to undo an allocation.');

            $allocation->delete();
        });
    }

    public function test_cheque_print_log_is_strictly_immutable(): void
    {
        [$tenantId, $payment, $line] = $this->createApprovedPaymentFixture();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $payment, $line): void {
            $template = ChequeTemplate::query()->create([
                'tenant_id' => $tenantId,
                'template_name' => 'Standard Cheque Template',
                'bank_name' => 'Commercial Bank',
                'page_width_mm' => '210.000',
                'page_height_mm' => '99.000',
                'date_x_mm' => '155.000',
                'date_y_mm' => '12.000',
                'payee_x_mm' => '25.000',
                'payee_y_mm' => '34.000',
                'amount_x_mm' => '160.000',
                'amount_y_mm' => '45.000',
                'amount_words_x_mm' => '25.000',
                'amount_words_y_mm' => '55.000',
                'cheque_number_x_mm' => '15.000',
                'cheque_number_y_mm' => '12.000',
                'font_size' => '12.00',
                'is_default' => false,
                'is_active' => true,
            ]);

            $log = ChequePrintLog::query()->create([
                'tenant_id' => $tenantId,
                'payment_id' => $payment->getKey(),
                'payment_line_id' => $line->getKey(),
                'cheque_template_id' => $template->getKey(),
                'printed_by' => 1,
                'printed_at' => now(),
                'print_status' => ChequePrintStatus::Printed,
                'notes' => 'First print',
            ]);

            try {
                $log->notes = 'Tampered notes';
                $log->save();
                self::fail('Cheque print log should be immutable on update.');
            } catch (LogicException $e) {
                self::assertStringContainsString('strictly immutable', $e->getMessage());
            }

            try {
                $log->delete();
                self::fail('Cheque print log should be immutable on delete.');
            } catch (LogicException $e) {
                self::assertStringContainsString('cannot be deleted', $e->getMessage());
            }
        });
    }

    public function test_payment_method_deletion_guard_when_lines_exist(): void
    {
        [$tenantId, $payment, $line] = $this->createApprovedPaymentFixture();

        $this->withTenantExecutionContext($tenantId, function () use ($line): void {
            $method = $line->paymentMethod;
            self::assertNotNull($method);

            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('Payment methods with recorded payment transactions cannot be deleted. Deactivate the payment method instead.');

            $method->delete();
        });
    }

    public function test_cheque_template_deletion_guards(): void
    {
        [$tenantId, $payment, $line] = $this->createApprovedPaymentFixture();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $payment, $line): void {
            $templateData = [
                'tenant_id' => $tenantId,
                'bank_name' => 'Sampath Bank',
                'page_width_mm' => '210.000',
                'page_height_mm' => '99.000',
                'date_x_mm' => '155.000',
                'date_y_mm' => '12.000',
                'payee_x_mm' => '25.000',
                'payee_y_mm' => '34.000',
                'amount_x_mm' => '160.000',
                'amount_y_mm' => '45.000',
                'amount_words_x_mm' => '25.000',
                'amount_words_y_mm' => '55.000',
                'cheque_number_x_mm' => '15.000',
                'cheque_number_y_mm' => '12.000',
                'font_size' => '12.00',
                'is_active' => true,
            ];

            $defaultTemplate = ChequeTemplate::query()->create([
                ...$templateData,
                'template_name' => 'Default Template',
                'is_default' => true,
            ]);

            try {
                $defaultTemplate->delete();
                self::fail('Default cheque template cannot be deleted.');
            } catch (LogicException $e) {
                self::assertStringContainsString('default cheque template cannot be deleted', $e->getMessage());
            }

            $nonDefaultTemplate = ChequeTemplate::query()->create([
                ...$templateData,
                'template_name' => 'Non Default Template',
                'bank_name' => 'HNB Bank',
                'is_default' => false,
            ]);

            ChequePrintLog::query()->create([
                'tenant_id' => $tenantId,
                'payment_id' => $payment->getKey(),
                'payment_line_id' => $line->getKey(),
                'cheque_template_id' => $nonDefaultTemplate->getKey(),
                'printed_by' => 1,
                'printed_at' => now(),
                'print_status' => ChequePrintStatus::Printed,
            ]);

            try {
                $nonDefaultTemplate->delete();
                self::fail('Cheque template with print logs cannot be deleted.');
            } catch (LogicException $e) {
                self::assertStringContainsString('print history cannot be deleted', $e->getMessage());
            }
        });
    }

    public function test_invoice_payment_integration_allocates_with_default_expected_version(): void
    {
        [$tenantId, $payment, $line] = $this->createApprovedPaymentFixture();

        $this->withTenantExecutionContext($tenantId, function () use ($payment): void {
            // Create dummy invoice record to satisfy foreign key or query if any
            $integration = app(InvoicePaymentIntegrationService::class);

            // Passing empty allocations verifies that allocatePaymentToInvoices executes
            // allocate() with the defaulted expected_version without ArgumentCountError
            $result = $integration->allocatePaymentToInvoices($payment, []);
            self::assertInstanceOf(Payment::class, $result);
        });
    }

    public function test_payment_refund_policy_service_uses_exact_decimal_summation(): void
    {
        [$tenantId, $payment, $line] = $this->createApprovedPaymentFixture();

        $this->withTenantExecutionContext($tenantId, function () use ($payment): void {
            $service = app(PaymentRefundPolicyService::class);
            $unposted = $service->reservedUnpostedAmount($payment);
            self::assertSame('0.000000', $unposted);
        });
    }

    public function test_payment_header_snapshots_are_immutable_on_approved_payment(): void
    {
        [$tenantId, $payment, $line] = $this->createApprovedPaymentFixture();

        $this->withTenantExecutionContext($tenantId, function () use ($payment): void {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('Historical posted payment financial attributes are immutable in place.');

            $payment->party_name_snapshot = 'Tampered Customer Name';
            $payment->save();
        });
    }

    public function test_payment_line_snapshots_are_immutable_on_approved_payment(): void
    {
        [$tenantId, $payment, $line] = $this->createApprovedPaymentFixture();

        $this->withTenantExecutionContext($tenantId, function () use ($line): void {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('Financial attributes of non-draft payment lines are immutable in place.');

            $line->payment_method_name_snapshot = 'Tampered Method';
            $line->save();
        });
    }

    public function test_payment_allocation_additional_snapshots_are_immutable(): void
    {
        [$tenantId, $payment, $line] = $this->createApprovedPaymentFixture();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $payment): void {
            $invoiceId = (int) DB::table('invoices')->insertGetId([
                'tenant_id' => $tenantId,
                'invoice_number' => 'INV-AUDIT-SNAP-01',
                'invoice_type' => 'sales',
                'direction' => 'outbound',
                'invoice_date' => '2026-11-01',
                'status' => 'posted',
                'grand_total' => '2000.000000',
                'paid_total' => '0.000000',
                'balance_due' => '2000.000000',
            ]);

            $allocation = new PaymentAllocation;
            $allocation->forceFill([
                'tenant_id' => $tenantId,
                'payment_id' => $payment->getKey(),
                'invoice_id' => $invoiceId,
                'allocation_date' => '2026-11-15',
                'allocated_amount' => '1000.000000',
                'active_identity_slot' => 1,
                'status' => AllocationStatus::Active->value,
                'invoice_number_snapshot' => 'INV-AUDIT-SNAP-01',
                'invoice_date_snapshot' => '2026-11-01',
                'invoice_currency_code_snapshot' => 'LKR',
                'invoice_exchange_rate_snapshot' => '1.000000',
                'invoice_total' => '2000.000000',
                'invoice_balance_before' => '2000.000000',
                'invoice_balance_after' => '1000.000000',
                'previously_allocated_amount' => '0.000000',
                'allocation_method' => 'specific_invoice',
            ]);
            $allocation->save();

            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('Payment allocation amounts and targets are immutable. Reverse the allocation instead.');

            $allocation->invoice_date_snapshot = '2026-11-05';
            $allocation->save();
        });
    }

    public function test_payment_lifecycle_event_is_strictly_immutable(): void
    {
        [$tenantId, $payment, $line] = $this->createApprovedPaymentFixture();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $payment): void {
            $event = PaymentLifecycleEvent::query()->create([
                'tenant_id' => $tenantId,
                'payment_id' => $payment->getKey(),
                'dimension' => 'document',
                'from_state' => 'draft',
                'to_state' => 'approved',
                'changed_by' => 1,
                'occurred_at' => now(),
            ]);

            try {
                $event->to_state = 'tampered';
                $event->save();
                self::fail('Payment lifecycle event update must throw LogicException.');
            } catch (LogicException $e) {
                self::assertStringContainsString('append-only', $e->getMessage());
            }

            try {
                $event->delete();
                self::fail('Payment lifecycle event delete must throw LogicException.');
            } catch (LogicException $e) {
                self::assertStringContainsString('cannot be deleted', $e->getMessage());
            }
        });
    }

    public function test_payment_unapplied_balance_cannot_be_deleted_on_approved_payment(): void
    {
        [$tenantId, $payment, $line] = $this->createApprovedPaymentFixture();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $payment): void {
            $balance = PaymentUnappliedBalance::query()->create([
                'tenant_id' => $tenantId,
                'payment_id' => $payment->getKey(),
                'balance_type' => 'credit',
                'allocation_status' => 'unallocated',
                'original_amount' => '10000.000000',
                'allocated_amount' => '0.000000',
                'refunded_amount' => '0.000000',
                'remaining_amount' => '10000.000000',
                'status' => 'available',
            ]);

            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('Unapplied balance records for non-draft payments cannot be deleted.');

            $balance->delete();
        });
    }

    public function test_payment_receipt_resolves_employee_party(): void
    {
        [$tenantId, $userId, $orgId, $currencyId] = $this->createTenantAndUser();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $userId, $currencyId, $orgId): void {
            $employeeId = DB::table('hr_employees')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_number' => 'EMP-AUDIT-001',
                'first_name' => 'Jane',
                'display_name' => 'Jane Driver',
                'phone' => '+94770000001',
                'email' => 'jane@example.test',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $payment = new Payment;
            $payment->forceFill([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'payment_number' => 'PAY-EMP-'.Str::upper(Str::random(6)),
                'payment_type' => PaymentType::Advance,
                'direction' => PaymentDirection::Outbound,
                'party_type' => 'employee',
                'party_id' => $employeeId,
                'document_status' => PaymentDocumentStatus::Approved,
                'allocation_status' => PaymentAllocationState::Unallocated,
                'posting_status' => PaymentPostingStatus::Posted,
                'instrument_status' => PaymentInstrumentStatus::Cleared,
                'payment_date' => '2026-11-15',
                'currency_id' => $currencyId,
                'exchange_rate' => '1.000000',
                'total_amount' => '8000.000000',
                'allocated_amount' => '0.000000',
                'unapplied_amount' => '8000.000000',
                'refunded_amount' => '0.000000',
                'created_by' => $userId,
            ]);
            $payment->save();

            $receipt = app(PaymentReceiptService::class)->getReceiptData($payment);
            self::assertSame('Jane Driver', $receipt['party']['name']);
            self::assertSame('EMP-AUDIT-001', $receipt['party']['code']);
            self::assertSame('+94770000001', $receipt['party']['phone']);
            self::assertSame('jane@example.test', $receipt['party']['email']);
        });
    }

    public function test_payment_allocation_reversal_rejects_stale_payment_version(): void
    {
        [$tenantId, $payment, $line] = $this->createApprovedPaymentFixture();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $payment): void {
            $invoiceId = (int) DB::table('invoices')->insertGetId([
                'tenant_id' => $tenantId,
                'invoice_number' => 'INV-CAS-01',
                'invoice_type' => 'sales',
                'direction' => 'outbound',
                'invoice_date' => '2026-11-15',
                'status' => 'posted',
                'grand_total' => '5000.000000',
                'paid_total' => '0.000000',
                'balance_due' => '5000.000000',
            ]);

            $allocation = new PaymentAllocation;
            $allocation->forceFill([
                'tenant_id' => $tenantId,
                'payment_id' => $payment->getKey(),
                'invoice_id' => $invoiceId,
                'allocation_date' => '2026-11-15',
                'allocated_amount' => '2000.000000',
                'active_identity_slot' => 1,
                'status' => AllocationStatus::Active->value,
                'invoice_number_snapshot' => 'INV-CAS-01',
                'invoice_exchange_rate_snapshot' => '1.000000',
                'invoice_total' => '5000.000000',
                'invoice_balance_before' => '5000.000000',
                'invoice_balance_after' => '3000.000000',
                'previously_allocated_amount' => '0.000000',
                'metadata' => [
                    'posting_reference' => 'JRN-ALLOC-TEST-01',
                ],
            ]);
            $allocation->save();

            $reversalService = app(PaymentAllocationReversalService::class);

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Payment was changed by another request. Reload it before reversing the allocation.');

            $reversalService->reverse(
                $payment,
                (int) $allocation->getKey(),
                expectedPaymentVersion: 999, // Stale version
                expectedAllocationVersion: (int) $allocation->row_version,
                reversalDate: '2026-11-15',
                reason: 'Testing stale payment version',
            );
        });
    }

    public function test_payment_allocation_reversal_rejects_stale_allocation_version(): void
    {
        [$tenantId, $payment, $line] = $this->createApprovedPaymentFixture();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $payment): void {
            $invoiceId = (int) DB::table('invoices')->insertGetId([
                'tenant_id' => $tenantId,
                'invoice_number' => 'INV-CAS-02',
                'invoice_type' => 'sales',
                'direction' => 'outbound',
                'invoice_date' => '2026-11-15',
                'status' => 'posted',
                'grand_total' => '5000.000000',
                'paid_total' => '0.000000',
                'balance_due' => '5000.000000',
            ]);

            $allocation = new PaymentAllocation;
            $allocation->forceFill([
                'tenant_id' => $tenantId,
                'payment_id' => $payment->getKey(),
                'invoice_id' => $invoiceId,
                'allocation_date' => '2026-11-15',
                'allocated_amount' => '2000.000000',
                'active_identity_slot' => 1,
                'status' => AllocationStatus::Active->value,
                'invoice_number_snapshot' => 'INV-CAS-02',
                'invoice_exchange_rate_snapshot' => '1.000000',
                'invoice_total' => '5000.000000',
                'invoice_balance_before' => '5000.000000',
                'invoice_balance_after' => '3000.000000',
                'previously_allocated_amount' => '0.000000',
                'metadata' => [
                    'posting_reference' => 'JRN-ALLOC-TEST-02',
                ],
            ]);
            $allocation->save();

            $reversalService = app(PaymentAllocationReversalService::class);

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Payment allocation was changed by another request. Reload it before reversing.');

            $reversalService->reverse(
                $payment,
                (int) $allocation->getKey(),
                expectedPaymentVersion: (int) $payment->row_version,
                expectedAllocationVersion: 999, // Stale allocation version
                reversalDate: '2026-11-15',
                reason: 'Testing stale allocation version',
            );
        });
    }

    public function test_payment_allocation_reversal_rejects_already_reversed_allocation(): void
    {
        [$tenantId, $payment, $line] = $this->createApprovedPaymentFixture();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $payment): void {
            $invoiceId = (int) DB::table('invoices')->insertGetId([
                'tenant_id' => $tenantId,
                'invoice_number' => 'INV-CAS-03',
                'invoice_type' => 'sales',
                'direction' => 'outbound',
                'invoice_date' => '2026-11-15',
                'status' => 'posted',
                'grand_total' => '5000.000000',
                'paid_total' => '0.000000',
                'balance_due' => '5000.000000',
            ]);

            $allocation = new PaymentAllocation;
            $allocation->forceFill([
                'tenant_id' => $tenantId,
                'payment_id' => $payment->getKey(),
                'invoice_id' => $invoiceId,
                'allocation_date' => '2026-11-15',
                'allocated_amount' => '2000.000000',
                'active_identity_slot' => null,
                'status' => AllocationStatus::Reversed->value,
                'invoice_number_snapshot' => 'INV-CAS-03',
                'invoice_exchange_rate_snapshot' => '1.000000',
                'invoice_total' => '5000.000000',
                'invoice_balance_before' => '5000.000000',
                'invoice_balance_after' => '3000.000000',
                'previously_allocated_amount' => '0.000000',
            ]);
            $allocation->save();

            $reversalService = app(PaymentAllocationReversalService::class);

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Active payment allocation was not found.');

            $reversalService->reverse(
                $payment,
                (int) $allocation->getKey(),
                expectedPaymentVersion: (int) $payment->row_version,
                expectedAllocationVersion: (int) $allocation->row_version,
                reversalDate: '2026-11-15',
                reason: 'Testing already reversed allocation',
            );
        });
    }

    private function createTenantAndUser(): array
    {
        $code = Str::upper(Str::random(10));
        $currencyId = CurrencyFixture::create(['code' => 'LKR', 'symbol' => 'Rs.', 'precision' => 2]);
        $tenantId = DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => 'Tenant '.$code,
            'slug' => strtolower($code),
            'status' => 'active',
            'status_changed_at' => now(),
            'base_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'code' => $code, 'name' => 'Org '.$code]);
        $userId = TenantUserFixture::create(['tenant_id' => $tenantId, 'email' => strtolower($code).'@example.test']);

        return [$tenantId, $userId, $orgId, $currencyId];
    }

    private function createApprovedPaymentFixture(): array
    {
        [$tenantId, $userId, $orgId, $currencyId] = $this->createTenantAndUser();

        return $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $userId, $currencyId, $orgId): array {
            $method = PaymentMethod::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'MTH-'.Str::upper(Str::random(5)),
                'name' => 'Bank Transfer',
                'method_type' => PaymentMethodType::BankTransfer,
                'direction_allowed' => PaymentMethodDirection::Both,
                'requires_reference' => false,
                'is_active' => true,
            ]);

            $payment = new Payment;
            $payment->forceFill([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'payment_number' => 'PAY-'.Str::upper(Str::random(8)),
                'payment_type' => PaymentType::CustomerReceipt,
                'direction' => PaymentDirection::Inbound,
                'party_type' => 'customer',
                'party_id' => 1,
                'party_name_snapshot' => 'Audit Test Customer',
                'document_status' => PaymentDocumentStatus::Approved,
                'allocation_status' => PaymentAllocationState::Unallocated,
                'posting_status' => PaymentPostingStatus::Posted,
                'instrument_status' => PaymentInstrumentStatus::Cleared,
                'payment_date' => '2026-11-15',
                'currency_id' => $currencyId,
                'exchange_rate' => '1.000000',
                'total_amount' => '10000.000000',
                'allocated_amount' => '0.000000',
                'unapplied_amount' => '10000.000000',
                'refunded_amount' => '0.000000',
                'created_by' => $userId,
            ]);
            $payment->save();

            $line = new PaymentLine;
            $line->forceFill([
                'tenant_id' => $tenantId,
                'payment_id' => $payment->getKey(),
                'line_number' => 1,
                'payment_method_id' => $method->getKey(),
                'payment_method_code_snapshot' => (string) $method->code,
                'payment_method_name_snapshot' => (string) $method->name,
                'payment_method_type_snapshot' => PaymentMethodType::BankTransfer->value,
                'amount' => '10000.000000',
            ]);
            $line->save();

            return [$tenantId, $payment->fresh(['lines', 'lines.paymentMethod']), $line->fresh(['payment', 'paymentMethod'])];
        });
    }
}
