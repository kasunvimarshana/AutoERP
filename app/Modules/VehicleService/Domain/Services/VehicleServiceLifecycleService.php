<?php

declare(strict_types=1);

namespace Modules\VehicleService\Domain\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Domain\Services\JournalEntryService;
use Modules\Inventory\Domain\Services\InventoryTransactionService;
use Modules\Sequence\Domain\Services\SequenceService;
use Modules\VehicleService\Domain\Events\JobCardCompleted;
use Modules\VehicleService\Domain\Events\JobCardCreated;
use Modules\VehicleService\Domain\Events\ServiceInvoicePosted;

final class VehicleServiceLifecycleService
{
    public function __construct(
        private readonly InventoryTransactionService $inventory,
        private readonly JournalEntryService $journals,
        private readonly SequenceService $sequences,
        private readonly VehicleServiceCalculationService $calculator,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function create(array $payload): array
    {
        return DB::transaction(function () use ($payload): array {
            $inventoryLines = $payload['inventory_lines'] ?? $payload['lines'] ?? [];
            $laborItems = $payload['labor_items'] ?? [];
            $nonInventoryItems = $payload['non_inventory_items'] ?? [];
            $laborAssignments = $payload['labor_assignments'] ?? [];
            unset($payload['inventory_lines'], $payload['lines'], $payload['labor_items'], $payload['non_inventory_items'], $payload['labor_assignments']);

            $tenantId = (int) $payload['tenant_id'];
            $organizationUnitId = isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null;
            $payload['job_card_number'] ??= $this->sequences->next('service_job_card', $tenantId, $organizationUnitId);
            $payload['status'] = $payload['status'] ?? 'open';

            $jobCardId = (int) DB::table('vehicle_service_job_cards')->insertGetId(array_merge($payload, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            foreach (is_array($inventoryLines) ? $inventoryLines : [] as $line) {
                DB::table('vehicle_service_job_card_lines')->insert(array_merge($line, [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'job_card_id' => $jobCardId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            foreach (is_array($laborItems) ? $laborItems : [] as $line) {
                DB::table('vehicle_service_labor_items')->insert(array_merge($line, [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'job_card_id' => $jobCardId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            foreach (is_array($nonInventoryItems) ? $nonInventoryItems : [] as $line) {
                DB::table('vehicle_service_non_inventory_items')->insert(array_merge($line, [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'job_card_id' => $jobCardId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            foreach (is_array($laborAssignments) ? $laborAssignments : [] as $assignment) {
                DB::table('vehicle_service_labor_assignments')->insert(array_merge($assignment, [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'job_card_id' => $jobCardId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            $record = $this->calculator->recalculateJobCard($jobCardId);
            event(new JobCardCreated($jobCardId));

            return $record;
        });
    }

    public function open(int $jobCardId): array
    {
        return DB::transaction(function () use ($jobCardId): array {
            $jobCard = DB::table('vehicle_service_job_cards')->lockForUpdate()->find($jobCardId);
            if ($jobCard === null) {
                throw new \RuntimeException('Job card not found.');
            }

            DB::table('vehicle_service_job_cards')->where('id', $jobCardId)->update([
                'status' => 'in_progress',
                'start_datetime' => $jobCard->start_datetime ?? now(),
                'row_version' => (int) $jobCard->row_version + 1,
                'updated_at' => now(),
            ]);

            event(new JobCardCreated($jobCardId));

            return (array) DB::table('vehicle_service_job_cards')->find($jobCardId);
        });
    }

    /** @param array<string, mixed> $payload */
    public function complete(int $jobCardId, array $payload = []): array
    {
        return DB::transaction(function () use ($jobCardId): array {
            $jobCard = DB::table('vehicle_service_job_cards')->lockForUpdate()->find($jobCardId);
            if ($jobCard === null) {
                throw new \RuntimeException('Job card not found.');
            }

            if (! in_array($jobCard->status, ['open', 'in_progress', 'waiting_parts'], true)) {
                throw new \RuntimeException('Only open or in-progress job cards can be completed.');
            }

            $this->calculator->recalculateJobCard($jobCardId);

            DB::table('vehicle_service_job_cards')->where('id', $jobCardId)->update([
                'status' => 'completed',
                'completed_datetime' => now(),
                'row_version' => (int) $jobCard->row_version + 1,
                'updated_at' => now(),
            ]);

            event(new JobCardCompleted($jobCardId));

            return (array) DB::table('vehicle_service_job_cards')->find($jobCardId);
        });
    }

    public function createInvoice(int $jobCardId): array
    {
        return DB::transaction(function () use ($jobCardId): array {
            $jobCard = (array) DB::table('vehicle_service_job_cards')->lockForUpdate()->find($jobCardId);
            if ($jobCard === []) {
                throw new \RuntimeException('Job card not found.');
            }

            if (($jobCard['status'] ?? null) !== 'completed') {
                throw new \RuntimeException('Only completed job cards can be invoiced.');
            }

            $jobCard = $this->calculator->recalculateJobCard($jobCardId);

            $invoiceId = (int) DB::table('invoices')->insertGetId([
                'tenant_id' => $jobCard['tenant_id'],
                'organization_unit_id' => $jobCard['organization_unit_id'],
                'direction' => 'outbound',
                'invoice_type' => 'vehicle_service',
                'invoice_number' => $this->sequences->next('service_invoice', (int) $jobCard['tenant_id'], $jobCard['organization_unit_id'] === null ? null : (int) $jobCard['organization_unit_id']),
                'status' => 'approved',
                'party_type' => 'customer',
                'party_id' => $jobCard['customer_id'],
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'currency_id' => $jobCard['currency_id'],
                'exchange_rate' => $jobCard['exchange_rate'],
                'subtotal' => $jobCard['subtotal'] + $jobCard['non_inventory_item_subtotal'] + $jobCard['labor_item_subtotal'],
                'line_tax_total' => $jobCard['line_tax_total'] + $jobCard['non_inventory_item_tax_total'] + $jobCard['labor_item_tax_total'],
                'line_discount_total' => $jobCard['line_discount_total'] + $jobCard['non_inventory_item_discount_total'] + $jobCard['labor_item_discount_total'],
                'header_discount_type' => $jobCard['header_discount_type'],
                'header_discount_value' => $jobCard['header_discount_value'],
                'header_discount_amount' => $jobCard['header_discount_amount'],
                'header_tax_group_id' => $jobCard['header_tax_group_id'],
                'header_tax_amount' => $jobCard['header_tax_amount'],
                'discount_total' => $jobCard['discount_total'],
                'tax_total' => $jobCard['tax_total'],
                'grand_total' => $jobCard['grand_total'],
                'paid_amount' => 0,
                'balance' => $jobCard['grand_total'],
                'created_by' => $jobCard['created_by'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('invoice_references')->insert([
                'tenant_id' => $jobCard['tenant_id'],
                'organization_unit_id' => $jobCard['organization_unit_id'],
                'invoice_id' => $invoiceId,
                'document_type' => 'JOB_CARD',
                'document_id' => $jobCardId,
                'currency_id' => $jobCard['currency_id'],
                'exchange_rate' => $jobCard['exchange_rate'],
                'grand_total' => $jobCard['grand_total'],
                'balance' => $jobCard['grand_total'],
                'created_by' => $jobCard['created_by'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $invoice = (array) DB::table('invoices')->find($invoiceId);
            $entryId = $this->journals->createServiceInvoice($invoice);
            DB::table('invoices')->where('id', $invoiceId)->update(['journal_entry_id' => $entryId, 'updated_at' => now()]);
            DB::table('vehicle_service_job_cards')->where('id', $jobCardId)->update(['status' => 'invoiced', 'updated_at' => now()]);

            event(new ServiceInvoicePosted($invoiceId));

            return (array) DB::table('invoices')->find($invoiceId);
        });
    }

    /** @param array<string, mixed> $payload */
    public function createPayment(array $payload): array
    {
        return DB::transaction(function () use ($payload): array {
            $payload['payment_number'] ??= $this->sequences->next('service_payment', (int) $payload['tenant_id'], $payload['organization_unit_id'] ?? null);
            $payload['direction'] = 'inbound';
            $payload['party_type'] = 'customer';
            $payload['status'] = 'posted';
            $payload['base_amount'] = round((float) $payload['amount'] * (float) ($payload['exchange_rate'] ?? 1), 4);
            $allocations = $payload['allocations'] ?? [];
            unset($payload['allocations']);

            $paymentId = (int) DB::table('payments')->insertGetId(array_merge($payload, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            foreach (is_array($allocations) ? $allocations : [] as $allocation) {
                DB::table('payment_allocations')->insert(array_merge($allocation, [
                    'tenant_id' => $payload['tenant_id'],
                    'organization_unit_id' => $payload['organization_unit_id'] ?? null,
                    'payment_id' => $paymentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));

                if (($allocation['document_type'] ?? null) !== 'INVOICE') {
                    continue;
                }

                $invoice = DB::table('invoices')->lockForUpdate()->find((int) $allocation['document_id']);
                if ($invoice === null) {
                    continue;
                }

                $paid = round((float) $invoice->paid_amount + (float) $allocation['allocated_amount'], 4);
                DB::table('invoices')->where('id', (int) $invoice->id)->update([
                    'paid_amount' => $paid,
                    'balance' => round((float) $invoice->grand_total - $paid, 4),
                    'status' => $paid >= (float) $invoice->grand_total ? 'paid' : 'partially_paid',
                    'row_version' => (int) $invoice->row_version + 1,
                    'updated_at' => now(),
                ]);

                $this->refreshJobCardPayment((int) $invoice->id);
            }

            $payment = (array) DB::table('payments')->find($paymentId);
            $entryId = $this->journals->createPaymentEntry($payment);
            DB::table('payments')->where('id', $paymentId)->update(['journal_entry_id' => $entryId, 'updated_at' => now()]);

            return (array) DB::table('payments')->find($paymentId);
        });
    }

    private function refreshJobCardPayment(int $invoiceId): void
    {
        $reference = DB::table('invoice_references')
            ->where('invoice_id', $invoiceId)
            ->where('document_type', 'JOB_CARD')
            ->first();

        if ($reference === null) {
            return;
        }

        $invoiceIds = DB::table('invoice_references')
            ->where('document_type', 'JOB_CARD')
            ->where('document_id', (int) $reference->document_id)
            ->pluck('invoice_id');
        $paid = (float) DB::table('invoices')->whereIn('id', $invoiceIds)->sum('paid_amount');
        $jobCard = DB::table('vehicle_service_job_cards')->lockForUpdate()->find((int) $reference->document_id);
        if ($jobCard === null) {
            return;
        }

        DB::table('vehicle_service_job_cards')->where('id', (int) $jobCard->id)->update([
            'paid_amount' => $paid,
            'balance' => round((float) $jobCard->grand_total - $paid, 4),
            'row_version' => (int) $jobCard->row_version + 1,
            'updated_at' => now(),
        ]);
    }
}
