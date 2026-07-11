<?php

declare(strict_types=1);

namespace Modules\Voucher\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentInstrumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentLine;
use Modules\Payment\Models\PaymentMethod;
use Modules\Voucher\Enums\VoucherType;
use Modules\Voucher\Services\VoucherSourceResolver;
use Tests\TestCase;

final class VoucherWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_voucher_uses_direction_and_immutable_snapshots(): void
    {
        $tenantId = $this->tenantId();
        $payment = $this->payment($tenantId);

        $payload = $this->withTenantExecutionContext($tenantId, fn (): array => app(VoucherSourceResolver::class)->resolve(
            VoucherType::Receipt,
            (int) $payment->getKey(),
            $tenantId,
            null,
            'payment',
        )->toArray());

        self::assertSame('receipt_voucher', $payload['voucher_type']);
        self::assertSame('Voucher Customer', $payload['party_name']);
        self::assertSame('LKR', $payload['currency']);
        self::assertSame('Cash', $payload['payment_lines'][0]['method']);
        self::assertSame('approved', $payload['document_status']);
        self::assertSame('posted', $payload['posting_status']);
        self::assertContains('reverse', $payload['available_actions']);
    }

    private function payment(int $tenantId): Payment
    {
        return $this->withTenantExecutionContext($tenantId, function () use ($tenantId): Payment {
            $method = PaymentMethod::query()->create([
                'tenant_id' => $tenantId,
                'code' => 'CASH-'.Str::upper(Str::random(6)),
                'name' => 'Cash',
                'method_type' => 'cash',
                'direction_allowed' => PaymentDirection::Inbound->value,
                'requires_reference' => false,
                'requires_instrument_details' => false,
                'is_active' => true,
            ]);
            $payment = new Payment();
            $payment->forceFill([
                'tenant_id' => $tenantId,
                'payment_number' => 'RV-2026-0001',
                'payment_type' => PaymentType::CustomerReceipt->value,
                'direction' => PaymentDirection::Inbound->value,
                'party_type' => 'customer',
                'party_id' => 10,
                'party_number_snapshot' => 'CUS-0010',
                'party_code_snapshot' => 'CUS-0010',
                'party_name_snapshot' => 'Voucher Customer',
                'document_status' => PaymentDocumentStatus::Approved->value,
                'allocation_status' => PaymentAllocationState::Unallocated->value,
                'posting_status' => PaymentPostingStatus::Posted->value,
                'instrument_status' => PaymentInstrumentStatus::Cleared->value,
                'payment_date' => '2026-06-29',
                'currency_code_snapshot' => 'LKR',
                'currency_name_snapshot' => 'Sri Lankan Rupee',
                'currency_symbol_snapshot' => 'Rs',
                'exchange_rate' => '1.000000',
                'total_amount' => '1000.000000',
                'allocated_amount' => '0.000000',
                'unapplied_amount' => '1000.000000',
                'refunded_amount' => '0.000000',
                'finance_posting_reference' => 'JV-RV-2026-0001',
            ]);
            $payment->save();
            PaymentLine::query()->create([
                'tenant_id' => $tenantId,
                'payment_id' => $payment->getKey(),
                'line_number' => 1,
                'payment_method_id' => $method->getKey(),
                'payment_method_code_snapshot' => (string) $method->code,
                'payment_method_name_snapshot' => 'Cash',
                'payment_method_type_snapshot' => 'cash',
                'requires_reference_snapshot' => false,
                'requires_instrument_details_snapshot' => false,
                'amount' => '1000.000000',
                'cleared_amount' => '1000.000000',
                'status' => 'cleared',
            ]);

            return $payment->refresh();
        });
    }

    private function tenantId(): int
    {
        $suffix = Str::upper(Str::random(8));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-VOU-'.$suffix,
            'name' => 'Voucher Tenant '.$suffix,
            'slug' => 'voucher-tenant-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
