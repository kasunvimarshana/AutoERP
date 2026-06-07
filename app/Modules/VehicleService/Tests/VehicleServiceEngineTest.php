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
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceSourceLine;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\Item;
use Modules\Item\Services\ItemCreationService;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\VehicleService\DTOs\VehicleServiceEmployeeAssignmentData;
use Modules\VehicleService\DTOs\VehicleServiceInspectionData;
use Modules\VehicleService\DTOs\VehicleServiceJobData;
use Modules\VehicleService\DTOs\VehicleServiceLineData;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Http\Resources\VehicleServiceJobResource;
use Modules\VehicleService\Models\VehicleServiceInvoiceLink;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServicePaymentLink;
use Modules\VehicleService\Services\VehicleServiceEmployeeAssignmentService;
use Modules\VehicleService\Services\VehicleServiceInspectionService;
use Modules\VehicleService\Services\VehicleServiceInventoryIntegrationService;
use Modules\VehicleService\Services\VehicleServiceInvoiceIntegrationService;
use Modules\VehicleService\Services\VehicleServiceJobService;
use Modules\VehicleService\Services\VehicleServiceLineService;
use Modules\VehicleService\Services\VehicleServicePaymentIntegrationService;
use Modules\VehicleService\Services\VehicleServiceStatusService;
use Tests\TestCase;

final class VehicleServiceEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_service_job_inspection_and_mixed_lines_use_decimal_totals(): void
    {
        $context = $this->context();
        $job = $this->createJob($context, VehicleServiceCommissionType::Percentage, '10.000000');
        $inspection = app(VehicleServiceInspectionService::class)->save($job, new VehicleServiceInspectionData(
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
        $service = app(VehicleServiceLineService::class)->create($job, new VehicleServiceLineData(
            lineSourceType: VehicleServiceLineSourceType::ServiceItem,
            description: 'Brake service',
            quantity: '1.000000',
            unitPrice: '200.000000',
            itemId: (int) $context['service']->getKey(),
            discountCalculationType: 'percentage',
            discountRate: '10.000000',
            taxCalculationType: 'percentage',
            taxRate: '15.000000',
            chargeCalculationType: 'fixed',
            chargeAmount: '5.000000',
        ));

        $job->refresh();
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
    }

    public function test_supervisor_and_employee_fixed_and_percentage_commissions(): void
    {
        $context = $this->context();
        $fixedJob = $this->createJob($context, VehicleServiceCommissionType::Fixed, '75.000000');
        $line = $this->line($fixedJob, VehicleServiceLineSourceType::LabourItem, $context['labour'], '2.000000', '100.000000');
        $assignments = app(VehicleServiceEmployeeAssignmentService::class);
        $fixed = $assignments->create($fixedJob, $line, new VehicleServiceEmployeeAssignmentData(
            employeeId: $context['employee_id'],
            roleType: 'technician',
            commissionType: VehicleServiceCommissionType::Fixed,
            commissionValue: '25.000000',
        ));
        $percentage = $assignments->create($fixedJob, $line, new VehicleServiceEmployeeAssignmentData(
            employeeId: $context['employee_id'],
            roleType: 'helper',
            commissionType: VehicleServiceCommissionType::Percentage,
            commissionValue: '12.500000',
        ));

        $this->assertSame('75.000000', (string) $fixedJob->refresh()->supervisor_commission_amount);
        $this->assertSame('25.000000', (string) $fixed->commission_amount);
        $this->assertSame('25.000000', (string) $percentage->commission_amount);
    }

    public function test_employee_assignment_is_restricted_to_service_labour_and_service_combo_children(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $inventory = $this->line($job, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '1.000000', '20.000000');
        $external = $this->line($job, VehicleServiceLineSourceType::ExternalItem, null, '1.000000', '20.000000');
        $service = $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '1.000000', '100.000000');
        $assignments = app(VehicleServiceEmployeeAssignmentService::class);

        foreach ([$inventory, $external] as $line) {
            try {
                $assignments->create($job, $line, new VehicleServiceEmployeeAssignmentData($context['employee_id'], 'technician'));
                $this->fail('Expected non-service employee assignment to fail.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame('Employees can only be assigned to service or labour lines.', $exception->getMessage());
            }
        }

        $allowed = $assignments->create($job, $service, new VehicleServiceEmployeeAssignmentData($context['employee_id'], 'technician'));
        $this->assertSame($context['employee_id'], (int) $allowed->employee_id);
    }

    public function test_combo_parent_expands_children_with_inventory_and_workforce_flags(): void
    {
        $context = $this->context();
        $combo = $this->item($context['tenant_id'], 'COMBO', ItemType::Combo, false, $context['uom_id']);
        DB::table('item_bundles')->insert([
            [
                'tenant_id' => $context['tenant_id'],
                'parent_item_id' => $combo->getKey(),
                'child_item_id' => $context['stock']->getKey(),
                'quantity' => '2.000000',
                'uom_id' => $context['uom_id'],
                'line_type' => 'item',
                'is_required' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $context['tenant_id'],
                'parent_item_id' => $combo->getKey(),
                'child_item_id' => $context['service']->getKey(),
                'quantity' => '1.000000',
                'uom_id' => $context['uom_id'],
                'line_type' => 'service',
                'is_required' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        $job = $this->createJob($context);
        $parent = $this->line($job, VehicleServiceLineSourceType::ComboParent, $combo, '3.000000', '500.000000');
        $children = $parent->children()->with('item')->orderBy('line_number')->get();

        $this->assertCount(2, $children);
        $this->assertSame('6.000000', (string) $children[0]->quantity);
        $this->assertTrue((bool) $children[0]->is_inventory_tracked);
        $this->assertFalse((bool) $children[0]->is_employee_assignable);
        $this->assertFalse((bool) $children[0]->is_billable);
        $this->assertTrue((bool) $children[1]->is_employee_assignable);

        $assignment = app(VehicleServiceEmployeeAssignmentService::class)->create(
            $job,
            $children[1],
            new VehicleServiceEmployeeAssignmentData($context['employee_id'], 'technician'),
        );
        $this->assertSame($children[1]->getKey(), $assignment->vehicle_service_job_line_id);

        $this->expectException(InvalidArgumentException::class);
        app(VehicleServiceEmployeeAssignmentService::class)->create(
            $job,
            $parent,
            new VehicleServiceEmployeeAssignmentData($context['employee_id'], 'technician'),
        );
    }

    public function test_inventory_issue_only_posts_inventory_lines_and_enforces_availability(): void
    {
        $context = $this->context();
        $this->receiveStock($context, '5.000000');
        $job = $this->createJob($context);
        $inventory = $this->line($job, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '2.000000', '20.000000');
        $this->line($job, VehicleServiceLineSourceType::ExternalItem, null, '1.000000', '10.000000');
        $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '1.000000', '100.000000');

        $issued = app(VehicleServiceInventoryIntegrationService::class)->issue($job, $context['warehouse_id']);
        $this->assertCount(1, $issued);
        $this->assertSame($inventory->getKey(), $issued[0]->source_line_id);
        $this->assertSame(1, InventoryMovement::query()->where('source_type', 'vehicle_service_job')->count());
        $availability = app(StockAvailabilityService::class)->availability(new StockBalanceData(
            $context['tenant_id'],
            (int) $context['stock']->getKey(),
            $context['warehouse_id'],
        ));
        $this->assertSame('3.000000', $availability->quantityAvailable);

        $secondJob = $this->createJob($context);
        $tooMuch = $this->line($secondJob, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '4.000000', '20.000000');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Inventory issue quantity cannot exceed available stock.');
        app(VehicleServiceInventoryIntegrationService::class)->issue($secondJob, $context['warehouse_id'], lineIds: [(int) $tooMuch->getKey()]);
    }

    public function test_invoice_contains_only_billable_lines_and_prevents_duplicate_full_invoice(): void
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
        $statuses = app(VehicleServiceStatusService::class);
        $statuses->change($job, VehicleServiceJobStatus::InProgress);
        $statuses->change($job->refresh(), VehicleServiceJobStatus::Completed);

        $invoice = app(VehicleServiceInvoiceIntegrationService::class)->create($job->refresh(), '2026-06-07');
        $this->assertSame('250.000000', (string) $invoice->grand_total);
        $this->assertCount(1, $invoice->lines);
        $this->assertSame($billable->getKey(), $invoice->lines->first()->source_line_id);
        $this->assertSame(0, InvoiceSourceLine::query()->where('source_line_id', $customerItem->getKey())->count());
        $this->assertSame(1, VehicleServiceInvoiceLink::query()->where('vehicle_service_job_id', $job->getKey())->count());
        $this->assertSame(VehicleServiceJobStatus::Invoiced, $job->refresh()->status);

        $payment = app(VehicleServicePaymentIntegrationService::class)->prepare(
            $job,
            (int) $invoice->getKey(),
            '2026-06-07',
            '100.000000',
        );
        $this->assertSame(PaymentType::ServiceReceipt, $payment->paymentType);
        $this->assertCount(1, $payment->allocations);
        $this->assertSame(0, Payment::query()->count());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No billable service job lines remain to invoice.');
        app(VehicleServiceInvoiceIntegrationService::class)->create($job->refresh(), '2026-06-07');
    }

    public function test_status_workflow_rejects_invalid_transitions_and_records_history(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $statuses = app(VehicleServiceStatusService::class);
        $statuses->change($job, VehicleServiceJobStatus::Inspected);
        $statuses->change($job->refresh(), VehicleServiceJobStatus::InProgress);
        $statuses->change($job->refresh(), VehicleServiceJobStatus::Completed);

        $this->assertSame(VehicleServiceJobStatus::Completed, $job->refresh()->status);
        $this->assertSame(4, $job->statusHistories()->count());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid service job status transition');
        $statuses->change($job->refresh(), VehicleServiceJobStatus::Draft);
    }

    public function test_tenant_and_organization_isolation_reject_cross_scope_references(): void
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

    public function test_job_resource_keeps_decimals_readable_and_relations_compact(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $this->line($job, VehicleServiceLineSourceType::LabourItem, $context['labour'], '1.000000', '125.500000');
        $job = $job->refresh()->load(app(VehicleServiceJobService::class)->relations());
        $resource = (new VehicleServiceJobResource($job))->resolve();

        $this->assertSame('125.500000', $resource['grand_total']);
        $this->assertSame($context['customer_id'], $resource['customer']['id']);
        $this->assertSame($context['vehicle_id'], $resource['vehicle']['id']);
        $this->assertSame('125.500000', $resource['lines'][0]['line_total']);
    }

    public function test_vehicle_service_boolean_inputs_are_normalized_before_validation(): void
    {
        $this->withoutMiddleware();
        $context = $this->context();
        $job = $this->createJob($context);

        foreach ([
            true => [true, 'true', 1, '1'],
            false => [false, 'false', 0, '0'],
        ] as $expected => $values) {
            foreach ($values as $index => $value) {
                $this->postJson("/api/v1/vehicle-service/jobs/{$job->getKey()}/lines", [
                    'tenant_id' => $context['tenant_id'],
                    'line_source_type' => 'service_item',
                    'item_id' => $context['service']->getKey(),
                    'description' => "Boolean line {$expected}-{$index}",
                    'quantity' => '1.000000',
                    'unit_price' => '10.000000',
                    'is_billable' => $value,
                    'is_inventory_tracked' => $value,
                    'is_employee_assignable' => $value,
                    'expand_combo' => $value,
                ])->assertCreated()
                    ->assertJsonPath('data.is_billable', (bool) $expected)
                    ->assertJsonPath('data.is_inventory_tracked', false)
                    ->assertJsonPath('data.is_employee_assignable', true);
            }
        }
    }

    public function test_line_sources_enforce_inventory_and_customer_supplied_rules(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $inventory = $this->line($job, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '1.000000', '10.000000');
        $customerSupplied = $this->line($job, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '1.000000', '10.000000', customerSupplied: true);
        $external = $this->line($job, VehicleServiceLineSourceType::ExternalItem, null, '1.000000', '10.000000');
        $labour = $this->line($job, VehicleServiceLineSourceType::LabourItem, $context['labour'], '1.000000', '10.000000');
        $service = $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '1.000000', '10.000000');

        $this->assertTrue((bool) $inventory->is_inventory_tracked);
        $this->assertFalse((bool) $customerSupplied->is_inventory_tracked);
        $this->assertFalse((bool) $customerSupplied->is_billable);
        $this->assertFalse((bool) $external->is_inventory_tracked);
        $this->assertFalse((bool) $labour->is_inventory_tracked);
        $this->assertFalse((bool) $service->is_inventory_tracked);

        $eligible = app(VehicleServiceInventoryIntegrationService::class)->issueLines($job);
        $this->assertSame([(int) $inventory->getKey()], $eligible->pluck('id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_invalid_combo_child_relationships_are_rejected(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);

        try {
            $this->line($job, VehicleServiceLineSourceType::ComboChild, $context['stock'], '1.000000', '0.000000');
            $this->fail('Expected a combo child without a parent to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Combo child lines require a combo parent line.', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only combo child lines may reference a parent line.');
        app(VehicleServiceLineService::class)->create($job, new VehicleServiceLineData(
            lineSourceType: VehicleServiceLineSourceType::ServiceItem,
            description: 'Invalid parent reference',
            quantity: '1.000000',
            unitPrice: '10.000000',
            parentLineId: 999,
            itemId: (int) $context['service']->getKey(),
        ));
    }

    public function test_partial_invoice_tracks_remaining_quantity_and_reaches_invoiced_status(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $line = $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '4.000000', '50.000000');
        $statuses = app(VehicleServiceStatusService::class);
        $statuses->change($job, VehicleServiceJobStatus::InProgress);
        $statuses->change($job->refresh(), VehicleServiceJobStatus::Completed);
        $invoices = app(VehicleServiceInvoiceIntegrationService::class);

        $first = $invoices->create($job->refresh(), '2026-06-07', [(int) $line->getKey() => '1.500000']);
        $this->assertSame('75.000000', (string) $first->grand_total);
        $this->assertSame(VehicleServiceJobStatus::Completed, $job->refresh()->status);
        $readiness = $invoices->billableLines($job->refresh())->firstWhere('id', $line->getKey());
        $this->assertSame('1.500000', (string) $readiness?->invoiced_quantity);
        $this->assertSame('2.500000', (string) $readiness?->remaining_billable_quantity);
        $this->assertSame('partially_invoiced', $readiness?->invoice_state);

        $invoices->create($job->refresh(), '2026-06-07', [(int) $line->getKey() => '2.500000']);
        $this->assertSame(VehicleServiceJobStatus::Invoiced, $job->refresh()->status);
    }

    public function test_cancelled_invoice_source_quantity_can_be_invoiced_again(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $line = $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '1.000000', '80.000000');
        $statuses = app(VehicleServiceStatusService::class);
        $statuses->change($job, VehicleServiceJobStatus::InProgress);
        $statuses->change($job->refresh(), VehicleServiceJobStatus::Completed);
        $invoices = app(VehicleServiceInvoiceIntegrationService::class);
        $first = $invoices->create($job->refresh(), '2026-06-07');
        $first->forceFill(['status' => InvoiceStatus::Cancelled->value])->save();

        $replacement = $invoices->create($job->refresh(), '2026-06-07', [(int) $line->getKey() => '1.000000']);
        $this->assertNotSame($first->getKey(), $replacement->getKey());
        $this->assertSame('80.000000', (string) $replacement->grand_total);
    }

    public function test_payment_creation_allocates_invoice_and_updates_job_status(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '1.000000', '250.000000');
        $statuses = app(VehicleServiceStatusService::class);
        $statuses->change($job, VehicleServiceJobStatus::InProgress);
        $statuses->change($job->refresh(), VehicleServiceJobStatus::Completed);
        $invoice = app(VehicleServiceInvoiceIntegrationService::class)->create($job->refresh(), '2026-06-07');
        $payments = app(VehicleServicePaymentIntegrationService::class);

        try {
            $payments->prepare($job->refresh(), (int) $invoice->getKey(), '2026-06-07', '251.000000');
            $this->fail('Expected payment amount above the invoice balance to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Payment amount cannot exceed invoice remaining balance.', $exception->getMessage());
        }

        $first = $payments->create($job->refresh(), (int) $invoice->getKey(), '2026-06-07', '100.000000');
        $this->assertSame(PaymentStatus::Allocated, $first->status);
        $this->assertSame('150.000000', (string) Invoice::query()->findOrFail($invoice->getKey())->balance_due);
        $this->assertSame(VehicleServiceJobStatus::PartiallyPaid, $job->refresh()->status);
        $resource = (new VehicleServiceJobResource($job->refresh()->load(app(VehicleServiceJobService::class)->relations())))->resolve();
        $this->assertSame('150.000000', $resource['invoice_links'][0]['balance_due']);

        $second = $payments->create($job->refresh(), (int) $invoice->getKey(), '2026-06-07', '150.000000');
        $this->assertSame(PaymentStatus::Allocated, $second->status);
        $this->assertSame(VehicleServiceJobStatus::Paid, $job->refresh()->status);
        $this->assertSame(2, VehicleServicePaymentLink::query()->where('vehicle_service_job_id', $job->getKey())->count());
    }

    public function test_inventory_issue_rejects_line_ids_from_another_job(): void
    {
        $context = $this->context();
        $this->receiveStock($context, '5.000000');
        $job = $this->createJob($context);
        $otherJob = $this->createJob($context);
        $otherLine = $this->line($otherJob, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '1.000000', '10.000000');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('One or more selected inventory lines are invalid or already issued.');
        app(VehicleServiceInventoryIntegrationService::class)->issue(
            $job,
            $context['warehouse_id'],
            lineIds: [(int) $otherLine->getKey()],
        );
    }

    public function test_tracked_inventory_lines_are_blocked_before_issue(): void
    {
        $context = $this->context();
        $tracked = $this->item(
            $context['tenant_id'],
            'BATCH-'.$context['tenant_id'],
            ItemType::Stock,
            true,
            $context['uom_id'],
            TrackingType::Batch,
        );
        $job = $this->createJob($context);
        $line = $this->line($job, VehicleServiceLineSourceType::InventoryItem, $tracked, '1.000000', '10.000000');

        $readiness = app(VehicleServiceInventoryIntegrationService::class)
            ->issueLines($job, $context['warehouse_id'])
            ->firstWhere('id', $line->getKey());

        $this->assertFalse((bool) $readiness?->issue_eligible);
        $this->assertSame(
            'Batch, lot, and serial tracked items require tracking references in the Inventory workflow.',
            $readiness?->inventory_warning,
        );
    }

    /** @return array<string, mixed> */
    private function context(string $suffix = ''): array
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(5));
        $tenantId = $this->tenant($suffix);
        $uomId = $this->uom($tenantId, 'PCS-'.$suffix);
        $warehouseId = $this->warehouse($tenantId, 'WH-'.$suffix);
        $customerId = $this->customer($tenantId, 'CUS-'.$suffix);
        $vehicleId = $this->vehicle($tenantId, $customerId, 'VEH-'.$suffix);
        $employeeId = $this->employee($tenantId, 'EMP-'.$suffix);

        return [
            'tenant_id' => $tenantId,
            'uom_id' => $uomId,
            'warehouse_id' => $warehouseId,
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
        return app(VehicleServiceJobService::class)->create(new VehicleServiceJobData(
            tenantId: $context['tenant_id'],
            jobDate: '2026-06-07',
            customerId: $context['customer_id'],
            vehicleId: $context['vehicle_id'],
            supervisorEmployeeId: $context['employee_id'],
            supervisorCommissionType: $commissionType,
            supervisorCommissionValue: $commissionValue,
            odometerReading: '12000.000000',
            fuelLevel: 'half',
        ));
    }

    private function line(
        VehicleServiceJob $job,
        VehicleServiceLineSourceType $source,
        ?Item $item,
        string $quantity,
        string $unitPrice,
        bool $customerSupplied = false,
        string $description = 'Service line',
    ) {
        return app(VehicleServiceLineService::class)->create($job, new VehicleServiceLineData(
            lineSourceType: $source,
            description: $description,
            quantity: $quantity,
            unitPrice: $unitPrice,
            itemId: $item === null ? null : (int) $item->getKey(),
            uomId: $item?->base_uom_id,
            unitCost: $source === VehicleServiceLineSourceType::InventoryItem ? '10.000000' : '0.000000',
            isCustomerSupplied: $customerSupplied,
        ));
    }

    private function receiveStock(array $context, string $quantity): void
    {
        app(StockMovementService::class)->record(new StockMovementData(
            tenantId: $context['tenant_id'],
            movementDate: '2026-06-07',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $context['stock']->getKey(),
            warehouseId: $context['warehouse_id'],
            quantity: $quantity,
            unitCost: '10.000000',
        ));
    }

    private function item(
        int $tenantId,
        string $code,
        ItemType $type,
        bool $stockable,
        int $uomId,
        TrackingType $trackingType = TrackingType::None,
    ): Item {
        return app(ItemCreationService::class)->create(new CreateItemData(
            tenantId: $tenantId,
            code: $code,
            name: str_replace('-', ' ', $code),
            itemType: $type,
            trackingType: $trackingType,
            costingMethod: $stockable ? CostingMethod::Fifo : CostingMethod::None,
            baseUomId: $uomId,
            isStockable: $stockable,
        ));
    }

    private function tenant(string $suffix): int
    {
        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-VS-'.$suffix,
            'name' => 'Vehicle Service '.$suffix,
            'slug' => 'vehicle-service-'.Str::lower($suffix),
            'status' => 'active',
            'is_active' => true,
            'is_isolated' => true,
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
        return (int) DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'vehicle_number' => $code,
            'customer_id' => $customerId,
            'registration_number' => 'REG-'.$code,
            'odometer_reading' => '12000.000000',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
