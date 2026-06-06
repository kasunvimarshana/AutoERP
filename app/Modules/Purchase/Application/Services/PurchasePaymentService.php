<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Application\Support\FinancialServiceSupport;
use Modules\Payment\Application\Services\PaymentAllocationService;
use Modules\Payment\Application\Services\PaymentService;

final class PurchasePaymentService
{
    public function __construct(
        private readonly FinancialServiceSupport $support,
        private readonly PaymentService $payments,
        private readonly PaymentAllocationService $allocations,
    ) {}

    /** @param array<string, mixed> $payload */
    public function createSupplierPayment(int $invoiceId, array $payload): object
    {
        return DB::transaction(function () use ($invoiceId, $payload): object {
            $invoice = $this->lockedPurchaseInvoice($invoiceId);
            $payment = $this->payments->create([
                ...$payload,
                'party_type' => 'supplier',
                'party_id' => (int) $invoice->supplier_id,
                'direction' => 'outbound',
                'source_module' => 'purchase',
                'source_type' => 'purchase_invoice',
                'source_id' => $invoiceId,
                'source_reference' => $invoice->invoice_number,
                'allocations' => [[
                    'invoice_id' => $invoiceId,
                    'allocated_amount' => $payload['allocated_amount'] ?? $payload['amount'],
                ]],
            ]);
            $this->syncInvoiceLinks($invoiceId);

            return $payment;
        });
    }

    /** @param array<string, mixed> $payload */
    public function createSupplierAdvance(int $supplierId, array $payload): object
    {
        $this->support->assertTenantRow('suppliers', $supplierId, 'supplier_id');

        return $this->payments->create([
            ...$payload,
            'party_type' => 'supplier',
            'party_id' => $supplierId,
            'direction' => 'outbound',
            'source_module' => 'purchase',
            'source_type' => 'supplier_advance',
            'source_id' => $supplierId,
            'allocations' => [],
        ]);
    }

    public function allocatePayment(int $invoiceId, int $paymentId, float $amount): object
    {
        return DB::transaction(function () use ($invoiceId, $paymentId, $amount): object {
            $this->lockedPurchaseInvoice($invoiceId);
            $existing = DB::table('purchase_payment_allocations')
                ->where('tenant_id', $this->support->tenantId())
                ->where('invoice_id', $invoiceId)
                ->where('payment_id', $paymentId)
                ->where('status', 'active')
                ->first();
            if ($existing !== null) {
                return $this->payments->find($paymentId);
            }
            if (DB::table('advance_payments')->where('tenant_id', $this->support->tenantId())->where('payment_id', $paymentId)->whereNull('deleted_at')->exists()) {
                throw ValidationException::withMessages(['payment_id' => ['This payment has already become an advance. Allocate the advance balance instead.']]);
            }
            $this->allocations->allocate($paymentId, [[
                'invoice_id' => $invoiceId,
                'allocated_amount' => $amount,
            ]]);
            $this->syncInvoiceLinks($invoiceId);

            return $this->payments->find($paymentId);
        });
    }

    public function allocateAdvance(int $invoiceId, int $advancePaymentId, float $amount): object
    {
        return DB::transaction(function () use ($invoiceId, $advancePaymentId, $amount): object {
            $this->lockedPurchaseInvoice($invoiceId);
            $existing = DB::table('purchase_payment_allocations')
                ->where('tenant_id', $this->support->tenantId())
                ->where('invoice_id', $invoiceId)
                ->where('advance_payment_id', $advancePaymentId)
                ->where('status', 'active')
                ->first();
            if ($existing !== null) {
                return $this->payments->findAdvance($advancePaymentId);
            }
            $this->allocations->allocateAdvance($advancePaymentId, [[
                'invoice_id' => $invoiceId,
                'allocated_amount' => $amount,
            ]]);
            $this->syncInvoiceLinks($invoiceId);

            return $this->payments->findAdvance($advancePaymentId);
        });
    }

    /** @return array<string, float> */
    public function visibility(int $invoiceId): array
    {
        $invoice = $this->purchaseInvoice($invoiceId);
        $advance = (float) DB::table('advance_payment_allocations')
            ->where('tenant_id', $this->support->tenantId())
            ->where('invoice_id', $invoiceId)
            ->where('status', 'active')
            ->sum('allocated_amount');
        $payment = (float) DB::table('payment_allocations')
            ->where('tenant_id', $this->support->tenantId())
            ->where('invoice_id', $invoiceId)
            ->where('status', 'active')
            ->sum('allocated_amount');

        return [
            'payment_allocated' => round($payment, 4),
            'advance_allocated' => round($advance, 4),
            'paid_total' => round((float) $invoice->settled_total, 4),
            'balance_due' => round((float) $invoice->balance_total, 4),
        ];
    }

    public function syncInvoiceLinks(int $invoiceId): void
    {
        $invoice = $this->lockedPurchaseInvoice($invoiceId);
        $normal = DB::table('payment_allocations')
            ->where('tenant_id', $this->support->tenantId())
            ->where('invoice_id', $invoiceId)
            ->where('status', 'active')
            ->get();
        foreach ($normal as $allocation) {
            DB::table('purchase_payment_allocations')->insertOrIgnore([
                'tenant_id' => $this->support->tenantId(),
                'organization_unit_id' => $invoice->organization_unit_id,
                'invoice_id' => $invoiceId,
                'payment_id' => (int) $allocation->payment_id,
                'allocated_amount' => $allocation->allocated_amount,
                'currency_id' => $allocation->currency_id,
                'base_allocated_amount' => $allocation->base_allocated_amount,
                'status' => 'active',
                'allocated_at' => $allocation->allocation_date,
                'created_by' => $this->support->userId(),
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $advances = DB::table('advance_payment_allocations')
            ->where('tenant_id', $this->support->tenantId())
            ->where('invoice_id', $invoiceId)
            ->where('status', 'active')
            ->get();
        foreach ($advances as $allocation) {
            DB::table('purchase_payment_allocations')->insertOrIgnore([
                'tenant_id' => $this->support->tenantId(),
                'organization_unit_id' => $invoice->organization_unit_id,
                'invoice_id' => $invoiceId,
                'advance_payment_id' => (int) $allocation->advance_payment_id,
                'allocated_amount' => $allocation->allocated_amount,
                'currency_id' => $allocation->currency_id,
                'base_allocated_amount' => $allocation->base_allocated_amount,
                'status' => 'active',
                'allocated_at' => $allocation->allocation_date,
                'created_by' => $this->support->userId(),
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function lockedPurchaseInvoice(int $invoiceId): object
    {
        $invoice = DB::table('invoices')
            ->where('tenant_id', $this->support->tenantId())
            ->where('id', $invoiceId)
            ->where('document_type', 'purchase_invoice')
            ->where('ledger_direction', 'payable')
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->first();
        if ($invoice === null) {
            throw ValidationException::withMessages(['invoice_id' => ['Purchase invoice was not found.']]);
        }

        return $invoice;
    }

    private function purchaseInvoice(int $invoiceId): object
    {
        $invoice = DB::table('invoices')
            ->where('tenant_id', $this->support->tenantId())
            ->where('id', $invoiceId)
            ->where('document_type', 'purchase_invoice')
            ->where('ledger_direction', 'payable')
            ->whereNull('deleted_at')
            ->first();
        if ($invoice === null) {
            throw ValidationException::withMessages(['invoice_id' => ['Purchase invoice was not found.']]);
        }

        return $invoice;
    }
}
