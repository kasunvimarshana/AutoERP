<?php

declare(strict_types=1);

namespace Modules\VehicleService\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\Item;
use Modules\Item\Services\ItemCreationService;
use Modules\VehicleService\DTOs\VehicleServiceJobData;
use Modules\VehicleService\DTOs\VehicleServiceLineData;
use Modules\VehicleService\Enums\VehicleServiceBillingStatus;
use Modules\VehicleService\Enums\VehicleServiceLifecycleDimension;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Enums\VehicleServiceOperationalStatus;
use Modules\VehicleService\Enums\VehicleServicePaymentStatus;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Services\VehicleServiceInvoiceIntegrationService;
use Modules\VehicleService\Services\VehicleServiceJobService;
use Modules\VehicleService\Services\VehicleServiceLineService;
use Modules\VehicleService\Services\VehicleServiceStatusService;
use Tests\TestCase;

final class VehicleServiceLifecycleBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->trustTenantScopedRequestContextFromPayload();
    }

    public function test_created_job_initializes_independent_lifecycle_states_and_history(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);

        $this->assertSame(VehicleServiceOperationalStatus::Draft, $job->operational_status);
        $this->assertSame(VehicleServiceBillingStatus::Unbilled, $job->billing_status);
        $this->assertSame(VehicleServicePaymentStatus::Unpaid, $job->payment_status);

        $history = $this->withTenantExecutionContext(
            $context['tenant_id'],
            fn () => $job->statusHistories()->oldest('changed_at')->get(),
        );

        $this->assertCount(3, $history);
        $this->assertSame([
            VehicleServiceLifecycleDimension::Operational,
            VehicleServiceLifecycleDimension::Billing,
            VehicleServiceLifecycleDimension::Payment,
        ], $history->pluck('dimension')->all());
        $this->assertSame(['draft', 'unbilled', 'unpaid'], $history->pluck('new_status')->all());
    }

    public function test_operational_completion_does_not_imply_billing_or_payment_completion(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $this->line($job, $context['service'], '1.000000', '100.000000');

        $job = $this->changeOperational($job, VehicleServiceOperationalStatus::InProgress);
        $job = $this->changeOperational($job, VehicleServiceOperationalStatus::Completed);

        $this->assertSame(VehicleServiceOperationalStatus::Completed, $job->operational_status);
        $this->assertSame(VehicleServiceBillingStatus::Unbilled, $job->billing_status);
        $this->assertSame(VehicleServicePaymentStatus::Unpaid, $job->payment_status);
    }

    public function test_partial_and_full_invoice_update_billing_without_touching_payment(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $line = $this->line($job, $context['service'], '4.000000', '50.000000');
        $this->changeOperational($job, VehicleServiceOperationalStatus::InProgress);
        $this->changeOperational($this->refreshJob($job), VehicleServiceOperationalStatus::Completed);

        $first = $this->createServiceInvoice($this->refreshJob($job), [(int) $line->getKey() => '1.500000']);
        $job = $this->refreshJob($job);

        $this->assertSame('75.000000', (string) $first->grand_total);
        $this->assertSame(VehicleServiceOperationalStatus::Completed, $job->operational_status);
        $this->assertSame(VehicleServiceBillingStatus::PartiallyBilled, $job->billing_status);
        $this->assertSame(VehicleServicePaymentStatus::Unpaid, $job->payment_status);

        $second = $this->createServiceInvoice($job, [(int) $line->getKey() => '2.500000']);
        $job = $this->refreshJob($job);

        $this->assertSame('125.000000', (string) $second->grand_total);
        $this->assertSame(VehicleServiceOperationalStatus::Completed, $job->operational_status);
        $this->assertSame(VehicleServiceBillingStatus::Billed, $job->billing_status);
        $this->assertSame(VehicleServicePaymentStatus::Unpaid, $job->payment_status);
    }

    public function test_operational_transition_rejects_invalid_backward_move(): void
    {
        $context = $this->context();
        $job = $this->createJob($context);
        $this->changeOperational($job, VehicleServiceOperationalStatus::InProgress);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid service job operational status transition');
        $this->changeOperational($this->refreshJob($job), VehicleServiceOperationalStatus::Draft);
    }

    /** @return array{tenant_id:int, customer_id:int, vehicle_id:int, employee_id:int, uom_id:int, service:Item} */
    private function context(): array
    {
        $suffix = Str::upper(Str::random(5));
        $tenantId = $this->tenant($suffix);
        $uomId = $this->uom($tenantId, 'PCS-'.$suffix);
        $customerId = $this->customer($tenantId, 'CUS-'.$suffix);
        $vehicleId = $this->vehicle($tenantId, $customerId, 'VEH-'.$suffix);
        $employeeId = $this->employee($tenantId, 'EMP-'.$suffix);

        return [
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'vehicle_id' => $vehicleId,
            'employee_id' => $employeeId,
            'uom_id' => $uomId,
            'service' => $this->item($tenantId, 'SERVICE-'.$suffix, $uomId),
        ];
    }

    /** @param array{tenant_id:int, customer_id:int, vehicle_id:int, employee_id:int} $context */
    private function createJob(array $context): VehicleServiceJob
    {
        return $this->withTenantExecutionContext(
            $context['tenant_id'],
            fn (): VehicleServiceJob => app(VehicleServiceJobService::class)->create(new VehicleServiceJobData(
                tenantId: $context['tenant_id'],
                jobDate: '2026-06-07',
                customerId: $context['customer_id'],
                vehicleId: $context['vehicle_id'],
                supervisorEmployeeId: $context['employee_id'],
                odometerReading: '12000.000000',
                fuelLevel: 'half',
            )),
        );
    }

    private function line(VehicleServiceJob $job, Item $item, string $quantity, string $unitPrice)
    {
        return $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn () => app(VehicleServiceLineService::class)->create(
                VehicleServiceJob::query()->findOrFail($job->getKey()),
                new VehicleServiceLineData(
                    lineSourceType: VehicleServiceLineSourceType::ServiceItem,
                    description: 'Service line',
                    quantity: $quantity,
                    unitPrice: $unitPrice,
                    itemId: (int) $item->getKey(),
                    uomId: (int) $item->base_uom_id,
                ),
                $this->currentJobVersion($job),
            ),
        );
    }

    private function changeOperational(VehicleServiceJob $job, VehicleServiceOperationalStatus $status): VehicleServiceJob
    {
        return $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn (): VehicleServiceJob => app(VehicleServiceStatusService::class)->changeOperational(
                VehicleServiceJob::query()->findOrFail($job->getKey()),
                $status,
                expectedVersion: $this->currentJobVersion($job),
            ),
        );
    }

    /** @param array<int, string> $lineQuantities */
    private function createServiceInvoice(VehicleServiceJob $job, array $lineQuantities = [])
    {
        return $this->withTenantExecutionContext(
            (int) $job->tenant_id,
            fn () => app(VehicleServiceInvoiceIntegrationService::class)->create(
                VehicleServiceJob::query()->findOrFail($job->getKey()),
                '2026-06-07',
                $lineQuantities,
                expectedVersion: $this->currentJobVersion($job),
            ),
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

    private function item(int $tenantId, string $code, int $uomId): Item
    {
        return $this->withTenantExecutionContext(
            $tenantId,
            fn (): Item => app(ItemCreationService::class)->create(new CreateItemData(
                tenantId: $tenantId,
                code: $code,
                name: str_replace('-', ' ', $code),
                itemType: ItemType::Service,
                trackingType: TrackingType::None,
                costingMethod: CostingMethod::None,
                baseUomId: $uomId,
                isStockable: false,
            )),
        );
    }

    private function tenant(string $suffix): int
    {
        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-VSLB-'.$suffix,
            'name' => 'Vehicle Service Lifecycle '.$suffix,
            'slug' => 'vehicle-service-lifecycle-'.Str::lower($suffix),
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
