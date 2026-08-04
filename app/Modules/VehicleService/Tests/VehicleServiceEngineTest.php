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
use Modules\Item\DTOs\ItemPriceData;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemPriceType;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\Item;
use Modules\Item\Services\ItemCreationService;
use Modules\Item\Services\ItemPriceService;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentMethodDirection;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Services\PaymentMethodService;
use Modules\User\Models\UserModel;
use Modules\VehicleService\DTOs\VehicleServiceEmployeeAssignmentData;
use Modules\VehicleService\DTOs\VehicleServiceInspectionData;
use Modules\VehicleService\DTOs\VehicleServiceJobData;
use Modules\VehicleService\DTOs\VehicleServiceLineData;
use Modules\VehicleService\DTOs\VehicleServicePaymentData;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Http\Resources\VehicleServiceJobResource;
use Modules\VehicleService\Models\VehicleServiceInvoiceLink;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;
use Modules\VehicleService\Models\VehicleServicePaymentLink;
use Modules\VehicleService\Services\VehicleServiceEmployeeAssignmentService;
use Modules\VehicleService\Services\VehicleServiceInspectionService;
use Modules\VehicleService\Services\VehicleServiceInventoryIntegrationService;
use Modules\VehicleService\Services\VehicleServiceInvoiceIntegrationService;
use Modules\VehicleService\Services\VehicleServiceJobService;
use Modules\VehicleService\Services\VehicleServiceLineService;
use Modules\VehicleService\Services\VehicleServicePaymentIntegrationService;
use Modules\VehicleService\Services\VehicleServiceStatusService;
use Tests\Support\CurrencyFixture;
use Tests\Support\FinancePostingFixture;
use Tests\Support\TenantUserFixture;
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
        $service = $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn () => app(VehicleServiceLineService::class)->create($job, new VehicleServiceLineData(
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
            )),
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

    public function test_employee_assignment_is_restricted_to_service_labour_and_service_combo_children(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $inventory = $this->line($job, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '1.000000', '20.000000');
        $external = $this->line($job, VehicleServiceLineSourceType::ExternalItem, null, '1.000000', '20.000000');
        $service = $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '1.000000', '100.000000');

        foreach ([$inventory, $external] as $line) {
            try {
                $this->assignEmployee($job, $line, new VehicleServiceEmployeeAssignmentData($context['employee_id'], 'technician'));
                $this->fail('Expected non-service employee assignment to fail.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame('Employees can only be assigned to service or labour lines.', $exception->getMessage());
            }
        }

        $allowed = $this->assignEmployee($job, $service, new VehicleServiceEmployeeAssignmentData($context['employee_id'], 'technician'));
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
                'unit_cost' => '0.000000',
                'default_workforce_role' => null,
                'is_required' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $context['tenant_id'],
                'parent_item_id' => $combo->getKey(),
                'child_item_id' => $context['labour']->getKey(),
                'quantity' => '1.000000',
                'uom_id' => $context['uom_id'],
                'line_type' => 'labour',
                'unit_cost' => '125.000000',
                'default_workforce_role' => 'body_wash',
                'is_required' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        $job = $this->createJob($context);
        $parent = $this->line($job, VehicleServiceLineSourceType::ComboParent, $combo, '3.000000', '500.000000');
        $children = $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn () => $parent->children()->with('item')->orderBy('line_number')->get(),
        );

        $this->assertCount(2, $children);
        $this->assertSame('6.000000', (string) $children[0]->quantity);
        $this->assertTrue((bool) $children[0]->is_inventory_tracked);
        $this->assertFalse((bool) $children[0]->is_employee_assignable);
        $this->assertFalse((bool) $children[0]->is_billable);
        $this->assertTrue((bool) $children[1]->is_employee_assignable);
        $this->assertFalse((bool) $children[1]->is_billable);
        $this->assertSame('125.000000', (string) $children[1]->unit_cost);
        $this->assertSame('body_wash', $children[1]->default_workforce_role->value);
        $this->assertSame('0.000000', (string) $children[1]->unit_price);
        $this->assertSame('0.000000', (string) $children[1]->line_total);
        $this->assertSame('1500.000000', (string) $this->refreshJob($job)->grand_total);

        $assignment = $this->assignEmployee(
            $job,
            $children[1],
            new VehicleServiceEmployeeAssignmentData($context['employee_id'], 'technician'),
        );
        $this->assertSame($children[1]->getKey(), $assignment->vehicle_service_job_line_id);
        $this->assertSame('375.000000', (string) $assignment->commission_amount);
        $this->assertSame('375.000000', (string) $this->refreshJob($job)->commission_cost_total);

        $this->expectException(InvalidArgumentException::class);
        $this->assignEmployee(
            $job,
            $parent,
            new VehicleServiceEmployeeAssignmentData($context['employee_id'], 'technician'),
        );
    }

    public function test_combo_labour_cost_is_snapshotted_before_later_bundle_changes(): void
    {
        $context = $this->context();
        $combo = $this->item($context['tenant_id'], 'COMBO-NO-PRICE', ItemType::Combo, false, $context['uom_id']);
        $bundleId = (int) DB::table('item_bundles')->insertGetId([
            'tenant_id' => $context['tenant_id'],
            'parent_item_id' => $combo->getKey(),
            'child_item_id' => $context['labour']->getKey(),
            'quantity' => '1.000000',
            'uom_id' => $context['uom_id'],
            'line_type' => 'labour',
            'unit_cost' => '80.000000',
            'default_workforce_role' => 'under_wash',
            'is_required' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $job = $this->createJob($context);
        $parent = $this->line($job, VehicleServiceLineSourceType::ComboParent, $combo, '1.000000', '500.000000');
        $child = $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn (): VehicleServiceJobLine => $parent->children()->firstOrFail(),
        );
        DB::table('item_bundles')->where('id', $bundleId)->update([
            'unit_cost' => '120.000000',
            'default_workforce_role' => 'technician',
        ]);

        $assignment = $this->assignEmployee(
            $job,
            $child,
            new VehicleServiceEmployeeAssignmentData($context['employee_id'], 'helper'),
        );

        $this->assertSame('80.000000', (string) $child->unit_cost);
        $this->assertSame('under_wash', $child->default_workforce_role->value);
        $this->assertSame('helper', $assignment->role_type);
        $this->assertSame('80.000000', (string) $assignment->commission_amount);
    }

    public function test_combo_supervisor_labour_replaces_global_supervisor_commission_without_locking_role(): void
    {
        $context = $this->context();
        $combo = $this->item($context['tenant_id'], 'COMBO-SUPERVISOR', ItemType::Combo, false, $context['uom_id']);
        DB::table('item_bundles')->insert([
            'tenant_id' => $context['tenant_id'],
            'parent_item_id' => $combo->getKey(),
            'child_item_id' => $context['labour']->getKey(),
            'quantity' => '1.000000',
            'uom_id' => $context['uom_id'],
            'line_type' => 'labour',
            'unit_cost' => '60.000000',
            'default_workforce_role' => 'supervisor',
            'is_required' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $job = $this->createJob($context, VehicleServiceCommissionType::Fixed, '25.000000');
        $parent = $this->line($job, VehicleServiceLineSourceType::ComboParent, $combo, '1.000000', '500.000000');
        $child = $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn (): VehicleServiceJobLine => $parent->children()->firstOrFail(),
        );

        $assignment = $this->assignEmployee(
            $job,
            $child,
            new VehicleServiceEmployeeAssignmentData($context['employee_id'], 'technician'),
        );
        $job = $this->refreshJob($job);

        $this->assertSame('technician', $assignment->role_type);
        $this->assertSame('60.000000', (string) $job->supervisor_commission_amount);
        $this->assertSame('60.000000', (string) $job->commission_cost_total);
        $this->assertSame('440.000000', (string) $job->net_after_commission);
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
        $this->changeStatus($job, VehicleServiceJobStatus::InProgress);
        $this->changeStatus($this->refreshJob($job), VehicleServiceJobStatus::Completed);

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
        $this->assertSame(VehicleServiceJobStatus::Invoiced, $this->refreshJob($job)->status);

        $method = $this->paymentMethod($context);
        $paymentJob = $this->refreshJob($job);
        $payment = $this->prepareServicePayment(
            $paymentJob,
            new VehicleServicePaymentData(
                expectedVersion: $this->currentJobVersion($paymentJob),
                invoiceId: (int) $invoice->getKey(),
                paymentDate: '2026-06-07',
                amount: '100.000000',
                paymentMethodId: (int) $method->getKey(),
            ),
        );
        $this->assertSame(PaymentType::ServiceReceipt, $payment->paymentType);
        $this->assertCount(1, $payment->allocations);
        $paymentCount = $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn (): int => Payment::query()->count(),
        );
        $this->assertSame(0, $paymentCount);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No billable service job lines remain to invoice.');
        $this->createServiceInvoice($this->refreshJob($job), '2026-06-07');
    }

    public function test_status_workflow_rejects_invalid_transitions_and_records_history(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $this->changeStatus($job, VehicleServiceJobStatus::Inspected);
        $this->changeStatus($this->refreshJob($job), VehicleServiceJobStatus::InProgress);
        $this->changeStatus($this->refreshJob($job), VehicleServiceJobStatus::Completed);

        $this->assertSame(VehicleServiceJobStatus::Completed, $this->refreshJob($job)->status);
        $historyCount = $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn (): int => $job->statusHistories()->count(),
        );
        $this->assertSame(4, $historyCount);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid service job status transition');
        $this->changeStatus($this->refreshJob($job), VehicleServiceJobStatus::Draft);
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

    public function test_job_update_persists_and_clears_customer_complaint_on_inspection(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $this->saveInspection($job, new VehicleServiceInspectionData(
            customerComplaint: 'Old complaint',
            inspectionNotes: 'Keep this note',
            diagnosis: 'Keep diagnosis',
        ));

        $updated = $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn (): VehicleServiceJob => app(VehicleServiceJobService::class)->update(
                $this->refreshJob($job),
                new VehicleServiceJobData(
                    tenantId: $context['tenant_id'],
                    jobDate: '2026-06-07',
                    customerId: $context['customer_id'],
                    vehicleId: $context['vehicle_id'],
                    supervisorEmployeeId: $context['employee_id'],
                    odometerReading: '12000.000000',
                    fuelLevel: 'half',
                    customerComplaint: 'New complaint',
                    customerComplaintProvided: true,
                ),
                $this->currentJobVersion($job),
            ),
        );

        $this->assertSame('New complaint', $updated->inspection?->customer_complaint);
        $this->assertSame('Keep this note', $updated->inspection?->inspection_notes);
        $this->assertSame('Keep diagnosis', $updated->inspection?->diagnosis);

        $cleared = $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn (): VehicleServiceJob => app(VehicleServiceJobService::class)->update(
                $this->refreshJob($updated),
                new VehicleServiceJobData(
                    tenantId: $context['tenant_id'],
                    jobDate: '2026-06-07',
                    customerId: $context['customer_id'],
                    vehicleId: $context['vehicle_id'],
                    supervisorEmployeeId: $context['employee_id'],
                    odometerReading: '12000.000000',
                    fuelLevel: 'half',
                    customerComplaint: null,
                    customerComplaintProvided: true,
                ),
                $this->currentJobVersion($updated),
            ),
        );

        $this->assertNull($cleared->inspection?->customer_complaint);
        $this->assertSame('Keep this note', $cleared->inspection?->inspection_notes);
    }

    public function test_bill_to_customer_drives_service_invoice_and_payment_party(): void
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
                odometerReading: '12000.000000',
            )),
        );
        $this->assertSame($billToCustomerId, (int) $job->bill_to_customer_id);
        $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '1.000000', '150.000000');
        $this->changeStatus($job, VehicleServiceJobStatus::InProgress);
        $this->changeStatus($this->refreshJob($job), VehicleServiceJobStatus::Completed);

        $invoice = $this->createServiceInvoice($this->refreshJob($job), '2026-06-07');
        $this->assertSame('customer', $invoice->party_type);
        $this->assertSame($billToCustomerId, (int) $invoice->party_id);

        $method = $this->paymentMethod($context);
        $paymentJob = $this->refreshJob($job);
        $payment = $this->prepareServicePayment(
            $paymentJob,
            new VehicleServicePaymentData(
                expectedVersion: $this->currentJobVersion($paymentJob),
                invoiceId: (int) $invoice->getKey(),
                paymentDate: '2026-06-07',
                amount: '50.000000',
                paymentMethodId: (int) $method->getKey(),
            ),
        );

        $this->assertSame($billToCustomerId, $payment->partyId);
    }

    public function test_job_resource_keeps_decimals_readable_and_relations_compact(): void
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
        $this->assertSame($this->currentJobVersion($job), $resource['row_version']);
    }

    public function test_vehicle_service_boolean_inputs_are_normalized_before_validation(): void
    {
        $this->withoutMiddleware();
        $context = $this->context();
        $this->actingAsTenantUser($context['tenant_id']);
        $job = $this->createJob($context);

        foreach ([
            true => [true, 'true', 1, '1'],
            false => [false, 'false', 0, '0'],
        ] as $expected => $values) {
            foreach ($values as $index => $value) {
                $this->tenantPostJson($context['tenant_id'], "/api/v1/vehicle-service/jobs/{$job->getKey()}/lines", [
                    'tenant_id' => $context['tenant_id'],
                    'expected_version' => $this->currentJobVersion($job),
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

    public function test_optional_job_fields_persist_and_nullable_commission_fields_default_to_none(): void
    {
        $this->withoutMiddleware();
        $context = $this->context();
        $this->actingAsTenantUser($context['tenant_id']);

        $createdJobId = (int) $this->tenantPostJson($context['tenant_id'], '/api/v1/vehicle-service/jobs', [
            'tenant_id' => $context['tenant_id'],
            'job_date' => '2026-06-07',
            'type' => 'full_service',
            'customer_id' => $context['customer_id'],
            'vehicle_id' => $context['vehicle_id'],
            'supervisor_employee_id' => $context['employee_id'],
            'supervisor_commission_type' => null,
            'supervisor_commission_value' => null,
            'odometer_reading' => '10000',
            'next_service_mileage' => '15000',
            'manual_job_card' => 'MJC-1042',
        ])->assertCreated()
            ->assertJsonPath('data.supervisor_commission_type', VehicleServiceCommissionType::None->value)
            ->assertJsonPath('data.supervisor_commission_value', '0.000000')
            ->assertJsonPath('data.next_service_mileage', '15000.000000')
            ->assertJsonPath('data.manual_job_card', 'MJC-1042')
            ->json('data.id');

        $job = $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn (): VehicleServiceJob => VehicleServiceJob::query()->findOrFail($createdJobId),
        );
        $this->assertSame('15000.000000', (string) $job->next_service_mileage);
        $this->assertSame('MJC-1042', $job->manual_job_card);
        $line = $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '1.000000', '25.000000');

        $this->tenantPostJson($context['tenant_id'], "/api/v1/vehicle-service/jobs/{$job->getKey()}/lines/{$line->getKey()}/employees", [
            'tenant_id' => $context['tenant_id'],
            'expected_version' => $this->currentJobVersion($job),
            'employee_id' => $context['employee_id'],
            'role_type' => 'technician',
            'commission_type' => null,
            'commission_value' => null,
        ])->assertCreated()
            ->assertJsonPath('data.commission_type', VehicleServiceCommissionType::None->value)
            ->assertJsonPath('data.commission_value', '0.000000');
    }

    public function test_job_type_enforces_mileage_field_rules(): void
    {
        $this->withoutMiddleware();
        $context = $this->context();
        $this->actingAsTenantUser($context['tenant_id']);
        $basePayload = [
            'tenant_id' => $context['tenant_id'],
            'job_date' => '2026-06-07',
            'customer_id' => $context['customer_id'],
            'vehicle_id' => $context['vehicle_id'],
        ];

        $this->tenantPostJson($context['tenant_id'], '/api/v1/vehicle-service/jobs', [
            ...$basePayload,
            'type' => 'full_service',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('odometer_reading');

        $this->tenantPostJson($context['tenant_id'], '/api/v1/vehicle-service/jobs', [
            ...$basePayload,
            'type' => 'body_wash',
            'odometer_reading' => '10000',
            'next_service_mileage' => '15000',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['odometer_reading', 'next_service_mileage']);

        $this->tenantPostJson($context['tenant_id'], '/api/v1/vehicle-service/jobs', [
            ...$basePayload,
            'type' => 'oil_change',
            'odometer_reading' => '10000',
            'next_service_mileage' => '15500',
        ])->assertCreated()
            ->assertJsonPath('data.type', 'oil_change')
            ->assertJsonPath('data.odometer_reading', '10000.000000')
            ->assertJsonPath('data.next_service_mileage', '15500.000000');

        $this->tenantPostJson($context['tenant_id'], '/api/v1/vehicle-service/jobs', [
            ...$basePayload,
            'type' => 'accessories',
            'odometer_reading' => '10000',
            'next_service_mileage' => '15000',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['odometer_reading', 'next_service_mileage']);
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

        $eligible = $this->issueLines($job);
        $this->assertSame([(int) $inventory->getKey()], $eligible->pluck('id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_line_uom_must_belong_to_selected_item(): void
    {
        $this->withoutMiddleware();
        $context = $this->context();
        $otherUomId = $this->uom($context['tenant_id'], 'ALT-'.$context['tenant_id']);
        $this->actingAsTenantUser($context['tenant_id']);
        $job = $this->createJob($context);

        $this->tenantPostJson($context['tenant_id'], "/api/v1/vehicle-service/jobs/{$job->getKey()}/lines", [
            'tenant_id' => $context['tenant_id'],
            'expected_version' => $this->currentJobVersion($job),
            'line_source_type' => 'service_item',
            'item_id' => $context['service']->getKey(),
            'uom_id' => $otherUomId,
            'description' => 'Wrong UOM service line',
            'quantity' => '1.000000',
            'unit_price' => '10.000000',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('uom_id');
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
        $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn () => app(VehicleServiceLineService::class)->create($job, new VehicleServiceLineData(
                lineSourceType: VehicleServiceLineSourceType::ServiceItem,
                description: 'Invalid parent reference',
                quantity: '1.000000',
                unitPrice: '10.000000',
                parentLineId: 999,
                itemId: (int) $context['service']->getKey(),
            )),
        );
    }

    public function test_partial_invoice_tracks_remaining_quantity_and_reaches_invoiced_status(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $line = $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '4.000000', '50.000000');
        $this->changeStatus($job, VehicleServiceJobStatus::InProgress);
        $this->changeStatus($this->refreshJob($job), VehicleServiceJobStatus::Completed);

        $first = $this->createServiceInvoice($this->refreshJob($job), '2026-06-07', [(int) $line->getKey() => '1.500000']);
        $this->assertSame(InvoiceStatus::Posted, $first->status);
        $this->assertSame('75.000000', (string) $first->grand_total);
        $this->assertSame(VehicleServiceJobStatus::Completed, $this->refreshJob($job)->status);
        $readiness = $this->billableLines($this->refreshJob($job))->firstWhere('id', $line->getKey());
        $this->assertSame('1.500000', (string) $readiness?->invoiced_quantity);
        $this->assertSame('2.500000', (string) $readiness?->remaining_billable_quantity);
        $this->assertSame('partially_invoiced', $readiness?->invoice_state);

        $second = $this->createServiceInvoice($this->refreshJob($job), '2026-06-07', [(int) $line->getKey() => '2.500000']);
        $this->assertSame(InvoiceStatus::Posted, $second->status);
        $this->assertSame(VehicleServiceJobStatus::Invoiced, $this->refreshJob($job)->status);
    }

    public function test_cancelled_invoice_source_quantity_can_be_invoiced_again(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $line = $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '1.000000', '80.000000');
        $this->changeStatus($job, VehicleServiceJobStatus::InProgress);
        $this->changeStatus($this->refreshJob($job), VehicleServiceJobStatus::Completed);
        $first = $this->createServiceInvoice($this->refreshJob($job), '2026-06-07');
        $this->withTenantExecutionContext((int) $context['tenant_id'], function () use ($first): void {
            $first->forceFill(['status' => InvoiceStatus::Cancelled->value])->save();
        });

        $replacement = $this->createServiceInvoice($this->refreshJob($job), '2026-06-07', [(int) $line->getKey() => '1.000000']);
        $this->assertNotSame($first->getKey(), $replacement->getKey());
        $this->assertSame('80.000000', (string) $replacement->grand_total);
    }

    public function test_payment_creation_allocates_invoice_and_updates_job_status(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '1.000000', '250.000000');
        $this->changeStatus($job, VehicleServiceJobStatus::InProgress);
        $this->changeStatus($this->refreshJob($job), VehicleServiceJobStatus::Completed);
        $invoice = $this->createServiceInvoice($this->refreshJob($job), '2026-06-07');
        $this->assertSame(InvoiceStatus::Posted, $invoice->status);

        $method = $this->paymentMethod($context);
        $this->paymentFinanceContext($context['tenant_id']);

        try {
            $paymentJob = $this->refreshJob($job);
            $this->prepareServicePayment($paymentJob, new VehicleServicePaymentData(
                expectedVersion: $this->currentJobVersion($paymentJob),
                invoiceId: (int) $invoice->getKey(),
                paymentDate: '2026-06-07',
                amount: '251.000000',
                paymentMethodId: (int) $method->getKey(),
            ));
            $this->fail('Expected payment amount above the invoice balance to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Payment amount cannot exceed invoice remaining balance.', $exception->getMessage());
        }

        $paymentJob = $this->refreshJob($job);
        $first = $this->createServicePayment($paymentJob, new VehicleServicePaymentData(
            expectedVersion: $this->currentJobVersion($paymentJob),
            invoiceId: (int) $invoice->getKey(),
            paymentDate: '2026-06-07',
            amount: '100.000000',
            paymentMethodId: (int) $method->getKey(),
        ));
        $this->assertSame(PaymentDocumentStatus::Approved, $first->document_status);
        $this->assertSame(PaymentPostingStatus::Posted, $first->posting_status);
        $this->assertSame(PaymentAllocationState::FullyAllocated, $first->allocation_status);
        $balanceDue = $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn (): string => (string) Invoice::query()->findOrFail($invoice->getKey())->balance_due,
        );
        $this->assertSame('150.000000', $balanceDue);
        $this->assertSame(VehicleServiceJobStatus::PartiallyPaid, $this->refreshJob($job)->status);
        $resourceJob = $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn (): VehicleServiceJob => $job->refresh()->load(app(VehicleServiceJobService::class)->relations()),
        );
        $resource = (new VehicleServiceJobResource($resourceJob))->resolve();
        $this->assertSame('150.000000', $resource['invoice_links'][0]['balance_due']);

        $paymentJob = $this->refreshJob($job);
        $second = $this->createServicePayment($paymentJob, new VehicleServicePaymentData(
            expectedVersion: $this->currentJobVersion($paymentJob),
            invoiceId: (int) $invoice->getKey(),
            paymentDate: '2026-06-07',
            amount: '150.000000',
            paymentMethodId: (int) $method->getKey(),
        ));
        $this->assertSame(PaymentDocumentStatus::Approved, $second->document_status);
        $this->assertSame(PaymentPostingStatus::Posted, $second->posting_status);
        $this->assertSame(PaymentAllocationState::FullyAllocated, $second->allocation_status);
        $this->assertSame(VehicleServiceJobStatus::Paid, $this->refreshJob($job)->status);
        $linkCount = $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn (): int => VehicleServicePaymentLink::query()->where('vehicle_service_job_id', $job->getKey())->count(),
        );
        $this->assertSame(2, $linkCount);
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
        $this->issueInventory(
            $job,
            $context['warehouse_id'],
            $context['warehouse_location_id'],
            lineIds: [(int) $otherLine->getKey()],
        );
    }

    public function test_vehicle_service_http_actions_reject_stale_expected_version(): void
    {
        $this->withoutMiddleware();
        $context = $this->context();
        $this->actingAsTenantUser($context['tenant_id']);
        $job = $this->createJob($context);

        $this->tenantPatchJson($context['tenant_id'], "/api/v1/vehicle-service/jobs/{$job->getKey()}/start", [
            'tenant_id' => $context['tenant_id'],
            'expected_version' => 999,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('expected_version');

        $this->assertSame(VehicleServiceJobStatus::Draft, $this->refreshJob($job)->status);
    }

    public function test_inventory_issue_api_returns_domain_error_when_stock_is_short(): void
    {
        $this->withoutMiddleware();
        $context = $this->context();
        $this->actingAsTenantUser($context['tenant_id']);
        $this->receiveStock($context, '1.000000');
        $job = $this->createJob($context);
        $line = $this->line($job, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '2.000000', '20.000000');

        $this->tenantPostJson($context['tenant_id'], "/api/v1/vehicle-service/jobs/{$job->getKey()}/issue-inventory", [
            'tenant_id' => $context['tenant_id'],
            'expected_version' => $this->currentJobVersion($job),
            'warehouse_id' => $context['warehouse_id'],
            'warehouse_location_id' => $context['warehouse_location_id'],
            'line_ids' => [(int) $line->getKey()],
        ])->assertUnprocessable()
            ->assertJsonPath('error.code', 'DOMAIN_RULE_FAILED')
            ->assertJsonPath('error.message', 'Inventory issue quantity cannot exceed available stock.');
    }

    public function test_inventory_issue_api_requires_exact_warehouse_location(): void
    {
        $this->withoutMiddleware();
        $context = $this->context();
        $this->actingAsTenantUser($context['tenant_id']);
        $this->receiveStock($context, '2.000000');
        $job = $this->createJob($context);
        $line = $this->line($job, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '1.000000', '20.000000');

        $this->tenantPostJson($context['tenant_id'], "/api/v1/vehicle-service/jobs/{$job->getKey()}/issue-inventory", [
            'tenant_id' => $context['tenant_id'],
            'expected_version' => $this->currentJobVersion($job),
            'warehouse_id' => $context['warehouse_id'],
            'line_ids' => [(int) $line->getKey()],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('warehouse_location_id');
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

        $readiness = $this->issueLines($job, $context['warehouse_id'], $context['warehouse_location_id'])
            ->firstWhere('id', $line->getKey());

        $this->assertFalse((bool) $readiness?->issue_eligible);
        $this->assertSame(
            'Batch, lot, and serial tracked items require tracking references in the Inventory workflow.',
            $readiness?->inventory_warning,
        );
    }

    public function test_split_http_endpoints_preserve_resources_and_document_validation(): void
    {
        $this->withoutMiddleware();
        $context = $this->context();
        $this->actingAsTenantUser($context['tenant_id']);
        $job = $this->createJob($context);
        $line = $this->line(
            $job,
            VehicleServiceLineSourceType::ServiceItem,
            $context['service'],
            '1.000000',
            '25.000000',
        );
        $query = http_build_query(['tenant_id' => $context['tenant_id']]);

        $this->tenantGetJson($context['tenant_id'], "/api/v1/vehicle-service/jobs/{$job->getKey()}/lines?{$query}")
            ->assertOk()
            ->assertJsonPath('data.0.id', $line->getKey())
            ->assertJsonPath('data.0.line_total', '25.000000');

        $this->tenantGetJson($context['tenant_id'], "/api/v1/vehicle-service/jobs/{$job->getKey()}/status-history?{$query}")
            ->assertOk()
            ->assertJsonPath('data.0.new_status', VehicleServiceJobStatus::Draft->value);

        $this->tenantPostJson($context['tenant_id'], "/api/v1/vehicle-service/jobs/{$job->getKey()}/documents", [
            'tenant_id' => $context['tenant_id'],
            'expected_version' => $this->currentJobVersion($job),
            'document_type' => 'image',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    /** @return array<string, mixed> */
    private function context(string $suffix = ''): array
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(5));
        $tenantId = $this->tenant($suffix);
        FinancePostingFixture::seedCustomerInvoiceProfiles($tenantId);
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
            fn () => app(VehicleServiceInspectionService::class)->save($job, $data),
        );
    }

    private function assignEmployee(
        VehicleServiceJob $job,
        VehicleServiceJobLine $line,
        VehicleServiceEmployeeAssignmentData $data,
    ) {
        return $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn () => app(VehicleServiceEmployeeAssignmentService::class)->create($job, $line, $data),
        );
    }

    private function changeStatus(VehicleServiceJob $job, VehicleServiceJobStatus $status): VehicleServiceJob
    {
        return $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn (): VehicleServiceJob => app(VehicleServiceStatusService::class)->change($job, $status),
        );
    }

    private function createServiceInvoice(VehicleServiceJob $job, string $invoiceDate, array $lineQuantities = [])
    {
        return $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn () => app(VehicleServiceInvoiceIntegrationService::class)->create($job, $invoiceDate, $lineQuantities),
        );
    }

    private function billableLines(VehicleServiceJob $job)
    {
        return $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn () => app(VehicleServiceInvoiceIntegrationService::class)->billableLines($job),
        );
    }

    private function prepareServicePayment(VehicleServiceJob $job, VehicleServicePaymentData $data)
    {
        return $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn () => app(VehicleServicePaymentIntegrationService::class)->prepare(
                VehicleServiceJob::query()->findOrFail($job->getKey()),
                $data,
            ),
        );
    }

    private function createServicePayment(VehicleServiceJob $job, VehicleServicePaymentData $data)
    {
        return $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn () => app(VehicleServicePaymentIntegrationService::class)->create(
                VehicleServiceJob::query()->findOrFail($job->getKey()),
                $data,
            ),
        );
    }

    private function issueInventory(VehicleServiceJob $job, int $warehouseId, int $warehouseLocationId, array $lineIds = [])
    {
        return $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn () => app(VehicleServiceInventoryIntegrationService::class)->issue($job, $warehouseId, $warehouseLocationId, lineIds: $lineIds),
        );
    }

    private function issueLines(VehicleServiceJob $job, ?int $warehouseId = null, ?int $warehouseLocationId = null)
    {
        return $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn () => app(VehicleServiceInventoryIntegrationService::class)->issueLines($job, $warehouseId, $warehouseLocationId),
        );
    }

    private function actingAsTenantUser(int $tenantId): void
    {
        $userId = TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Vehicle',
            'last_name' => 'Service',
            'email' => 'vehicle-service-'.Str::lower(Str::random(8)).'@example.test',
            'password' => 'secret-password',
            'status' => 'active',
        ]);

        $user = $this->withTenantExecutionContext(
            $tenantId,
            fn (): UserModel => UserModel::query()->findOrFail($userId),
        );

        $this->actingAs($user);
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
                isCustomerSupplied: $customerSupplied,
            )),
        );
    }

    private function paymentMethod(array $context): PaymentMethod
    {
        return $this->withTenantExecutionContext(
            (int) $context['tenant_id'],
            fn (): PaymentMethod => app(PaymentMethodService::class)->create([
                'code' => 'CASH-'.Str::upper(Str::random(5)),
                'name' => 'Cash',
                'method_type' => PaymentMethodType::Cash->value,
                'direction_allowed' => PaymentMethodDirection::Inbound->value,
                'requires_reference' => false,
                'requires_instrument_details' => false,
                'is_active' => true,
            ], $context['tenant_id'], null),
        );
    }

    private function paymentFinanceContext(int $tenantId): void
    {
        FinancePostingFixture::seedCustomerPaymentProfiles($tenantId);
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

    private function setTenantBaseCurrency(int $tenantId): int
    {
        $currencyId = CurrencyFixture::create();
        DB::table('tenants')->where('id', $tenantId)->update(['base_currency_id' => $currencyId]);

        return $currencyId;
    }

    private function servicePrice(Item $item, int $currencyId, int $uomId, string $amount): void
    {
        $this->withTenantExecutionContext(
            (int) $item->tenant_id,
            fn () => app(ItemPriceService::class)->create($item, new ItemPriceData(
                priceType: ItemPriceType::Service,
                amount: $amount,
                currencyId: $currencyId,
                uomId: $uomId,
                organizationUnitId: null,
                effectiveFrom: '2026-01-01',
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
            'updated_at' => now()]);
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
