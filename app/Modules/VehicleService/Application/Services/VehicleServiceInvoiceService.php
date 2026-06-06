<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Application\Support\FinancialServiceSupport;
use Modules\Invoice\Application\Services\InvoiceService;

final class VehicleServiceInvoiceService
{
    public function __construct(
        private readonly FinancialServiceSupport $support,
        private readonly InvoiceService $invoices,
    ) {}

    public function generateFinalInvoice(int $jobCardId): object
    {
        return DB::transaction(function () use ($jobCardId): object {
            $job = $this->lockedJob($jobCardId);
            $existing = DB::table('vehicle_service_job_invoice_links')
                ->where('tenant_id', $this->support->tenantId())
                ->where('job_card_id', $jobCardId)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->first();
            if ($existing !== null) {
                return $this->invoices->find((int) $existing->invoice_id);
            }
            if ($job->status !== 'completed') {
                throw ValidationException::withMessages(['status' => ['Only completed jobs can be invoiced.']]);
            }
            if ($job->linked_customer_id === null) {
                throw ValidationException::withMessages(['linked_customer_id' => ['A customer is required before invoicing this job.']]);
            }

            $lines = $this->billableLines($jobCardId);
            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['lines' => ['The job has no billable lines.']]);
            }

            $invoice = $this->invoices->create([
                'organization_unit_id' => $job->organization_unit_id,
                'external_reference_number' => $job->job_card_number,
                'document_type' => 'service_invoice',
                'business_context' => 'vehicle_service',
                'ledger_direction' => 'receivable',
                'balance_effect' => 'increase',
                'customer_id' => (int) $job->linked_customer_id,
                'invoice_date' => now()->toDateString(),
                'notes' => 'Generated from vehicle service job '.$job->job_card_number,
                'adjustments' => $this->headerAdjustments($job),
                'lines' => $lines->all(),
            ]);
            $invoice = $this->invoices->issue((int) $invoice->id);

            DB::table('vehicle_service_job_invoice_links')->insert([
                'tenant_id' => $this->support->tenantId(),
                'organization_unit_id' => $job->organization_unit_id,
                'job_card_id' => $jobCardId,
                'invoice_id' => (int) $invoice->id,
                'invoice_type' => 'service_invoice',
                'direction' => 'outbound',
                'status' => 'active',
                'linked_by' => $this->support->userId(),
                'linked_at' => now(),
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('vehicle_service_job_cards')->where('id', $jobCardId)->update([
                'status' => 'invoiced',
                'invoice_status' => 'invoiced',
                'finance_status' => 'posted',
                'updated_by' => $this->support->userId(),
                'updated_at' => now(),
                'row_version' => ((int) $job->row_version) + 1,
            ]);
            $this->recordStatus($job, 'invoiced');

            return $invoice;
        });
    }

    /** @return Collection<int, array<string, mixed>> */
    private function billableLines(int $jobCardId): Collection
    {
        $parts = DB::table('vehicle_service_job_card_lines')
            ->leftJoin('items', 'items.id', '=', 'vehicle_service_job_card_lines.item_id')
            ->select(['vehicle_service_job_card_lines.*', 'items.name as catalog_name'])
            ->where('vehicle_service_job_card_lines.tenant_id', $this->support->tenantId())
            ->where('vehicle_service_job_card_lines.job_card_id', $jobCardId)
            ->orderBy('vehicle_service_job_card_lines.id')
            ->get()
            ->map(fn (object $line): array => $this->invoiceLine($line, 'inventory_item'));
        $labor = DB::table('vehicle_service_labor_items')
            ->leftJoin('items', 'items.id', '=', 'vehicle_service_labor_items.item_id')
            ->select(['vehicle_service_labor_items.*', 'items.name as catalog_name'])
            ->where('vehicle_service_labor_items.tenant_id', $this->support->tenantId())
            ->where('vehicle_service_labor_items.job_card_id', $jobCardId)
            ->orderBy('vehicle_service_labor_items.id')
            ->get()
            ->map(fn (object $line): array => $this->invoiceLine($line, 'labor'));
        $nonInventory = DB::table('vehicle_service_non_inventory_items')
            ->where('tenant_id', $this->support->tenantId())
            ->where('job_card_id', $jobCardId)
            ->where('is_billable', true)
            ->orderBy('id')
            ->get()
            ->map(fn (object $line): array => $this->invoiceLine($line, 'non_inventory_item'));

        return $parts->concat($labor)->concat($nonInventory)->values();
    }

    /** @return array<string, mixed> */
    private function invoiceLine(object $line, string $type): array
    {
        return [
            'line_type' => $type,
            'item_id' => $line->item_id ?? null,
            'uom_id' => $line->uom_id,
            'description' => $line->description ?? $line->name ?? $line->catalog_name ?? null,
            'quantity' => (float) $line->quantity,
            'unit_price' => (float) $line->unit_price,
            'discount_total' => (float) $line->discount_amount,
            'tax_total' => (float) $line->tax_amount,
            'charge_total' => 0,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function headerAdjustments(object $job): array
    {
        return collect([
            ['adjustment_type' => 'discount', 'effect' => 'deduct', 'amount' => $job->header_discount_amount, 'name' => 'Service header discount'],
            ['adjustment_type' => 'tax', 'effect' => 'add', 'amount' => $job->header_tax_amount, 'name' => 'Service header tax'],
            ['adjustment_type' => 'charge', 'effect' => 'add', 'amount' => $job->header_charge_total, 'name' => 'Service header charge'],
            ['adjustment_type' => 'debit_adjustment', 'effect' => 'add', 'amount' => $job->header_debit_adjustment_total, 'name' => 'Service debit adjustment'],
            ['adjustment_type' => 'credit_adjustment', 'effect' => 'deduct', 'amount' => $job->header_credit_adjustment_total, 'name' => 'Service credit adjustment'],
        ])->filter(fn (array $adjustment): bool => (float) $adjustment['amount'] > 0)->values()->all();
    }

    private function lockedJob(int $jobCardId): object
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

        return $job;
    }

    private function recordStatus(object $job, string $status): void
    {
        DB::table('vehicle_service_job_status_histories')->insert([
            'tenant_id' => $this->support->tenantId(),
            'organization_unit_id' => $job->organization_unit_id,
            'entity_type' => 'job_card',
            'entity_id' => (int) $job->id,
            'workflow_action' => $status,
            'from_status' => $job->status,
            'to_status' => $status,
            'changed_by' => $this->support->userId(),
            'changed_at' => now(),
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
