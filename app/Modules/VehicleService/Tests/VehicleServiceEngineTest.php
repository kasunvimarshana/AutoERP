<?php

declare(strict_types=1);

namespace Modules\VehicleService\Tests;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Services\StockAvailabilityService;
use Modules\Inventory\Services\StockMovementService;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\InvoiceSourceLine;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\Item;
use Modules\Item\Services\ItemCreationService;
use Modules\VehicleService\DTOs\VehicleServiceEmployeeAssignmentData;
use Modules\VehicleService\DTOs\VehicleServiceInspectionData;
use Modules\VehicleService\DTOs\VehicleServiceJobData;
use Modules\VehicleService\DTOs\VehicleServiceLineData;
use Modules\VehicleService\Enums\VehicleServiceBillingStatus;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Enums\VehicleServiceLifecycleDimension;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Enums\VehicleServiceOperationalStatus;
use Modules\VehicleService\Enums\VehicleServicePaymentStatus;
use Modules\VehicleService\Http\Resources\VehicleServiceJobResource;
use Modules\VehicleService\Models\VehicleServiceInvoiceLink;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;
use Modules\VehicleService\Services\VehicleServiceEmployeeAssignmentService;
use Modules\VehicleService\Services\VehicleServiceInspectionService;
use Modules\VehicleService\Services\VehicleServiceInventoryIntegrationService;
use Modules\VehicleService\Services\VehicleServiceInvoiceIntegrationService;
use Modules\VehicleService\Services\VehicleServiceJobService;
use Modules\VehicleService\Services\VehicleServiceLineService;
use Modules\VehicleService\Services\VehicleServiceStatusService;
use Tests\TestCase;

final class VehicleServiceEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->trustTenantScopedRequestContextFromPayload();
    }

    public function test_create_service_job_inspection_and_mixed_lines_use_decimal_totals(): void
    {
        $context = $this->context();
        $job = $this->createJob($context, VehicleServiceCommissionType::Percentage, '10.000000');
        $inspection = $this->saveInspection($job, new VehicleServiceInspectionData(
            customerComplaint: 'Brake noise',
            inspectionNotes: 'Front pads worn',
            diagnosis: 'Replace front pads',
            odometerReading: '12500.500000',
            fuelLevel: 'half',
        ));

        $inventory = $this->line($job, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '2.000000', '100.000000');
        $external = $this->line($job, VehicleServiceLineSourceType::ExternalItem, null, '1.000000', '50.000000', description: 'Outside gasket');
        $customerSupplied = $this->line(
            $job,
            VehicleServiceLineSourceType::ExternalItem,
            null,
            '1.000000',
            '500.000000',
            customerSupplied: true,
            description: 'Customer supplied oil',
        );
        $service = $this->line(
            $job,
            VehicleServiceLineSourceType::ServiceItem,
            $context['service'],
            '1.000000',
            '200.000000',
            description: 'Brake service',
            discountCalculationType: 'percentage',
            discountRate: '10.000000',
            taxCalculationType: 'percentage',
            taxRate: '15.000000',
            chargeCalculationType: 'fixed',
            chargeAmount: '5.000000',
        );

        $job = $this->refreshJob($job);
        $this->assertSame('Brake noise', $inspection->customer_complaint);
        $this->assertSame('200.000000', (string) $inventory->line_total);
        $this->assertSame('50.000000', (string) $external->line_total);
        $this->assertFalse((bool) $customerSupplied->is_billable);
        $this->assertSame('215.000000', (string) $service->line_total);
        $this->assertSame('450.000000', (string) $job->subtotal);
        $this->assertSame('20.000000', (string) $job->discount_total);
        $this->assertSame('30.000000', (string) $job->tax_total);
        $this->assertSame('5.000000', (string) $job->charge_total);
        $this->assertSame('465.000000', (string) $job->grand_total);
        $this->assertSame('46.500000', (string) $job->supervisor_commission_amount);
        $this->assertSame(VehicleServiceOperationalStatus::Draft, $job->operational_status);
        $this->assertSame(VehicleServiceBillingStatus::Unbilled, $job->billing_status);
        $this->assertSame(VehicleServicePaymentStatus::Unpaid, $job->payment_status);
    }

    public function test_supervisor_and_employee_fixed_and_percentage_commissions(): void
    {
        $context = $this->context();
        $fixedJob = $this->createJob($context, VehicleServiceCommissionType::Fixed, '75.000000');
        $line = $this->line($fixedJob, VehicleServiceLineSourceType::LabourItem, $context['labour'], '2.000000', '100.000000');
        $fixed = $this->assignEmployee($fixedJob, $line, new VehicleServiceEmployeeAssignmentData(
            employeeId: $context['employee_id'],
            roleType: 'technician',
            commissionType: VehicleServiceCommissionType::Fixed,
            commissionValue: '25.000000',
        ));
        $percentage = $this->assignEmployee($fixedJob, $line, new VehicleServiceEmployeeAssignmentData(
            employeeId: $context['employee_id'],
            roleType: 'helper',
            commissionType: VehicleServiceCommissionType::Percentage,
            commissionValue: '12.500000',
        ));

        $this->assertSame('75.000000', (string) $this->refreshJob($fixedJob)->supervisor_commission_amount);
        $this->assertSame('25.000000', (string) $fixed->commission_amount);
        $this->assertSame('25.000000', (string) $percentage->commission_amount);
    }

    public function test_line_sources_enforce_inventory_customer_supplied_and_employee_assignment_rules(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $inventory = $this->line($job, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '1.000000', '20.000000');
        $customerSupplied = $this->line($job, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '1.000000', '20.000000', customerSupplied: true);
        $external = $this->line($job, VehicleServiceLineSourceType::ExternalItem, null, '1.000000', '20.000000');
        $service = $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '1.000000', '100.000000');

        $this->assertTrue((bool) $inventory->is_inventory_tracked);
        $this->assertFalse((bool) $customerSupplied->is_inventory_tracked);
        $this->assertFalse((bool) $customerSupplied->is_billable);
        $this->assertFalse((bool) $external->is_inventory_tracked);

        foreach ([$inventory, $external] as $line) {
            try {
                $this->assignEmployee($job, $line, new VehicleServiceEmployeeAssignmentData($context['employee_id'], 'technician'));
                $this->fail('Expected non-service employee assignment to fail.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame('Employees can only be assigned to service or labour lines.', $exception->getMessage());
            }
        }

        $assignment = $this->assignEmployee($job, $service, new VehicleServiceEmployeeAssignmentData($context['employee_id'], 'technician'));
        $this->assertSame($service->getKey(), $assignment->vehicle_service_job_line_id);
    }

    public function test_inventory_issue_only_posts_inventory_lines_and_enforces_availability(): void
    {
        $context = $this->context();
        $this->receiveStock($context, '5.000000');
        $job = $this->createJob($context);
        $inventory = $this->line($job, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '2.000000', '20.000000');
        $this->line($job, VehicleServiceLineSourceType::ExternalItem, null, '1.000000', '10.000000');
        $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '1.000000', '100.000000');

        $issued = $this->issueInventory($job, $context['warehouse_id'], $context['warehouse_location_id']);
        $this->assertCount(1, $issued);
        $this->assertSame($inventory->getKey(), $issued[0]->source_line_id);
        $movementCount = $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn (): int => InventoryMovement::query()->where('source_type', 'vehicle_service_job')->count(),
        );
        $this->assertSame(1, $movementCount);
        $availability = $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn () => app(StockAvailabilityService::class)->availability(new StockBalanceData(
                $context['tenant_id'],
                (int) $context['stock']->getKey(),
                $context['warehouse_id'],
            )),
        );
        $this->assertSame('3.000000', $availability->quantityAvailable);

        $secondJob = $this->createJob($context);
        $tooMuch = $this->line($secondJob, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '4.000000', '20.000000');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Inventory issue quantity cannot exceed available stock.');
        $this->issueInventory($secondJob, $context['warehouse_id'], $context['warehouse_location_id'], lineIds: [(int) $tooMuch->getKey()]);
    }

    public function test_invoice_contains_only_billable_lines_and_updates_billing_lifecycle(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $billable = $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '1.000000', '250.000000');
        $customerItem = $this->line(
            $job,
            VehicleServiceLineSourceType::ExternalItem,
            null,
            '1.000000',
            '999.000000',
            customerSupplied: true,
        );
        $this->changeOperational($job, VehicleServiceOperationalStatus::InProgress);
        $this->changeOperational($this->refreshJob($job), VehicleServiceOperationalStatus::Completed);

        $invoice = $this->createServiceInvoice($this->refreshJob($job), '2026-06-07');
        $this->assertSame('250.000000', (string) $invoice->grand_total);
        $this->assertCount(1, $invoice->lines);
        $this->assertSame($billable->getKey(), $invoice->lines->first()->source_line_id);
        $sourceLineCount = $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn (): int => InvoiceSourceLine::query()->where('source_line_id', $customerItem->getKey())->count(),
        );
        $linkCount = $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn (): int => VehicleServiceInvoiceLink::query()->where('vehicle_service_job_id', $job->getKey())->count(),
        );
        $this->assertSame(0, $sourceLineCount);
        $this->assertSame(1, $linkCount);
        $this->assertSame(InvoiceStatus::Posted, $invoice->status);
        $this->assertNotNull($invoice->posted_at);

        $job = $this->refreshJob($job);
        $this->assertSame(VehicleServiceOperationalStatus::Completed, $job->operational_status);
        $this->assertSame(VehicleServiceBillingStatus::Billed, $job->billing_status);
        $this->assertSame(VehicleServicePaymentStatus::Unpaid, $job->payment_status);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No billable service job lines remain to invoice.');
        $this->createServiceInvoice($job, '2026-06-07');
    }

    public function test_partial_invoice_tracks_remaining_quantity_and_reaches_billed_status(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $line = $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '4.000000', '50.000000');
        $this->changeOperational($job, VehicleServiceOperationalStatus::InProgress);
        $this->changeOperational($this->refreshJob($job), VehicleServiceOperationalStatus::Completed);

        $first = $this->createServiceInvoice($this->refreshJob($job), '2026-06-07', [(int) $line->getKey() => '1.500000']);
        $this->assertSame(InvoiceStatus::Posted, $first->status);
        $this->assertSame('75.000000', (string) $first->grand_total);
        $job = $this->refreshJob($job);
        $this->assertSame(VehicleServiceBillingStatus::PartiallyBilled, $job->billing_status);
        $readiness = $this->billableLines($job)->firstWhere('id', $line->getKey());
        $this->assertSame('1.500000', (string) $readiness?->invoiced_quantity);
        $this->assertSame('2.500000', (string) $readiness?->remaining_billable_quantity);
        $this->assertSame('partially_invoiced', $readiness?->invoice_state);

        $second = $this->createServiceInvoice($job, '2026-06-07', [(int) $line->getKey() => '2.500000']);
        $this->assertSame(InvoiceStatus::Posted, $second->status);
        $this->assertSame(VehicleServiceBillingStatus::Billed, $this->refreshJob($job)->billing_status);
    }

    public function test_operational_workflow_rejects_invalid_transitions_and_records_history_dimensions(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $this->changeOperational($job, VehicleServiceOperationalStatus::Inspected);
        $this->changeOperational($this->refreshJob($job), VehicleServiceOperationalStatus::InProgress);
        $this->changeOperational($this->refreshJob($job), VehicleServiceOperationalStatus::Completed);

        $job = $this->refreshJob($job);
        $this->assertSame(VehicleServiceOperationalStatus::Completed, $job->operational_status);
        $this->assertSame(VehicleServiceBillingStatus::Unbilled, $job->billing_status);
        $this->assertSame(VehicleServicePaymentStatus::Unpaid, $job->payment_status);

        $history = $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn () => $job->statusHistories()->oldest('changed_at')->get(),
        );
        $this->assertSame(6, $history->count());
        $this->assertSame([
            VehicleServiceLifecycleDimension::Operational,
            VehicleServiceLifecycleDimension::Billing,
            VehicleServiceLifecycleDimension::Payment,
            VehicleServiceLifecycleDimension::Operational,
            VehicleServiceLifecycleDimension::Operational,
            VehicleServiceLifecycleDimension::Operational,
        ], $history->pluck('dimension')->all());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid service job operational status transition');
        $this->changeOperational($job, VehicleServiceOperationalStatus::Draft);
    }

    public function test_bill_to_customer_drives_service_invoice_party(): void
    {
        $context = $this->context();
        $billToCustomerId = $this->customer($context['tenant_id'], 'BILL-'.$context['tenant_id']);
        $job = $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn (): VehicleServiceJob => app(VehicleServiceJobService::class)->create(new VehicleServiceJobData(
                tenantId: $context['tenant_id'],
                jobDate: '2026-06-07',
                customerId: $context['customer_id'],
                vehicleId: $context['vehicle_id'],
                billToCustomerId: $billToCustomerId,
                supervisorEmployeeId: $context['employee_id'],
            )),
        );
        $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '1.000000', '150.000000');
        $this->changeOperational($job, VehicleServiceOperationalStatus::InProgress);
        $this->changeOperational($this->refreshJob($job), VehicleServiceOperationalStatus::Completed);

        $invoice = $this->createServiceInvoice($this->refreshJob($job), '2026-06-07');
        $this->assertSame('customer', $invoice->party_type);
        $this->assertSame($billToCustomerId, (int) $invoice->party_id);
    }

    public function test_job_resource_keeps_decimals_readable_relations_compact_and_lifecycle_split(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $this->line($job, VehicleServiceLineSourceType::LabourItem, $context['labour'], '1.000000', '125.500000');
        $job = $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn (): VehicleServiceJob => $job->refresh()->load(array_merge(
                app(VehicleServiceJobService::class)->relations(),
                ['lines.item', 'lines.variant', 'lines.uom'],
            )),
        );
        $resource = (new VehicleServiceJobResource($job))->resolve();

        $this->assertSame('125.500000', $resource['grand_total']);
        $this->assertSame($context['customer_id'], $resource['customer']['id']);
        $this->assertSame($context['customer_id'], $resource['bill_to_customer']['id']);
        $this->assertSame($context['vehicle_id'], $resource['vehicle']['id']);
        $this->assertSame('125.500000', $resource['lines'][0]['line_total']);
        $this->assertSame('draft', $resource['operational_status']);
        $this->assertSame('unbilled', $resource['billing_status']);
        $this->assertSame('unpaid', $resource['payment_status']);
        $this->assertSame($this->currentJobVersion($job), $resource['row_version']);
    }

    public function test_tenant_isolation_rejects_cross_scope_references(): void
    {
        $context = $this->context();
        $other = $this->context('OTHER');

        $this->expectException(ModelNotFoundException::class);
        app(VehicleServiceJobService::class)->create(new VehicleServiceJobData(
            tenantId: $context['tenant_id'],
            jobDate: '2026-06-07',
            customerId: $other['customer_id'],
            vehicleId: $other['vehicle_id'],
        ));
    }

    /** @return array<string, mixed> */
    private function context(string $suffix = ''): array
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(5));
        $tenantId = $this->tenant($suffix);
        $uomId = $this->uom($tenantId, 'PCS-'.$suffix);
        $warehouseId = $this->warehouse($tenantId, 'WH-'.$suffix);
        $warehouseLocationId = $this->warehouseLocation($tenantId, $warehouseId, 'BIN-'.$suffix);
        $customerId = $this->customer($tenantId, 'CUS-'.$suffix);
        $vehicleId = $this->vehicle($tenantId, $customerId, 'VEH-'.$suffix);
        $employeeId = $this->employee($tenantId, 'EMP-'.$suffix);

        return [
            'tenant_id' => $tenantId,
            'uom_id' => $uomId,
            'warehouse_id' => $warehouseId,
            'warehouse_location_id' => $warehouseLocationId,
            'customer_id' => $customerId,
            'vehicle_id' => $vehicleId,
            'employee_id' => $employeeId,
            'stock' => $this->item($tenantId, 'STOCK-'.$suffix, ItemType::Stock, true, $uomId),
            'service' => $this->item($tenantId, 'SERVICE-'.$suffix, ItemType::Service, false, $uomId),
            'labour' => $this->item($tenantId, 'LABOUR-'.$suffix, ItemType::Labour, false, $uomId),
        ];
    }

    private function createJob(
        array $context,
        VehicleServiceCommissionType $commissionType = VehicleServiceCommissionType::None,
        string $commissionValue = '0.000000',
    ): VehicleServiceJob {
        return $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn (): VehicleServiceJob => app(VehicleServiceJobService::class)->create(new VehicleServiceJobData(
                tenantId: $context['tenant_id'],
                jobDate: '2026-06-07',
                customerId: $context['customer_id'],
                vehicleId: $context['vehicle_id'],
                supervisorEmployeeId: $context['employee_id'],
                supervisorCommissionType: $commissionType,
                supervisorCommissionValue: $commissionValue,
                odometerReading: '12000.000000',
                fuelLevel: 'half',
            )),
        );
    }

    private function refreshJob(VehicleServiceJob $job): VehicleServiceJob
    {
        return $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn (): VehicleServiceJob => $job->refresh(),
        );
    }

    private function currentJobVersion(VehicleServiceJob $job): int
    {
        return $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn (): int => (int) VehicleServiceJob::query()
                ->whereKey($job->getKey())
                ->value('row_version'),
        );
    }

    private function saveInspection(VehicleServiceJob $job, VehicleServiceInspectionData $data)
    {
        return $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn () => app(VehicleServiceInspectionService::class)->save($job, $data, $this->currentJobVersion($job)),
        );
    }

    private function assignEmployee(
        VehicleServiceJob $job,
        VehicleServiceJobLine $line,
        VehicleServiceEmployeeAssignmentData $data,
    ) {
        return $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn () => app(VehicleServiceEmployeeAssignmentService::class)->create($job, $line, $data, $this->currentJobVersion($job)),
        );
    }

    private function changeOperational(VehicleServiceJob $job, VehicleServiceOperationalStatus $status): VehicleServiceJob
    {
        return $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn (): VehicleServiceJob => app(VehicleServiceStatusService::class)->changeOperational(
                $job,
                $status,
                expectedVersion: $this->currentJobVersion($job),
            ),
        );
    }

    private function createServiceInvoice(VehicleServiceJob $job, string $invoiceDate, array $lineQuantities = [])
    {
        return $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn () => app(VehicleServiceInvoiceIntegrationService::class)->create(
                $job,
                $invoiceDate,
                $lineQuantities,
                expectedVersion: $this->currentJobVersion($job),
            ),
        );
    }

    private function billableLines(VehicleServiceJob $job)
    {
        return $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn () => app(VehicleServiceInvoiceIntegrationService::class)->billableLines($job),
        );
    }

    private function issueInventory(VehicleServiceJob $job, int $warehouseId, int $warehouseLocationId, array $lineIds = [])
    {
        return $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn () => app(VehicleServiceInventoryIntegrationService::class)->issue(
                $job,
                $warehouseId,
                $warehouseLocationId,
                lineIds: $lineIds,
                expectedVersion: $this->currentJobVersion($job),
            ),
        );
    }

    private function line(
        VehicleServiceJob $job,
        VehicleServiceLineSourceType $source,
        ?Item $item,
        string $quantity,
        string $unitPrice,
        bool $customerSupplied = false,
        string $description = 'Service line',
        ?string $discountCalculationType = null,
        string $discountRate = '0.000000',
        string $discountAmount = '0.000000',
        ?string $taxCalculationType = null,
        string $taxRate = '0.000000',
        string $taxAmount = '0.000000',
        ?string $chargeCalculationType = null,
        string $chargeRate = '0.000000',
        string $chargeAmount = '0.000000',
    ) {
        return $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn () => app(VehicleServiceLineService::class)->create($job, new VehicleServiceLineData(
                lineSourceType: $source,
                description: $description,
                quantity: $quantity,
                unitPrice: $unitPrice,
                itemId: $item === null ? null : (int) $item->getKey(),
                uomId: $item?->base_uom_id,
                unitCost: $source === VehicleServiceLineSourceType::InventoryItem ? '10.000000' : '0.000000',
                discountCalculationType: $discountCalculationType,
                discountRate: $discountRate,
                discountAmount: $discountAmount,
                taxCalculationType: $taxCalculationType,
                taxRate: $taxRate,
                taxAmount: $taxAmount,
                chargeCalculationType: $chargeCalculationType,
                chargeRate: $chargeRate,
                chargeAmount: $chargeAmount,
                isCustomerSupplied: $customerSupplied,
            ), $this->currentJobVersion($job)),
        );
    }

    private function receiveStock(array $context, string $quantity): void
    {
        $this->withTenantExecutionContext((int) $context['tenant_id'], function () use ($context, $quantity): void {
            app(StockMovementService::class)->record(new StockMovementData(
                tenantId: $context['tenant_id'],
                movementDate: '2026-06-07',
                movementType: InventoryMovementType::Receipt,
                direction: InventoryDirection::In,
                itemId: (int) $context['stock']->getKey(),
                warehouseId: $context['warehouse_id'],
                warehouseLocationId: $context['warehouse_location_id'],
                quantity: $quantity,
                unitCost: '10.000000',
            ));
        });
    }

    private function item(
        int $tenantId,
        string $code,
        ItemType $type,
        bool $stockable,
        int $uomId,
        TrackingType $trackingType = TrackingType::None,
    ): Item {
        return $this->withTenantExecutionContext(
            $tenantId,
            fn (): Item => app(ItemCreationService::class)->create(new CreateItemData(
                tenantId: $tenantId,
                code: $code,
                name: str_replace('-', ' ', $code),
                itemType: $type,
                trackingType: $trackingType,
                costingMethod: $stockable ? CostingMethod::Fifo : CostingMethod::None,
                baseUomId: $uomId,
                isStockable: $stockable,
            )),
        );
    }

    private function tenant(string $suffix): int
    {
        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-VS-'.$suffix,
            'name' => 'Vehicle Service '.$suffix,
            'slug' => 'vehicle-service-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function uom(int $tenantId, string $code): int
    {
        return (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'code' => $code,
            'name' => 'Unit '.$code,
            'symbol' => 'pcs',
            'type' => 'unit',
            'category' => 'quantity',
            'decimal_precision' => 6,
            'allow_fractional_quantity' => true,
            'is_base' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function warehouse(int $tenantId, string $code): int
    {
        return (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'name' => 'Warehouse '.$code,
            'code' => $code,
            'type' => 'standard',
            'is_active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function warehouseLocation(int $tenantId, int $warehouseId, string $code): int
    {
        return (int) DB::table('warehouse_locations')->insertGetId([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'warehouse_id' => $warehouseId,
            'name' => 'Location '.$code,
            'code' => $code,
            'type' => 'bin',
            'is_active' => true,
            'is_pickable' => true,
            'is_receivable' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function customer(int $tenantId, string $code): int
    {
        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'customer_number' => $code,
            'code' => $code,
            'name' => 'Customer '.$code,
            'display_name' => 'Customer '.$code,
            'customer_type' => 'individual',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function vehicle(int $tenantId, int $customerId, string $code): int
    {
        $vehicleId = (int) DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'vehicle_number' => $code,
            'registration_number' => 'REG-'.$code,
            'odometer_reading' => '12000.000000',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vehicle_ownerships')->insert([
            'row_version' => 1,
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'vehicle_id' => $vehicleId,
            'owner_type' => 'customer',
            'owner_id' => $customerId,
            'owner_key' => 'customer:'.$customerId,
            'owner_code_snapshot' => $code,
            'owner_name_snapshot' => 'Customer '.$code,
            'ownership_type' => 'customer_owned',
            'started_at' => now(),
            'is_current' => true,
            'current_guard' => 1,
            'active_guard' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $vehicleId;
    }

    private function employee(int $tenantId, string $code): int
    {
        return (int) DB::table('hr_employees')->insertGetId([
            'tenant_id' => $tenantId,
            'employee_number' => $code,
            'code' => $code,
            'first_name' => 'Tech',
            'display_name' => 'Technician '.$code,
            'status' => 'active',
            'availability_status' => 'available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
