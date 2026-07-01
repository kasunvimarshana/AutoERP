<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentInstrumentStatus;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Models\ChequeTemplate;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentLine;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Services\ChequePrintService;
use Tests\TestCase;

final class ChequePrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_uses_immutable_payment_method_snapshot(): void
    {
        [$payment, $line, $template] = $this->fixture(PaymentDocumentStatus::Approved);

        $preview = $this->withTenantExecutionContext((int) $payment->tenant_id, fn () => app(ChequePrintService::class)->preview($payment, $line, $template));

        self::assertSame('PAY-TEST-001', $preview['payment']['payment_number']);
        self::assertSame('cheque', $preview['line']['payment_method']['method_type']);
        self::assertSame('ABC Supplier', $preview['line']['payee_name']);
        self::assertSame('12500.000000', $preview['line']['amount']);
        self::assertSame('Twelve Thousand Five Hundred Only', $preview['line']['amount_in_words']);
        self::assertSame('11/06/2026', $preview['line']['formatted_cheque_date']);
    }

    public function test_non_approved_payment_cannot_be_printed(): void
    {
        [$payment, $line, $template] = $this->fixture(PaymentDocumentStatus::Voided);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only approved cheque payments can be printed.');

        $this->withTenantExecutionContext((int) $payment->tenant_id, fn () => app(ChequePrintService::class)->preview($payment, $line, $template));
    }

    public function test_mark_printed_creates_an_immutable_print_log(): void
    {
        [$payment, $line, $template] = $this->fixture(
            PaymentDocumentStatus::Approved,
            PaymentPostingStatus::Posted,
        );

        $log = $this->withTenantExecutionContext((int) $payment->tenant_id, fn () => app(ChequePrintService::class)->markPrinted(
            $payment,
            $line,
            $template,
            printedBy: 10,
            notes: 'Test print confirmed',
        ));

        self::assertSame((int) $payment->getKey(), (int) $log->payment_id);
        self::assertSame((int) $line->getKey(), (int) $log->payment_line_id);
        self::assertSame('printed', (string) $log->print_status->value);
        self::assertSame('Test print confirmed', $log->notes);
    }

    /**
     * @return array{0: Payment, 1: PaymentLine, 2: ChequeTemplate}
     */
    private function fixture(
        PaymentDocumentStatus $documentStatus,
        PaymentPostingStatus $postingStatus = PaymentPostingStatus::NotPosted,
    ): array {
        $tenantId = $this->tenantId();
        return $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $documentStatus, $postingStatus): array {
            $method = PaymentMethod::query()->create([
                'tenant_id' => $tenantId,
                'code' => 'CHQ-'.Str::upper(Str::random(6)),
                'name' => 'Cheque',
                'method_type' => PaymentMethodType::Cheque->value,
                'direction_allowed' => 'outbound',
                'requires_reference' => true,
                'requires_instrument_details' => true,
                'is_active' => true,
            ]);
            $payment = Payment::query()->create([
                'tenant_id' => $tenantId,
                'payment_number' => 'PAY-TEST-001',
                'payment_type' => 'supplier_payment',
                'direction' => 'outbound',
                'document_status' => $documentStatus->value,
                'allocation_status' => PaymentAllocationState::Unallocated->value,
                'posting_status' => $postingStatus->value,
                'instrument_status' => PaymentInstrumentStatus::Pending->value,
                'payment_date' => '2026-06-11',
                'reference_number' => 'CHQ-1001',
                'cheque_number' => '1001',
                'cheque_date' => '2026-06-11',
                'payee_name' => 'ABC Supplier',
                'total_amount' => '12500.000000',
                'allocated_amount' => '0.000000',
                'unapplied_amount' => '12500.000000',
                'refunded_amount' => '0.000000',
            ]);
            $line = PaymentLine::query()->create([
                'tenant_id' => $tenantId,
                'payment_id' => $payment->getKey(),
                'line_number' => 1,
                'payment_method_id' => $method->getKey(),
                'payment_method_code_snapshot' => (string) $method->code,
                'payment_method_name_snapshot' => (string) $method->name,
                'payment_method_type_snapshot' => PaymentMethodType::Cheque->value,
                'requires_reference_snapshot' => true,
                'requires_instrument_details_snapshot' => true,
                'reference_number' => 'CHQ-1001',
                'instrument_direction' => 'issued',
                'instrument_number' => '1001',
                'instrument_date' => '2026-06-11',
                'amount' => '12500.000000',
                'cleared_amount' => '0.000000',
                'status' => 'pending',
            ]);
            $template = ChequeTemplate::query()->create([
                'tenant_id' => $tenantId,
                'bank_name' => 'Example Bank',
                'template_name' => 'Default Bank Cheque',
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
                'font_family' => 'Arial',
                'is_default' => true,
                'is_active' => true,
                'metadata' => ['date_format' => 'd/m/Y'],
            ]);

            return [$payment, $line, $template];
        });
    }

    private function tenantId(): int
    {
        $suffix = Str::upper(Str::random(8));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-CHQ-'.$suffix,
            'name' => 'Cheque Tenant '.$suffix,
            'slug' => 'cheque-tenant-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
