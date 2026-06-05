<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Application\Support\FinancialServiceSupport;
use Modules\Invoice\Application\Services\InvoiceService;

final class PaymentAllocationService
{
    public function __construct(
        private readonly FinancialServiceSupport $support,
        private readonly InvoiceService $invoices,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $allocations
     * @return array<int, int>
     */
    public function allocate(int $paymentId, array $allocations): array
    {
        return DB::transaction(function () use ($paymentId, $allocations): array {
            $tenantId = $this->support->tenantId();
            $payment = DB::table('payments')->where('tenant_id', $tenantId)->where('id', $paymentId)->whereNull('deleted_at')->lockForUpdate()->first();
            if ($payment === null) {
                throw ValidationException::withMessages(['payment_id' => ['Payment was not found.']]);
            }
            if ($allocations === []) {
                return [];
            }

            $existingAllocated = (float) DB::table('payment_allocations')
                ->where('tenant_id', $tenantId)
                ->where('payment_id', $paymentId)
                ->where('status', 'active')
                ->sum('allocated_amount');
            $available = (float) $payment->amount - $existingAllocated;
            $created = [];

            foreach (array_values($allocations) as $index => $allocation) {
                $amount = (float) ($allocation['allocated_amount'] ?? $allocation['amount'] ?? 0);
                if ($amount <= 0) {
                    throw ValidationException::withMessages(["allocations.$index.allocated_amount" => ['Allocated amount must be positive.']]);
                }
                if ($amount > $available + 0.0001) {
                    throw ValidationException::withMessages(['allocations' => ['Total allocations cannot exceed payment amount.']]);
                }

                $invoice = DB::table('invoices')
                    ->where('tenant_id', $tenantId)
                    ->where('id', (int) $allocation['invoice_id'])
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();
                if ($invoice === null) {
                    throw ValidationException::withMessages(["allocations.$index.invoice_id" => ['Invoice was not found.']]);
                }
                if ($invoice->ledger_direction === 'receivable' && $payment->direction !== 'inbound') {
                    throw ValidationException::withMessages(['direction' => ['Receivable invoices require inbound payments.']]);
                }
                if ($invoice->ledger_direction === 'payable' && $payment->direction !== 'outbound') {
                    throw ValidationException::withMessages(['direction' => ['Payable invoices require outbound payments.']]);
                }
                if ($amount > (float) $invoice->balance_total + 0.0001) {
                    throw ValidationException::withMessages(["allocations.$index.allocated_amount" => ['Allocated amount cannot exceed invoice balance.']]);
                }

                $allocationId = DB::table('payment_allocations')->insertGetId([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $payment->organization_unit_id,
                    'payment_id' => $paymentId,
                    'invoice_id' => (int) $allocation['invoice_id'],
                    'invoice_line_id' => $allocation['invoice_line_id'] ?? null,
                    'source_module' => 'payment',
                    'source_type' => 'payment_allocation',
                    'source_id' => $paymentId,
                    'source_reference' => $payment->payment_number,
                    'reference' => $allocation['reference'] ?? null,
                    'allocated_amount' => $amount,
                    'currency_id' => $payment->currency_id,
                    'base_allocated_amount' => $amount,
                    'allocation_date' => $allocation['allocation_date'] ?? $payment->payment_date,
                    'status' => 'active',
                    'row_version' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->invoices->applySettlement((int) $allocation['invoice_id'], 'payment', $allocationId, $amount, $allocation['allocation_date'] ?? $payment->payment_date);
                $available -= $amount;
                $created[] = $allocationId;
            }

            $allocated = round((float) $payment->amount - $available, 4);
            DB::table('payments')->where('id', $paymentId)->update([
                'allocated_amount' => $allocated,
                'status' => $allocated <= 0 ? 'posted' : ($allocated + 0.0001 >= (float) $payment->amount ? 'fully_allocated' : 'partially_allocated'),
                'updated_at' => now(),
                'row_version' => ((int) $payment->row_version) + 1,
            ]);

            return $created;
        });
    }
}
