<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Application\Support\FinancialServiceSupport;
use Modules\Payment\Application\Services\PaymentAllocationService;
use Modules\Payment\Application\Services\PaymentService;

final class VehicleServicePaymentService
{
    public function __construct(
        private readonly FinancialServiceSupport $support,
        private readonly PaymentService $payments,
        private readonly PaymentAllocationService $allocations,
    ) {}

    /** @return array{paid_amount: float, balance: float, payment_status: string, job_status: string, payments: Collection<int, object>} */
    public function visibility(int $jobCardId, string $currentJobStatus): array
    {
        $invoices = DB::table('vehicle_service_job_invoice_links')
            ->join('invoices', 'invoices.id', '=', 'vehicle_service_job_invoice_links.invoice_id')
            ->select(['invoices.id', 'invoices.settled_total', 'invoices.balance_total'])
            ->where('vehicle_service_job_invoice_links.tenant_id', $this->support->tenantId())
            ->where('vehicle_service_job_invoice_links.job_card_id', $jobCardId)
            ->where('vehicle_service_job_invoice_links.status', 'active')
            ->whereNull('vehicle_service_job_invoice_links.deleted_at')
            ->get();
        $paid = round((float) $invoices->sum('settled_total'), 4);
        $balance = round((float) $invoices->sum('balance_total'), 4);
        $status = $paid <= 0 ? 'unpaid' : ($balance <= 0.0001 ? 'paid' : 'partially_paid');

        $normal = DB::table('payment_allocations')
            ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
            ->join('vehicle_service_job_invoice_links', 'vehicle_service_job_invoice_links.invoice_id', '=', 'payment_allocations.invoice_id')
            ->select([
                'payments.id',
                'payments.payment_number',
                'payments.payment_date',
                'payments.status',
                'payment_allocations.allocated_amount',
                DB::raw("'payment' as allocation_type"),
            ])
            ->where('payment_allocations.tenant_id', $this->support->tenantId())
            ->where('vehicle_service_job_invoice_links.job_card_id', $jobCardId)
            ->where('payment_allocations.status', 'active')
            ->where('vehicle_service_job_invoice_links.status', 'active')
            ->get();
        $advances = DB::table('advance_payment_allocations')
            ->join('advance_payments', 'advance_payments.id', '=', 'advance_payment_allocations.advance_payment_id')
            ->join('vehicle_service_job_invoice_links', 'vehicle_service_job_invoice_links.invoice_id', '=', 'advance_payment_allocations.invoice_id')
            ->select([
                'advance_payments.id',
                'advance_payments.advance_number as payment_number',
                'advance_payments.advance_date as payment_date',
                'advance_payments.status',
                'advance_payment_allocations.allocated_amount',
                DB::raw("'advance' as allocation_type"),
            ])
            ->where('advance_payment_allocations.tenant_id', $this->support->tenantId())
            ->where('vehicle_service_job_invoice_links.job_card_id', $jobCardId)
            ->where('advance_payment_allocations.status', 'active')
            ->where('vehicle_service_job_invoice_links.status', 'active')
            ->get();

        return [
            'paid_amount' => $paid,
            'balance' => $invoices->isEmpty() ? 0.0 : $balance,
            'payment_status' => $status,
            'job_status' => $currentJobStatus === 'invoiced' && $status === 'paid' ? 'paid' : $currentJobStatus,
            'payments' => $normal->concat($advances)->values(),
        ];
    }

    /** @param array<string, mixed> $payload */
    public function createForJob(int $jobCardId, array $payload): object
    {
        return DB::transaction(function () use ($jobCardId, $payload): object {
            [$job, $invoice] = $this->lockedJobInvoice($jobCardId);
            $payment = $this->payments->create([
                ...$payload,
                'party_type' => 'customer',
                'party_id' => (int) $job->linked_customer_id,
                'direction' => 'inbound',
                'source_module' => 'vehicle_service',
                'source_type' => 'vehicle_service_job',
                'source_id' => $jobCardId,
                'source_reference' => $job->job_card_number,
                'allocations' => [[
                    'invoice_id' => (int) $invoice->id,
                    'allocated_amount' => $payload['allocated_amount'] ?? $payload['amount'],
                ]],
            ]);
            $this->syncLinks($jobCardId);

            return $payment;
        });
    }

    /** @param array<string, mixed> $payload */
    public function createAdvanceForJob(int $jobCardId, array $payload): object
    {
        $job = DB::table('vehicle_service_job_cards')
            ->where('tenant_id', $this->support->tenantId())
            ->whereNull('deleted_at')
            ->where('id', $jobCardId)
            ->first();
        if ($job === null) {
            abort(404);
        }

        return $this->payments->create([
            ...$payload,
            'party_type' => 'customer',
            'party_id' => (int) $job->linked_customer_id,
            'direction' => 'inbound',
            'source_module' => 'vehicle_service',
            'source_type' => 'vehicle_service_advance',
            'source_id' => $jobCardId,
            'source_reference' => $job->job_card_number,
            'allocations' => [],
        ]);
    }

    public function allocatePayment(int $jobCardId, int $paymentId, float $amount): object
    {
        return DB::transaction(function () use ($jobCardId, $paymentId, $amount): object {
            [, $invoice] = $this->lockedJobInvoice($jobCardId);
            if ($this->normalLinkExists($jobCardId, $paymentId, (int) $invoice->id)) {
                return $this->payments->find($paymentId);
            }
            if (DB::table('advance_payments')->where('tenant_id', $this->support->tenantId())->where('payment_id', $paymentId)->whereNull('deleted_at')->exists()) {
                throw ValidationException::withMessages(['payment_id' => ['This payment has already become an advance. Allocate the advance balance instead.']]);
            }
            $this->allocations->allocate($paymentId, [[
                'invoice_id' => (int) $invoice->id,
                'allocated_amount' => $amount,
            ]]);
            $this->syncLinks($jobCardId);

            return $this->payments->find($paymentId);
        });
    }

    public function allocateAdvance(int $jobCardId, int $advancePaymentId, float $amount): object
    {
        return DB::transaction(function () use ($jobCardId, $advancePaymentId, $amount): object {
            [, $invoice] = $this->lockedJobInvoice($jobCardId);
            if ($this->advanceLinkExists($jobCardId, $advancePaymentId, (int) $invoice->id)) {
                return $this->payments->findAdvance($advancePaymentId);
            }
            $this->allocations->allocateAdvance($advancePaymentId, [[
                'invoice_id' => (int) $invoice->id,
                'allocated_amount' => $amount,
            ]]);
            $this->syncLinks($jobCardId);

            return $this->payments->findAdvance($advancePaymentId);
        });
    }

    public function syncLinks(int $jobCardId): void
    {
        $job = DB::table('vehicle_service_job_cards')
            ->where('tenant_id', $this->support->tenantId())
            ->where('id', $jobCardId)
            ->lockForUpdate()
            ->first();
        if ($job === null) {
            abort(404);
        }

        $normal = DB::table('payment_allocations')
            ->join('vehicle_service_job_invoice_links', 'vehicle_service_job_invoice_links.invoice_id', '=', 'payment_allocations.invoice_id')
            ->select(['payment_allocations.id', 'payment_allocations.payment_id', 'payment_allocations.allocated_amount'])
            ->where('payment_allocations.tenant_id', $this->support->tenantId())
            ->where('vehicle_service_job_invoice_links.job_card_id', $jobCardId)
            ->where('payment_allocations.status', 'active')
            ->where('vehicle_service_job_invoice_links.status', 'active')
            ->get();
        foreach ($normal as $allocation) {
            DB::table('vehicle_service_job_payment_links')->insertOrIgnore([
                'tenant_id' => $this->support->tenantId(),
                'organization_unit_id' => $job->organization_unit_id,
                'job_card_id' => $jobCardId,
                'payment_id' => (int) $allocation->payment_id,
                'payment_allocation_id' => (int) $allocation->id,
                'allocated_amount' => $allocation->allocated_amount,
                'status' => 'active',
                'linked_by' => $this->support->userId(),
                'linked_at' => now(),
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $advances = DB::table('advance_payment_allocations')
            ->join('vehicle_service_job_invoice_links', 'vehicle_service_job_invoice_links.invoice_id', '=', 'advance_payment_allocations.invoice_id')
            ->select(['advance_payment_allocations.id', 'advance_payment_allocations.allocated_amount'])
            ->where('advance_payment_allocations.tenant_id', $this->support->tenantId())
            ->where('vehicle_service_job_invoice_links.job_card_id', $jobCardId)
            ->where('advance_payment_allocations.status', 'active')
            ->where('vehicle_service_job_invoice_links.status', 'active')
            ->get();
        foreach ($advances as $allocation) {
            DB::table('vehicle_service_job_payment_links')->insertOrIgnore([
                'tenant_id' => $this->support->tenantId(),
                'organization_unit_id' => $job->organization_unit_id,
                'job_card_id' => $jobCardId,
                'advance_payment_allocation_id' => (int) $allocation->id,
                'advance_amount' => $allocation->allocated_amount,
                'allocated_amount' => $allocation->allocated_amount,
                'status' => 'active',
                'linked_by' => $this->support->userId(),
                'linked_at' => now(),
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $visibility = $this->visibility($jobCardId, (string) $job->status);
        DB::table('vehicle_service_job_cards')->where('id', $jobCardId)->update([
            'paid_amount' => $visibility['paid_amount'],
            'balance' => $visibility['balance'],
            'payment_status' => $visibility['payment_status'],
            'status' => $visibility['job_status'],
            'updated_at' => now(),
        ]);
        if ($visibility['job_status'] !== $job->status) {
            DB::table('vehicle_service_job_status_histories')->insert([
                'tenant_id' => $this->support->tenantId(),
                'organization_unit_id' => $job->organization_unit_id,
                'entity_type' => 'job_card',
                'entity_id' => $jobCardId,
                'workflow_action' => 'settlement',
                'from_status' => $job->status,
                'to_status' => $visibility['job_status'],
                'changed_by' => $this->support->userId(),
                'changed_at' => now(),
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /** @return array{0: object, 1: object} */
    private function lockedJobInvoice(int $jobCardId): array
    {
        $job = DB::table('vehicle_service_job_cards')
            ->where('tenant_id', $this->support->tenantId())
            ->whereNull('deleted_at')
            ->where('id', $jobCardId)
            ->lockForUpdate()
            ->first();
        if ($job === null) {
            abort(404);
        }
        $invoice = DB::table('invoices')
            ->join('vehicle_service_job_invoice_links', 'vehicle_service_job_invoice_links.invoice_id', '=', 'invoices.id')
            ->select('invoices.*')
            ->where('vehicle_service_job_invoice_links.tenant_id', $this->support->tenantId())
            ->where('vehicle_service_job_invoice_links.job_card_id', $jobCardId)
            ->where('vehicle_service_job_invoice_links.status', 'active')
            ->whereNull('vehicle_service_job_invoice_links.deleted_at')
            ->lockForUpdate()
            ->first();
        if ($invoice === null) {
            throw ValidationException::withMessages(['invoice' => ['Generate the service invoice before allocating payment.']]);
        }

        return [$job, $invoice];
    }

    private function normalLinkExists(int $jobCardId, int $paymentId, int $invoiceId): bool
    {
        return DB::table('payment_allocations')
            ->join('vehicle_service_job_payment_links', 'vehicle_service_job_payment_links.payment_allocation_id', '=', 'payment_allocations.id')
            ->where('vehicle_service_job_payment_links.tenant_id', $this->support->tenantId())
            ->where('vehicle_service_job_payment_links.job_card_id', $jobCardId)
            ->where('payment_allocations.payment_id', $paymentId)
            ->where('payment_allocations.invoice_id', $invoiceId)
            ->where('payment_allocations.status', 'active')
            ->exists();
    }

    private function advanceLinkExists(int $jobCardId, int $advancePaymentId, int $invoiceId): bool
    {
        return DB::table('advance_payment_allocations')
            ->join('vehicle_service_job_payment_links', 'vehicle_service_job_payment_links.advance_payment_allocation_id', '=', 'advance_payment_allocations.id')
            ->where('vehicle_service_job_payment_links.tenant_id', $this->support->tenantId())
            ->where('vehicle_service_job_payment_links.job_card_id', $jobCardId)
            ->where('advance_payment_allocations.advance_payment_id', $advancePaymentId)
            ->where('advance_payment_allocations.invoice_id', $invoiceId)
            ->where('advance_payment_allocations.status', 'active')
            ->exists();
    }
}
