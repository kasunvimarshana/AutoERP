<?php

declare(strict_types=1);

namespace Modules\VehicleService\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\VehicleService\DTOs\VehicleServiceEmployeeAssignmentData;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;
use Modules\VehicleService\Models\VehicleServiceLineEmployee;
use Modules\VehicleService\Services\VehicleServiceEmployeeAssignmentService;
use Tests\TestCase;

final class VehicleServiceLabourCommissionSplitTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_labour_commission_pool_is_split_after_assignment_membership_changes(): void
    {
        $fixture = $this->fixture();
        $service = app(VehicleServiceEmployeeAssignmentService::class);

        $first = $this->withTenantExecutionContext(
            $fixture['tenant_id'],
            fn (): VehicleServiceLineEmployee => $service->create(
                $fixture['job'],
                $fixture['line'],
                $this->assignmentData($fixture['employee_ids'][0]),
            ),
        );
        $this->assertSame('100.000000', (string) $first->commission_amount);

        $this->withTenantExecutionContext(
            $fixture['tenant_id'],
            fn (): VehicleServiceLineEmployee => $service->create(
                $fixture['job'],
                $fixture['line'],
                $this->assignmentData($fixture['employee_ids'][1]),
            ),
        );
        $this->assertSame(
            ['50.000000', '50.000000'],
            $this->commissionAmounts($fixture['tenant_id'], $fixture['line']),
        );

        $third = $this->withTenantExecutionContext(
            $fixture['tenant_id'],
            fn (): VehicleServiceLineEmployee => $service->create(
                $fixture['job'],
                $fixture['line'],
                $this->assignmentData($fixture['employee_ids'][2]),
            ),
        );
        $this->assertSame(
            ['33.333333', '33.333333', '33.333334'],
            $this->commissionAmounts($fixture['tenant_id'], $fixture['line']),
        );

        $third = $this->withTenantExecutionContext(
            $fixture['tenant_id'],
            fn (): VehicleServiceLineEmployee => $service->update(
                $fixture['job'],
                $fixture['line'],
                $third,
                new VehicleServiceEmployeeAssignmentData(
                    employeeId: $fixture['employee_ids'][2],
                    roleType: 'technician',
                    commissionType: VehicleServiceCommissionType::Fixed,
                    commissionValue: '100.000000',
                    status: 'cancelled',
                ),
            ),
        );
        $this->assertSame(
            ['50.000000', '50.000000', '0.000000'],
            $this->commissionAmounts($fixture['tenant_id'], $fixture['line']),
        );

        $this->withTenantExecutionContext(
            $fixture['tenant_id'],
            fn () => $service->delete($fixture['job'], $fixture['line'], $third),
        );
        $this->assertSame(
            ['50.000000', '50.000000'],
            $this->commissionAmounts($fixture['tenant_id'], $fixture['line']),
        );
    }

    private function assignmentData(int $employeeId): VehicleServiceEmployeeAssignmentData
    {
        return new VehicleServiceEmployeeAssignmentData(
            employeeId: $employeeId,
            roleType: 'technician',
            commissionType: VehicleServiceCommissionType::Fixed,
            commissionValue: '100.000000',
        );
    }

    /** @return list<string> */
    private function commissionAmounts(int $tenantId, VehicleServiceJobLine $line): array
    {
        return $this->withTenantExecutionContext(
            $tenantId,
            fn (): array => $line->employeeAssignments()
                ->orderBy('id')
                ->pluck('commission_amount')
                ->map(static fn ($amount): string => (string) $amount)
                ->all(),
        );
    }

    /** @return array{tenant_id: int, employee_ids: list<int>, job: VehicleServiceJob, line: VehicleServiceJobLine} */
    private function fixture(): array
    {
        $suffix = Str::upper(Str::random(6));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-VS-COM-'.$suffix,
            'name' => 'Vehicle Service Commission '.$suffix,
            'slug' => 'vehicle-service-commission-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $customerId = (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'customer_number' => 'CUS-'.$suffix,
            'code' => 'CUS-'.$suffix,
            'name' => 'Customer '.$suffix,
            'display_name' => 'Customer '.$suffix,
            'customer_type' => 'individual',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $vehicleId = (int) DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'vehicle_number' => 'VEH-'.$suffix,
            'registration_number' => 'REG-'.$suffix,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $employeeIds = [];
        foreach (range(1, 3) as $number) {
            $employeeIds[] = (int) DB::table('hr_employees')->insertGetId([
                'tenant_id' => $tenantId,
                'employee_number' => 'EMP-'.$number.'-'.$suffix,
                'code' => 'EMP-'.$number.'-'.$suffix,
                'first_name' => 'Technician '.$number,
                'display_name' => 'Technician '.$number,
                'status' => 'active',
                'availability_status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $jobId = (int) DB::table('vehicle_service_jobs')->insertGetId([
            'tenant_id' => $tenantId,
            'job_number' => 'VS-'.$suffix,
            'job_date' => '2026-07-17',
            'customer_id' => $customerId,
            'bill_to_customer_id' => $customerId,
            'vehicle_id' => $vehicleId,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $lineId = (int) DB::table('vehicle_service_job_lines')->insertGetId([
            'tenant_id' => $tenantId,
            'vehicle_service_job_id' => $jobId,
            'line_number' => 1,
            'line_source_type' => 'labour_item',
            'description' => 'Shared labour commission',
            'quantity' => '1.000000',
            'unit_price' => '1000.000000',
            'line_total' => '1000.000000',
            'is_employee_assignable' => true,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->withTenantExecutionContext($tenantId, fn (): array => [
            'tenant_id' => $tenantId,
            'employee_ids' => $employeeIds,
            'job' => VehicleServiceJob::query()->findOrFail($jobId),
            'line' => VehicleServiceJobLine::query()->findOrFail($lineId),
        ]);
    }
}
