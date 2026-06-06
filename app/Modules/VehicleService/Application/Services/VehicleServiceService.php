<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Application\Support\FinancialServiceSupport;

final class VehicleServiceService
{
    public function __construct(
        private readonly FinancialServiceSupport $support,
        private readonly VehicleServiceInventoryService $inventory,
        private readonly VehicleServiceInvoiceService $invoiceOrchestrator,
        private readonly VehicleServicePaymentService $paymentOrchestrator,
    ) {}

    /** @param array<string, mixed> $filters */
    public function paginateServiceTypes(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return DB::table('vehicle_service_types')
            ->select(['id', 'name', 'code', 'description', 'standard_hours', 'is_active', 'created_at'])
            ->where('tenant_id', $this->support->tenantId())
            ->whereNull('deleted_at')
            ->when(isset($filters['is_active']), fn (Builder $query): Builder => $query->where('is_active', (bool) $filters['is_active']))
            ->when($search !== '', fn (Builder $query): Builder => $query->where(fn (Builder $nested): Builder => $nested
                ->where('name', 'like', "%$search%")
                ->orWhere('code', 'like', "%$search%")))
            ->orderBy('name')
            ->paginate(min((int) ($filters['per_page'] ?? 20), 100), ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    public function findServiceType(int $id): object
    {
        return $this->tenantRow('vehicle_service_types', $id);
    }

    /** @param array<string, mixed> $payload */
    public function createServiceType(array $payload): object
    {
        $id = DB::table('vehicle_service_types')->insertGetId([
            'tenant_id' => $this->support->tenantId(),
            'organization_unit_id' => $this->support->organizationUnitId($payload['organization_unit_id'] ?? null),
            'name' => $payload['name'],
            'code' => $payload['code'] ?? null,
            'description' => $payload['description'] ?? null,
            'standard_hours' => $payload['standard_hours'] ?? null,
            'is_active' => $payload['is_active'] ?? true,
            'created_by' => $this->support->userId(),
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->findServiceType($id);
    }

    /** @param array<string, mixed> $payload */
    public function updateServiceType(int $id, array $payload): object
    {
        $serviceType = $this->tenantRow('vehicle_service_types', $id, true);
        DB::table('vehicle_service_types')->where('id', $id)->update([
            'organization_unit_id' => array_key_exists('organization_unit_id', $payload)
                ? $this->support->organizationUnitId($payload['organization_unit_id'])
                : $serviceType->organization_unit_id,
            'name' => $payload['name'] ?? $serviceType->name,
            'code' => array_key_exists('code', $payload) ? $payload['code'] : $serviceType->code,
            'description' => array_key_exists('description', $payload) ? $payload['description'] : $serviceType->description,
            'standard_hours' => array_key_exists('standard_hours', $payload) ? $payload['standard_hours'] : $serviceType->standard_hours,
            'is_active' => $payload['is_active'] ?? $serviceType->is_active,
            'updated_by' => $this->support->userId(),
            'row_version' => ((int) $serviceType->row_version) + 1,
            'updated_at' => now(),
        ]);

        return $this->findServiceType($id);
    }

    public function deleteServiceType(int $id): void
    {
        $this->tenantRow('vehicle_service_types', $id, true);
        if (DB::table('vehicle_service_job_cards')->where('tenant_id', $this->support->tenantId())->where('service_type_id', $id)->whereNull('deleted_at')->exists()) {
            throw ValidationException::withMessages(['service_type' => ['Service types used by job cards cannot be deleted.']]);
        }

        DB::table('vehicle_service_types')->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);
    }

    /** @param array<string, mixed> $filters */
    public function paginateJobs(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return DB::table('vehicle_service_job_cards')
            ->leftJoin('customers', 'customers.id', '=', 'vehicle_service_job_cards.linked_customer_id')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'vehicle_service_job_cards.vehicle_id')
            ->leftJoin('vehicle_service_types', 'vehicle_service_types.id', '=', 'vehicle_service_job_cards.service_type_id')
            ->select([
                'vehicle_service_job_cards.*',
                'customers.customer_name',
                'vehicles.registration_number',
                'vehicle_service_types.name as service_type_name',
            ])
            ->where('vehicle_service_job_cards.tenant_id', $this->support->tenantId())
            ->whereNull('vehicle_service_job_cards.deleted_at')
            ->when(isset($filters['status']), fn (Builder $query): Builder => $query->where('vehicle_service_job_cards.status', (string) $filters['status']))
            ->when($search !== '', fn (Builder $query): Builder => $query->where(fn (Builder $nested): Builder => $nested
                ->where('vehicle_service_job_cards.job_card_number', 'like', "%$search%")
                ->orWhere('vehicle_service_job_cards.reference', 'like', "%$search%")
                ->orWhere('customers.customer_name', 'like', "%$search%")
                ->orWhere('vehicles.registration_number', 'like', "%$search%")))
            ->orderByDesc('vehicle_service_job_cards.id')
            ->paginate(min((int) ($filters['per_page'] ?? 20), 100), ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    public function findJob(int $id): object
    {
        $job = DB::table('vehicle_service_job_cards')
            ->leftJoin('customers', 'customers.id', '=', 'vehicle_service_job_cards.linked_customer_id')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'vehicle_service_job_cards.vehicle_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'vehicle_service_job_cards.warehouse_id')
            ->leftJoin('vehicle_service_types', 'vehicle_service_types.id', '=', 'vehicle_service_job_cards.service_type_id')
            ->select([
                'vehicle_service_job_cards.*',
                'customers.customer_name',
                'vehicles.registration_number',
                'vehicles.make as vehicle_make',
                'vehicles.model as vehicle_model',
                'warehouses.name as warehouse_name',
                'vehicle_service_types.name as service_type_name',
            ])
            ->where('vehicle_service_job_cards.tenant_id', $this->support->tenantId())
            ->whereNull('vehicle_service_job_cards.deleted_at')
            ->where('vehicle_service_job_cards.id', $id)
            ->first();

        if ($job === null) {
            abort(404);
        }

        $job->parts = $this->partLines($id);
        $job->labor_items = $this->laborLines($id);
        $job->non_inventory_items = $this->nonInventoryLines($id);
        $job->invoice_links = DB::table('vehicle_service_job_invoice_links')
            ->join('invoices', 'invoices.id', '=', 'vehicle_service_job_invoice_links.invoice_id')
            ->select([
                'vehicle_service_job_invoice_links.id',
                'vehicle_service_job_invoice_links.invoice_id',
                'invoices.invoice_number',
                'invoices.status',
                'invoices.grand_total',
                'invoices.settled_total',
                'invoices.balance_total',
            ])
            ->where('vehicle_service_job_invoice_links.tenant_id', $this->support->tenantId())
            ->where('vehicle_service_job_invoice_links.job_card_id', $id)
            ->where('vehicle_service_job_invoice_links.status', 'active')
            ->whereNull('vehicle_service_job_invoice_links.deleted_at')
            ->get();
        $payment = $this->paymentOrchestrator->visibility($id, (string) $job->status);
        $job->paid_amount = $payment['paid_amount'];
        $job->balance = $job->invoice_links->isEmpty() ? $job->grand_total : $payment['balance'];
        $job->payment_status = $payment['payment_status'];
        $job->status = $payment['job_status'];
        $job->payments = $payment['payments'];

        return $job;
    }

    /** @param array<string, mixed> $payload */
    public function createJob(array $payload): object
    {
        return $this->storeJob($payload);
    }

    /** @param array<string, mixed> $payload */
    public function updateJob(int $id, array $payload): object
    {
        return DB::transaction(function () use ($id, $payload): object {
            $job = $this->tenantRow('vehicle_service_job_cards', $id, true);
            if (! in_array($job->status, ['open', 'in_progress'], true) || $job->inventory_status === 'consumed') {
                throw ValidationException::withMessages(['status' => ['Only open jobs without consumed inventory can be edited.']]);
            }

            return $this->storeJob(array_merge((array) $job, $payload), $id);
        });
    }

    public function startJob(int $id): object
    {
        return DB::transaction(function () use ($id): object {
            $job = $this->tenantRow('vehicle_service_job_cards', $id, true);
            if ($job->status === 'in_progress') {
                return $this->findJob($id);
            }
            if ($job->status !== 'open') {
                throw ValidationException::withMessages(['status' => ['Only open jobs can be started.']]);
            }
            $this->updateJobStatus($job, 'in_progress', ['start_datetime' => $job->start_datetime ?? now()]);

            return $this->findJob($id);
        });
    }

    public function consumeInventory(int $id): object
    {
        return DB::transaction(function () use ($id): object {
            $job = $this->tenantRow('vehicle_service_job_cards', $id, true);
            if (in_array($job->status, ['cancelled'], true)) {
                throw ValidationException::withMessages(['status' => ['Cancelled jobs cannot consume inventory.']]);
            }
            $this->inventory->consumeJobParts($job);

            return $this->findJob($id);
        });
    }

    public function completeJob(int $id): object
    {
        return DB::transaction(function () use ($id): object {
            $job = $this->tenantRow('vehicle_service_job_cards', $id, true);
            if (in_array($job->status, ['completed', 'invoiced', 'paid'], true)) {
                return $this->findJob($id);
            }
            if (! in_array($job->status, ['open', 'in_progress'], true)) {
                throw ValidationException::withMessages(['status' => ['Only open or in-progress jobs can be completed.']]);
            }
            if ($this->allBillableLines($id)->isEmpty()) {
                throw ValidationException::withMessages(['lines' => ['At least one part, labor, or non-inventory item is required.']]);
            }

            $this->inventory->consumeJobParts($job);
            $this->updateJobStatus($job, 'completed', ['completed_datetime' => now()]);

            return $this->findJob($id);
        });
    }

    public function cancelJob(int $id, ?string $reason = null): object
    {
        return DB::transaction(function () use ($id, $reason): object {
            $job = $this->tenantRow('vehicle_service_job_cards', $id, true);
            if ($job->inventory_status === 'consumed' || in_array($job->status, ['completed', 'invoiced', 'paid'], true)) {
                throw ValidationException::withMessages(['status' => ['Jobs with consumed inventory or completed billing cannot be cancelled without reversal.']]);
            }
            if ($job->status !== 'cancelled') {
                $this->updateJobStatus($job, 'cancelled', ['cancelled_at' => now()], $reason);
            }

            return $this->findJob($id);
        });
    }

    public function deleteJob(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $job = $this->tenantRow('vehicle_service_job_cards', $id, true);
            if ($job->status !== 'open' || $job->inventory_status !== 'pending') {
                throw ValidationException::withMessages(['status' => ['Only untouched open jobs can be deleted.']]);
            }
            DB::table('vehicle_service_job_cards')->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);
        });
    }

    public function createInvoice(int $id): object
    {
        return $this->invoiceOrchestrator->generateFinalInvoice($id);
    }

    /** @return array<string, mixed> */
    public function dashboard(): array
    {
        $tenantId = $this->support->tenantId();

        return [
            'open_jobs' => DB::table('vehicle_service_job_cards')->where('tenant_id', $tenantId)->whereNull('deleted_at')->whereIn('status', ['open', 'in_progress'])->count(),
            'completed_jobs' => DB::table('vehicle_service_job_cards')->where('tenant_id', $tenantId)->whereNull('deleted_at')->where('status', 'completed')->count(),
            'pending_invoice_jobs' => DB::table('vehicle_service_job_cards')->where('tenant_id', $tenantId)->whereNull('deleted_at')->where('status', 'completed')->where('invoice_status', 'pending')->count(),
            'unpaid_amount' => $this->money(DB::table('invoices')
                ->join('vehicle_service_job_invoice_links', 'vehicle_service_job_invoice_links.invoice_id', '=', 'invoices.id')
                ->where('invoices.tenant_id', $tenantId)
                ->where('vehicle_service_job_invoice_links.status', 'active')
                ->sum('invoices.balance_total')),
            'service_value' => $this->money(DB::table('vehicle_service_job_cards')->where('tenant_id', $tenantId)->whereNull('deleted_at')->whereNotIn('status', ['cancelled'])->sum('grand_total')),
        ];
    }

    /** @param array<string, mixed> $filters */
    public function lookup(string $type, array $filters = []): array
    {
        $tenantId = $this->support->tenantId();
        $search = trim((string) ($filters['search'] ?? ''));
        $limit = min((int) ($filters['limit'] ?? 50), 100);

        if ($type === 'completed-jobs') {
            return $this->completedJobLookup($search, $limit);
        }
        if ($type === 'job-billable-lines') {
            return $this->jobBillableLineLookup((int) ($filters['job_card_id'] ?? 0), $limit);
        }
        if ($type === 'job-inventory-lines') {
            return $this->jobInventoryLineLookup((int) ($filters['job_card_id'] ?? 0), $limit);
        }
        if ($type === 'outstanding-service-invoices') {
            return $this->outstandingServiceInvoiceLookup($filters, $search, $limit);
        }

        $query = match ($type) {
            'customers' => DB::table('customers')->select(['id', 'customer_code as code', 'customer_name as name'])->where('tenant_id', $tenantId)->where('status', 'active')->whereNull('deleted_at'),
            'vehicles' => DB::table('vehicles')->select(['id', 'vehicle_code as code', 'registration_number as name', 'make', 'model'])->where('tenant_id', $tenantId)->where('status', 'active')->whereNull('deleted_at'),
            'service-types' => DB::table('vehicle_service_types')->select(['id', 'code', 'name', 'standard_hours'])->where('tenant_id', $tenantId)->where('is_active', true)->whereNull('deleted_at'),
            'items' => DB::table('items')->select(['id', 'item_code as code', 'name', 'base_uom_id', 'sales_uom_id', 'cost_price', 'sales_price', 'track_inventory', 'is_service_item'])->where('tenant_id', $tenantId)->where('status', 'active')->whereNull('deleted_at'),
            'uoms' => DB::table('unit_of_measures')->select(['id', 'uom_code as code', 'name', 'symbol'])->where('tenant_id', $tenantId)->where('status', 'active')->whereNull('deleted_at'),
            'warehouses' => DB::table('warehouses')->select(['id', 'code', 'name'])->where('tenant_id', $tenantId)->where('is_active', true)->whereNull('deleted_at'),
            'employees' => DB::table('employees')
                ->select(['id', 'employee_code as code', DB::raw('coalesce(display_name, full_name, first_name) as name')])
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->whereNull('deleted_at'),
            default => throw ValidationException::withMessages(['type' => ['Unsupported lookup type.']]),
        };

        return $query
            ->when($search !== '', fn (Builder $builder): Builder => $builder->where(fn (Builder $nested): Builder => $nested
                ->where('name', 'like', "%$search%")
                ->orWhere('code', 'like', "%$search%")))
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();
    }

    /** @param array<string, mixed> $payload */
    private function storeJob(array $payload, ?int $id = null): object
    {
        return DB::transaction(function () use ($payload, $id): object {
            $tenantId = $this->support->tenantId();
            $organizationUnitId = $this->support->organizationUnitId($payload['organization_unit_id'] ?? null);
            $this->assertJobReferences($payload);
            $totals = $this->calculateTotals($payload);
            $attributes = [
                'organization_unit_id' => $organizationUnitId,
                'job_card_number' => $payload['job_card_number'] ?? $this->support->nextNumber('JOB', 'vehicle_service_job_cards', 'job_card_number'),
                'reference' => $payload['reference'] ?? null,
                'service_type_id' => $payload['service_type_id'] ?? null,
                'vehicle_id' => $payload['vehicle_id'],
                'service_customer_type' => 'customer',
                'service_customer_id' => $payload['linked_customer_id'],
                'linked_customer_id' => $payload['linked_customer_id'],
                'billing_customer_type' => 'customer',
                'billing_customer_id' => $payload['linked_customer_id'],
                'payer_type' => 'customer',
                'payer_id' => $payload['linked_customer_id'],
                'warehouse_id' => $payload['warehouse_id'],
                'priority' => $payload['priority'] ?? 'medium',
                'reported_issue' => $payload['reported_issue'] ?? null,
                'resolution_notes' => $payload['resolution_notes'] ?? null,
                'technician_notes' => $payload['technician_notes'] ?? null,
                'start_datetime' => $payload['start_datetime'] ?? null,
                'promised_delivery_date_time' => $payload['promised_delivery_date_time'] ?? null,
                'estimated_hours' => $payload['estimated_hours'] ?? null,
                'start_odometer' => $payload['start_odometer'] ?? null,
                'end_odometer' => $payload['end_odometer'] ?? null,
                'next_service_odometer' => $payload['next_service_odometer'] ?? null,
                'next_service_date' => $payload['next_service_date'] ?? null,
                'header_discount_type' => $payload['header_discount_type'] ?? null,
                'header_discount_value' => $payload['header_discount_value'] ?? null,
                ...$totals,
                'advance_amount' => 0,
                'paid_amount' => 0,
                'refund_amount' => 0,
                'write_off_amount' => 0,
                'balance' => $totals['grand_total'],
                'notes' => $payload['notes'] ?? null,
                'updated_by' => $this->support->userId(),
                'updated_at' => now(),
            ];

            if ($id === null) {
                $id = DB::table('vehicle_service_job_cards')->insertGetId([
                    'tenant_id' => $tenantId,
                    'status' => 'open',
                    'inventory_status' => 'pending',
                    'invoice_status' => 'pending',
                    'payment_status' => 'unpaid',
                    'finance_status' => 'draft',
                    'created_by' => $this->support->userId(),
                    'row_version' => 1,
                    'created_at' => now(),
                    ...$attributes,
                ]);
                $this->recordStatus($id, null, 'open', 'create');
            } else {
                $job = $this->tenantRow('vehicle_service_job_cards', $id, true);
                DB::table('vehicle_service_job_cards')->where('id', $id)->update([
                    ...$attributes,
                    'row_version' => ((int) $job->row_version) + 1,
                ]);
                DB::table('vehicle_service_job_card_lines')->where('job_card_id', $id)->delete();
                DB::table('vehicle_service_labor_items')->where('job_card_id', $id)->delete();
                DB::table('vehicle_service_non_inventory_items')->where('job_card_id', $id)->delete();
            }

            $this->insertParts($id, $tenantId, $organizationUnitId, $payload['parts'] ?? [], (int) $payload['warehouse_id']);
            $this->insertLabor($id, $tenantId, $organizationUnitId, $payload['labor_items'] ?? []);
            $this->insertNonInventory($id, $tenantId, $organizationUnitId, $payload['non_inventory_items'] ?? []);

            return $this->findJob($id);
        });
    }

    /** @param array<string, mixed> $payload */
    private function assertJobReferences(array $payload): void
    {
        $this->support->assertTenantRow('customers', (int) $payload['linked_customer_id'], 'linked_customer_id');
        $this->support->assertTenantRow('vehicles', (int) $payload['vehicle_id'], 'vehicle_id');
        $this->support->assertTenantRow('warehouses', (int) $payload['warehouse_id'], 'warehouse_id');
        if (! empty($payload['service_type_id'])) {
            $this->support->assertTenantRow('vehicle_service_types', (int) $payload['service_type_id'], 'service_type_id');
        }
        foreach ($payload['parts'] ?? [] as $line) {
            $this->support->assertTenantRow('items', (int) $line['item_id'], 'item_id');
            $this->support->assertTenantRow('unit_of_measures', (int) $line['uom_id'], 'uom_id');
        }
        foreach ($payload['labor_items'] ?? [] as $line) {
            if (! empty($line['item_id'])) {
                $this->support->assertTenantRow('items', (int) $line['item_id'], 'item_id');
            }
            $this->support->assertTenantRow('unit_of_measures', (int) $line['uom_id'], 'uom_id');
            if (! empty($line['employee_id'])) {
                $this->support->assertTenantRow('employees', (int) $line['employee_id'], 'employee_id');
            }
        }
        foreach ($payload['non_inventory_items'] ?? [] as $line) {
            $this->support->assertTenantRow('unit_of_measures', (int) $line['uom_id'], 'uom_id');
        }
    }

    /** @param array<string, mixed> $payload @return array<string, float> */
    private function calculateTotals(array $payload): array
    {
        $parts = $this->lineTotals($payload['parts'] ?? []);
        $labor = $this->lineTotals($payload['labor_items'] ?? []);
        $nonInventory = $this->lineTotals($payload['non_inventory_items'] ?? []);
        $gross = $parts['gross'] + $labor['gross'] + $nonInventory['gross'];
        $lineDiscount = $parts['discount'] + $labor['discount'] + $nonInventory['discount'];
        $lineTax = $parts['tax'] + $labor['tax'] + $nonInventory['tax'];
        $headerDiscount = $this->discountAmount(
            $gross,
            (string) ($payload['header_discount_type'] ?? ''),
            (float) ($payload['header_discount_value'] ?? 0),
            (float) ($payload['header_discount_amount'] ?? 0),
        );
        $headerTax = round((float) ($payload['header_tax_amount'] ?? 0), 4);
        $charge = round((float) ($payload['header_charge_amount'] ?? 0), 4);
        $adjustment = round((float) ($payload['header_adjustment_amount'] ?? 0), 4);
        $effect = (string) ($payload['header_adjustment_effect'] ?? 'add');
        $debit = round($charge + ($effect === 'add' ? $adjustment : 0), 4);
        $credit = round($effect === 'deduct' ? $adjustment : 0, 4);
        $discount = round($lineDiscount + $headerDiscount, 4);
        $tax = round($lineTax + $headerTax, 4);
        $grand = round(max(0, $gross - $discount + $tax + $debit - $credit), 4);

        return [
            'parts_subtotal' => $parts['gross'],
            'subtotal' => $gross,
            'line_tax_total' => $parts['tax'],
            'line_discount_total' => $parts['discount'],
            'non_inventory_item_subtotal' => $nonInventory['gross'],
            'non_inventory_item_tax_total' => $nonInventory['tax'],
            'non_inventory_item_discount_total' => $nonInventory['discount'],
            'labor_item_subtotal' => $labor['gross'],
            'labor_item_tax_total' => $labor['tax'],
            'labor_item_discount_total' => $labor['discount'],
            'header_discount_amount' => $headerDiscount,
            'header_tax_amount' => $headerTax,
            'header_charge_total' => $charge,
            'header_debit_adjustment_total' => $effect === 'add' ? $adjustment : 0,
            'header_credit_adjustment_total' => $effect === 'deduct' ? $adjustment : 0,
            'discount_total' => $discount,
            'tax_total' => $tax,
            'debit_note_total' => $debit,
            'credit_note_total' => $credit,
            'grand_total' => $grand,
        ];
    }

    /** @param array<int, array<string, mixed>> $lines @return array{gross: float, discount: float, tax: float} */
    private function lineTotals(array $lines): array
    {
        $totals = ['gross' => 0.0, 'discount' => 0.0, 'tax' => 0.0];
        foreach ($lines as $line) {
            $gross = round((float) $line['quantity'] * (float) $line['unit_price'], 4);
            $discount = $this->discountAmount($gross, (string) ($line['discount_type'] ?? ''), (float) ($line['discount_value'] ?? 0), (float) ($line['discount_amount'] ?? 0));
            $totals['gross'] += $gross;
            $totals['discount'] += $discount;
            $totals['tax'] += round((float) ($line['tax_amount'] ?? 0), 4);
        }

        return array_map(fn (float $value): float => round($value, 4), $totals);
    }

    private function discountAmount(float $gross, string $type, float $value, float $explicit): float
    {
        return match ($type) {
            'percentage' => round(min($gross, $gross * $value / 100), 4),
            'fixed' => round(min($gross, $value), 4),
            default => round(min($gross, $explicit), 4),
        };
    }

    /** @param array<int, array<string, mixed>> $lines */
    private function insertParts(int $jobId, int $tenantId, ?int $organizationUnitId, array $lines, int $defaultWarehouseId): void
    {
        foreach ($lines as $line) {
            $values = $this->calculatedLine($line);
            DB::table('vehicle_service_job_card_lines')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'job_card_id' => $jobId,
                'item_id' => $line['item_id'],
                'line_type' => 'inventory',
                'warehouse_id' => $line['warehouse_id'] ?? $defaultWarehouseId,
                'location_id' => $line['location_id'] ?? null,
                'requires_stock_movement' => true,
                'description' => $line['description'] ?? null,
                'uom_id' => $line['uom_id'],
                'quantity' => $line['quantity'],
                'quantity_base' => $line['quantity'],
                'outstanding_qty' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'unit_cost' => $line['unit_cost'] ?? null,
                ...$values,
                'tax_group_id' => $line['tax_group_id'] ?? null,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /** @param array<int, array<string, mixed>> $lines */
    private function insertLabor(int $jobId, int $tenantId, ?int $organizationUnitId, array $lines): void
    {
        foreach ($lines as $line) {
            $values = $this->calculatedLine($line);
            $laborItemId = DB::table('vehicle_service_labor_items')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'job_card_id' => $jobId,
                'item_id' => $line['item_id'] ?? null,
                'status' => 'planned',
                'requires_assignment' => ! empty($line['employee_id']),
                'description' => $line['description'] ?? null,
                'uom_id' => $line['uom_id'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'unit_cost' => $line['unit_cost'] ?? null,
                'actual_hours' => $line['actual_hours'] ?? null,
                ...$values,
                'tax_group_id' => $line['tax_group_id'] ?? null,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if (! empty($line['employee_id'])) {
                DB::table('vehicle_service_labor_assignments')->insert([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'job_card_id' => $jobId,
                    'labor_item_id' => $laborItemId,
                    'employee_id' => (int) $line['employee_id'],
                    'hours_worked' => $line['actual_hours'] ?? null,
                    'hourly_rate' => $line['unit_price'],
                    'status' => 'assigned',
                    'assigned_by' => $this->support->userId(),
                    'row_version' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /** @param array<int, array<string, mixed>> $lines */
    private function insertNonInventory(int $jobId, int $tenantId, ?int $organizationUnitId, array $lines): void
    {
        foreach ($lines as $line) {
            $values = $this->calculatedLine($line);
            DB::table('vehicle_service_non_inventory_items')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'job_card_id' => $jobId,
                'source_type' => 'internal',
                'is_billable' => true,
                'name' => $line['name'],
                'description' => $line['description'] ?? null,
                'uom_id' => $line['uom_id'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'unit_cost' => $line['unit_cost'] ?? null,
                ...$values,
                'tax_group_id' => $line['tax_group_id'] ?? null,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /** @param array<string, mixed> $line @return array<string, mixed> */
    private function calculatedLine(array $line): array
    {
        $gross = round((float) $line['quantity'] * (float) $line['unit_price'], 4);
        $discount = $this->discountAmount($gross, (string) ($line['discount_type'] ?? ''), (float) ($line['discount_value'] ?? 0), (float) ($line['discount_amount'] ?? 0));
        $tax = round((float) ($line['tax_amount'] ?? 0), 4);

        return [
            'discount_type' => $line['discount_type'] ?? null,
            'discount_value' => $line['discount_value'] ?? 0,
            'discount_amount' => $discount,
            'gross_amount' => $gross,
            'line_total' => round($gross - $discount, 4),
            'tax_amount' => $tax,
            'line_total_with_tax' => round($gross - $discount + $tax, 4),
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function allBillableLines(int $jobId): Collection
    {
        return $this->partLines($jobId)->map(fn (object $line): array => [
            'line_type' => 'inventory_item',
            'item_id' => $line->item_id === null ? null : (int) $line->item_id,
            'uom_id' => (int) $line->uom_id,
            'description' => $line->description ?? $line->item_name,
            'quantity' => (float) $line->quantity,
            'unit_price' => (float) $line->unit_price,
            'discount_amount' => (float) $line->discount_amount,
            'tax_amount' => (float) $line->tax_amount,
        ])->concat($this->laborLines($jobId)->map(fn (object $line): array => [
            'line_type' => 'labor',
            'item_id' => $line->item_id === null ? null : (int) $line->item_id,
            'uom_id' => (int) $line->uom_id,
            'description' => $line->description ?? $line->item_name,
            'quantity' => (float) $line->quantity,
            'unit_price' => (float) $line->unit_price,
            'discount_amount' => (float) $line->discount_amount,
            'tax_amount' => (float) $line->tax_amount,
        ]))->concat($this->nonInventoryLines($jobId)->map(fn (object $line): array => [
            'line_type' => 'non_inventory_item',
            'item_id' => null,
            'uom_id' => (int) $line->uom_id,
            'description' => $line->description ?? $line->name,
            'quantity' => (float) $line->quantity,
            'unit_price' => (float) $line->unit_price,
            'discount_amount' => (float) $line->discount_amount,
            'tax_amount' => (float) $line->tax_amount,
        ]))->values();
    }

    private function updateJobStatus(object $job, string $status, array $extra = [], ?string $reason = null): void
    {
        DB::table('vehicle_service_job_cards')->where('id', (int) $job->id)->update([
            'status' => $status,
            'updated_by' => $this->support->userId(),
            'updated_at' => now(),
            'row_version' => ((int) $job->row_version) + 1,
            ...$extra,
        ]);
        $this->recordStatus((int) $job->id, (string) $job->status, $status, $status, $reason);
    }

    private function recordStatus(int $jobId, ?string $from, string $to, string $action, ?string $reason = null): void
    {
        DB::table('vehicle_service_job_status_histories')->insert([
            'tenant_id' => $this->support->tenantId(),
            'organization_unit_id' => $this->support->organizationUnitId(null),
            'entity_type' => 'job_card',
            'entity_id' => $jobId,
            'workflow_action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'changed_by' => $this->support->userId(),
            'changed_at' => now(),
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function tenantRow(string $table, int $id, bool $lock = false): object
    {
        $query = DB::table($table)->where('tenant_id', $this->support->tenantId())->where('id', $id);
        if (DB::getSchemaBuilder()->hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        if ($lock) {
            $query->lockForUpdate();
        }
        $row = $query->first();
        if ($row === null) {
            abort(404);
        }

        return $row;
    }

    private function partLines(int $jobId): Collection
    {
        return DB::table('vehicle_service_job_card_lines')
            ->leftJoin('items', 'items.id', '=', 'vehicle_service_job_card_lines.item_id')
            ->leftJoin('unit_of_measures', 'unit_of_measures.id', '=', 'vehicle_service_job_card_lines.uom_id')
            ->select(['vehicle_service_job_card_lines.*', 'items.item_code', 'items.name as item_name', 'unit_of_measures.uom_code'])
            ->where('vehicle_service_job_card_lines.tenant_id', $this->support->tenantId())
            ->where('vehicle_service_job_card_lines.job_card_id', $jobId)
            ->orderBy('vehicle_service_job_card_lines.id')
            ->get();
    }

    private function laborLines(int $jobId): Collection
    {
        $lines = DB::table('vehicle_service_labor_items')
            ->leftJoin('items', 'items.id', '=', 'vehicle_service_labor_items.item_id')
            ->leftJoin('unit_of_measures', 'unit_of_measures.id', '=', 'vehicle_service_labor_items.uom_id')
            ->select(['vehicle_service_labor_items.*', 'items.item_code', 'items.name as item_name', 'unit_of_measures.uom_code'])
            ->where('vehicle_service_labor_items.tenant_id', $this->support->tenantId())
            ->where('vehicle_service_labor_items.job_card_id', $jobId)
            ->orderBy('vehicle_service_labor_items.id')
            ->get();
        $assignments = DB::table('vehicle_service_labor_assignments')
            ->leftJoin('employees', 'employees.id', '=', 'vehicle_service_labor_assignments.employee_id')
            ->select([
                'vehicle_service_labor_assignments.labor_item_id',
                'vehicle_service_labor_assignments.employee_id',
                'vehicle_service_labor_assignments.hours_worked',
                'employees.employee_code',
                'employees.first_name',
                'employees.last_name',
                'employees.display_name',
            ])
            ->where('vehicle_service_labor_assignments.tenant_id', $this->support->tenantId())
            ->where('vehicle_service_labor_assignments.job_card_id', $jobId)
            ->orderBy('vehicle_service_labor_assignments.id')
            ->get()
            ->keyBy('labor_item_id');

        return $lines->map(function (object $line) use ($assignments): object {
            $assignment = $assignments->get($line->id);
            $line->employee_id = $assignment?->employee_id;
            $line->employee_name = $assignment === null
                ? null
                : ($assignment->display_name ?: trim($assignment->first_name.' '.($assignment->last_name ?? '')));
            $line->hours_worked = $assignment?->hours_worked;

            return $line;
        });
    }

    private function nonInventoryLines(int $jobId): Collection
    {
        return DB::table('vehicle_service_non_inventory_items')
            ->leftJoin('unit_of_measures', 'unit_of_measures.id', '=', 'vehicle_service_non_inventory_items.uom_id')
            ->select(['vehicle_service_non_inventory_items.*', 'unit_of_measures.uom_code'])
            ->where('vehicle_service_non_inventory_items.tenant_id', $this->support->tenantId())
            ->where('vehicle_service_non_inventory_items.job_card_id', $jobId)
            ->orderBy('vehicle_service_non_inventory_items.id')
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function completedJobLookup(string $search, int $limit): array
    {
        return DB::table('vehicle_service_job_cards')
            ->leftJoin('customers', 'customers.id', '=', 'vehicle_service_job_cards.linked_customer_id')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'vehicle_service_job_cards.vehicle_id')
            ->select([
                'vehicle_service_job_cards.id',
                'vehicle_service_job_cards.job_card_number as code',
                'customers.customer_name as customer_name',
                'vehicles.registration_number',
                'vehicle_service_job_cards.grand_total',
                'vehicle_service_job_cards.status',
                'vehicle_service_job_cards.invoice_status',
            ])
            ->where('vehicle_service_job_cards.tenant_id', $this->support->tenantId())
            ->whereNull('vehicle_service_job_cards.deleted_at')
            ->where('vehicle_service_job_cards.status', 'completed')
            ->where('vehicle_service_job_cards.invoice_status', 'pending')
            ->whereNotExists(function (Builder $query): void {
                $query->selectRaw('1')
                    ->from('vehicle_service_job_invoice_links')
                    ->whereColumn('vehicle_service_job_invoice_links.job_card_id', 'vehicle_service_job_cards.id')
                    ->where('vehicle_service_job_invoice_links.tenant_id', $this->support->tenantId())
                    ->where('vehicle_service_job_invoice_links.status', 'active')
                    ->whereNull('vehicle_service_job_invoice_links.deleted_at');
            })
            ->when($search !== '', fn (Builder $query): Builder => $query->where(fn (Builder $nested): Builder => $nested
                ->where('vehicle_service_job_cards.job_card_number', 'like', "%$search%")
                ->orWhere('customers.customer_name', 'like', "%$search%")
                ->orWhere('vehicles.registration_number', 'like', "%$search%")))
            ->orderByDesc('vehicle_service_job_cards.completed_datetime')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'code' => $row->code,
                'name' => trim(($row->customer_name ?? '').' '.($row->registration_number ? ' - '.$row->registration_number : '')),
                'customer_name' => $row->customer_name,
                'registration_number' => $row->registration_number,
                'grand_total' => $this->money($row->grand_total),
                'status' => $row->status,
                'invoice_status' => $row->invoice_status,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function jobBillableLineLookup(int $jobCardId, int $limit): array
    {
        if ($jobCardId <= 0 || ! $this->jobCanGenerateInvoice($jobCardId)) {
            return [];
        }

        return $this->allBillableLines($jobCardId)
            ->take($limit)
            ->map(fn (array $line): array => [
                'line_type' => $line['line_type'],
                'item_id' => $line['item_id'],
                'uom_id' => $line['uom_id'],
                'description' => $line['description'],
                'quantity' => $this->money($line['quantity']),
                'unit_price' => $this->money($line['unit_price']),
                'discount_amount' => $this->money($line['discount_amount']),
                'tax_amount' => $this->money($line['tax_amount']),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function jobInventoryLineLookup(int $jobCardId, int $limit): array
    {
        if ($jobCardId <= 0) {
            return [];
        }

        return $this->partLines($jobCardId)
            ->filter(fn (object $line): bool => (bool) $line->requires_stock_movement && round((float) $line->quantity - (float) $line->consumed_qty, 4) > 0)
            ->take($limit)
            ->map(fn (object $line): array => [
                'id' => (int) $line->id,
                'line_type' => 'inventory_item',
                'item_id' => (int) $line->item_id,
                'item_code' => $line->item_code,
                'name' => $line->item_name,
                'uom_id' => (int) $line->uom_id,
                'uom_code' => $line->uom_code,
                'warehouse_id' => $line->warehouse_id,
                'source_qty' => $this->money($line->quantity),
                'used_qty' => $this->money($line->consumed_qty),
                'remaining_qty' => $this->money(round((float) $line->quantity - (float) $line->consumed_qty, 4)),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function outstandingServiceInvoiceLookup(array $filters, string $search, int $limit): array
    {
        return DB::table('vehicle_service_job_invoice_links')
            ->join('invoices', 'invoices.id', '=', 'vehicle_service_job_invoice_links.invoice_id')
            ->leftJoin('vehicle_service_job_cards', 'vehicle_service_job_cards.id', '=', 'vehicle_service_job_invoice_links.job_card_id')
            ->select([
                'invoices.id',
                'invoices.invoice_number as code',
                'invoices.invoice_number as name',
                'vehicle_service_job_invoice_links.job_card_id',
                'vehicle_service_job_cards.job_card_number',
                'invoices.customer_id',
                'invoices.invoice_date',
                'invoices.due_date',
                'invoices.grand_total',
                'invoices.settled_total',
                'invoices.balance_total',
                'invoices.status',
            ])
            ->where('vehicle_service_job_invoice_links.tenant_id', $this->support->tenantId())
            ->where('vehicle_service_job_invoice_links.status', 'active')
            ->whereNull('vehicle_service_job_invoice_links.deleted_at')
            ->where('invoices.balance_total', '>', 0)
            ->when(isset($filters['job_card_id']), fn (Builder $query): Builder => $query->where('vehicle_service_job_invoice_links.job_card_id', (int) $filters['job_card_id']))
            ->when($search !== '', fn (Builder $query): Builder => $query->where(fn (Builder $nested): Builder => $nested
                ->where('invoices.invoice_number', 'like', "%$search%")
                ->orWhere('vehicle_service_job_cards.job_card_number', 'like', "%$search%")))
            ->orderBy('invoices.due_date')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'job_card_id' => (int) $row->job_card_id,
                'job_card_number' => $row->job_card_number,
                'party_type' => 'customer',
                'party_id' => (int) $row->customer_id,
                'invoice_date' => $row->invoice_date,
                'due_date' => $row->due_date,
                'grand_total' => $this->money($row->grand_total),
                'settled_total' => $this->money($row->settled_total),
                'balance_total' => $this->money($row->balance_total),
                'status' => $row->status,
            ])
            ->all();
    }

    private function jobCanGenerateInvoice(int $jobCardId): bool
    {
        return DB::table('vehicle_service_job_cards')
            ->where('tenant_id', $this->support->tenantId())
            ->where('id', $jobCardId)
            ->where('status', 'completed')
            ->where('invoice_status', 'pending')
            ->whereNull('deleted_at')
            ->whereNotExists(function (Builder $query): void {
                $query->selectRaw('1')
                    ->from('vehicle_service_job_invoice_links')
                    ->whereColumn('vehicle_service_job_invoice_links.job_card_id', 'vehicle_service_job_cards.id')
                    ->where('vehicle_service_job_invoice_links.tenant_id', $this->support->tenantId())
                    ->where('vehicle_service_job_invoice_links.status', 'active')
                    ->whereNull('vehicle_service_job_invoice_links.deleted_at');
            })
            ->exists();
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
