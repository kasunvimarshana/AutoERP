<?php

declare(strict_types=1);

namespace Tests\Feature\VehicleRental;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\VehicleRental\Enums\RentalCustodyEventType;
use Modules\VehicleRental\Enums\RentalUsageEventApplicability;
use Modules\VehicleRental\Enums\RentalUsageEventType;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalVehicleAllocation;
use Modules\VehicleRental\Services\RentalAgreementService;
use Modules\VehicleRental\Services\RentalAllocationService;
use Modules\VehicleRental\Services\RentalCustodyService;
use Modules\VehicleRental\Services\RentalReplacementService;
use Modules\VehicleRental\Services\RentalUsageService;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\TestCase;

final class RentalRunningChartReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_running_chart_report_generation_and_export(): void
    {
        $currencyId = $this->createCurrency();
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);
        $vehicle1Id = $this->createVehicle($tenantId, 'CBC-9180', '137000.000000');
        $vehicle2Id = $this->createVehicle($tenantId, 'CAP-1302', '190000.000000');
        $driverId = $this->createEmployee($tenantId, 'Sunil Perera');

        $this->withTenantExecutionContext($tenantId, function () use (
            $tenantId, $customerId, $currencyId, $vehicle1Id, $vehicle2Id, $driverId
        ): void {
            $agreementService = app(RentalAgreementService::class);
            $allocationService = app(RentalAllocationService::class);
            $custodyService = app(RentalCustodyService::class);
            $replacementService = app(RentalReplacementService::class);
            $usageService = app(RentalUsageService::class);

            // 1. Customer Agreement with 1,000 included KM
            $agreement = $agreementService->create([
                'agreement_number' => 'LAG7000012',
                'agreement_kind' => 'customer_rental',
                'customer_id' => $customerId,
                'agreement_date' => '2026-07-01',
                'starts_at' => '2026-07-01 00:00:00',
                'ends_at' => '2026-07-31 23:59:59',
                'legal_context' => 'company',
                'rental_mode' => 'with_driver',
                'billing_cycle' => 'monthly',
                'billing_basis' => 'calendar_month',
                'proration_rule' => 'exact_day_count',
                'payment_term_days' => 30,
                'currency_id' => $currencyId,
                'rate_version' => [
                    'effective_from' => '2026-07-01 00:00:00',
                    'excess_km_method' => 'period',
                    'included_km' => '500.000000',
                    'currency_id' => $currencyId,
                    'components' => [[
                        'component_code' => 'base_rental',
                        'unit' => 'month',
                        'rate' => '150000.000000',
                        'multiplier' => '1.000000',
                        'calculation_order' => 1,
                    ]],
                ],
                'activate_rate_version' => true,
            ], $tenantId, null, null);
            $agreement->update(['status' => 'active']);

            // 2. Allocation 1 for CBC-9180
            $allocation1 = $allocationService->create($agreement, [
                'vehicle_id' => $vehicle1Id,
                'vehicle_source_type' => 'company_owned',
                'allocated_from' => '2026-07-01 00:00:00',
                'allocated_to' => '2026-07-31 23:59:59',
                'start_odometer' => '137130.000000',
                'drivers' => [[
                    'employee_id' => $driverId,
                    'assignment_role' => 'primary',
                    'is_primary' => true,
                    'assigned_from' => '2026-07-01 00:00:00',
                    'assigned_to' => '2026-07-31 23:59:59',
                ]],
            ], null);

            $handover1 = $custodyService->create($allocation1, [
                'event_type' => RentalCustodyEventType::CompanyToCustomer->value,
                'occurred_at' => '2026-07-01 05:00:00',
                'odometer' => '137130.000000',
                'from_role' => 'company',
                'to_role' => 'customer',
            ], null);
            $custodyService->confirm($handover1, (int) $handover1->row_version, null);
            $allocation1->refresh();

            $driverAssignment1 = $allocation1->driverAssignments->first();

            // Record Log 1 (2026-07-01): 137,130 -> 137,337 = 207 km, 5.00 hrs OT (300 mins), Rs 850 Pass
            $usageService->create($allocation1, [
                'usage_date' => '2026-07-01',
                'started_at' => '2026-07-01 05:00:00',
                'ended_at' => '2026-07-01 22:00:00',
                'driver_assignment_id' => $driverAssignment1->id,
                'start_odometer' => '137130.000000',
                'end_odometer' => '137337.000000',
                'garage_distance_km' => '0',
                'internal_distance_km' => '0',
                'normal_overtime_minutes' => 300,
                'double_overtime_minutes' => 0,
                'triple_overtime_minutes' => 0,
                'night_out_count' => '0',
                'metadata' => [
                    'trip_from' => 'Colombo',
                    'trip_to' => 'Galle',
                    'trip_purpose' => 'Factory audit',
                ],
                'events' => [[
                    'event_type' => RentalUsageEventType::Pass->value,
                    'applicability' => RentalUsageEventApplicability::Customer->value,
                    'occurred_at' => '2026-07-01 08:30:00',
                    'quantity' => '850.000000',
                    'unit' => 'LKR',
                    'reference_number' => 'EX-PASS-01',
                ]],
            ], null);

            // Record Log 2 (2026-07-02): 137,337 -> 137,543 = 206 km, 1.50 hrs OT (90 mins), Rs 750 Pass
            $usageService->create($allocation1, [
                'usage_date' => '2026-07-02',
                'started_at' => '2026-07-02 06:30:00',
                'ended_at' => '2026-07-02 20:00:00',
                'driver_assignment_id' => $driverAssignment1->id,
                'start_odometer' => '137337.000000',
                'end_odometer' => '137543.000000',
                'garage_distance_km' => '0',
                'internal_distance_km' => '0',
                'normal_overtime_minutes' => 90,
                'double_overtime_minutes' => 0,
                'triple_overtime_minutes' => 0,
                'night_out_count' => '0',
                'events' => [[
                    'event_type' => RentalUsageEventType::Pass->value,
                    'applicability' => RentalUsageEventApplicability::Customer->value,
                    'occurred_at' => '2026-07-02 10:00:00',
                    'quantity' => '750.000000',
                    'unit' => 'LKR',
                ]],
            ], null);

            // 3. Vehicle replacement on 2026-07-03: replace CBC-9180 with CAP-1302
            $replacement = $replacementService->replace($allocation1, [
                'new_vehicle_id' => $vehicle2Id,
                'vehicle_source_type' => 'company_owned',
                'replacement_at' => '2026-07-03 12:00:00',
                'reason_code' => 'breakdown',
                'reason' => 'Radiator issue',
                'billing_continuity_rule' => 'continue_period',
                'old_return' => [
                    'odometer' => '137597.000000',
                    'fuel_level_percent' => '50.00',
                ],
                'new_handover' => [
                    'odometer' => '190491.000000',
                    'fuel_level_percent' => '100.00',
                ],
            ], null);

            $allocation2 = RentalVehicleAllocation::query()->findOrFail($replacement->new_allocation_id);
            $driverAssignment2 = $allocation2->driverAssignments->first();

            // Record Log 3 on replacement allocation (2026-07-04): 190,491 -> 190,698 = 207 km
            $usageService->create($allocation2, [
                'usage_date' => '2026-07-04',
                'started_at' => '2026-07-04 05:00:00',
                'ended_at' => '2026-07-04 17:00:00',
                'driver_assignment_id' => $driverAssignment2->id,
                'start_odometer' => '190491.000000',
                'end_odometer' => '190698.000000',
                'garage_distance_km' => '0',
                'internal_distance_km' => '0',
                'normal_overtime_minutes' => 0,
                'double_overtime_minutes' => 120, // 2.00 hrs Double OT
                'triple_overtime_minutes' => 0,
                'night_out_count' => '1.00',
                'metadata' => [
                    'remarks' => 'Plant site visit',
                ],
            ], null);

            // 4. Test report generation for running chart data (custom date range 2026-07-01 to 2026-07-05)
            $reportService = app(\Modules\VehicleRental\Services\RentalRunningChartReportService::class);
            $data = $reportService->generate($tenantId, null, [
                'agreement_id' => $agreement->id,
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-05',
            ]);

            // Assert Header
            self::assertSame('LAG7000012', $data['header']['agreement_number']);
            self::assertSame('Unilever Sri Lanka Ltd', $data['header']['client_name']);
            self::assertSame('CBC-9180', $data['header']['agreement_vehicle_number']);
            self::assertSame('01/07/2026 To 05/07/2026', $data['header']['duration']);

            // Assert Rows
            self::assertCount(5, $data['rows']);

            // Row 1 (01/07): 207 km, cumul 207, 5.00 OT, N, Pass 850.00
            $row1 = $data['rows'][0];
            self::assertSame('01/07/2026', $row1['date']);
            self::assertSame('137130', $row1['start_odometer']);
            self::assertSame('137337', $row1['finish_odometer']);
            self::assertSame(207, $row1['comparative_mileage']);
            self::assertSame(207, $row1['cumulative_mileage']);
            self::assertSame('05:00 AM', $row1['on_time']);
            self::assertSame('10:00 PM', $row1['off_time']);
            self::assertSame('5.00', $row1['ot_hours']);
            self::assertSame('N', $row1['ot_type']);
            self::assertSame('850.00', $row1['pass']);

            // Row 2 (02/07): 206 km, cumul 413, 1.50 OT, N, Pass 750.00
            $row2 = $data['rows'][1];
            self::assertSame('02/07/2026', $row2['date']);
            self::assertSame(206, $row2['comparative_mileage']);
            self::assertSame(413, $row2['cumulative_mileage']);
            self::assertSame('1.50', $row2['ot_hours']);
            self::assertSame('750.00', $row2['pass']);

            // Row 3 (03/07): idle / non-log day
            $row3 = $data['rows'][2];
            self::assertSame('03/07/2026', $row3['date']);
            self::assertSame(0, $row3['comparative_mileage']);
            self::assertSame(413, $row3['cumulative_mileage']);

            // Row 4 (04/07): on replacement allocation CAP-1302!
            $row4 = $data['rows'][3];
            self::assertSame('04/07/2026', $row4['date']);
            self::assertSame('190491', $row4['start_odometer']);
            self::assertSame('190698', $row4['finish_odometer']);
            self::assertSame(207, $row4['comparative_mileage']);
            self::assertSame(620, $row4['cumulative_mileage']);
            self::assertSame('2.00', $row4['ot_hours']);
            self::assertSame('D', $row4['ot_type']);
            self::assertSame('1', $row4['night_out']);
            self::assertStringContainsString('For Repl. CAP-1302', $row4['remarks']);

            // Assert Summary
            $summary = $data['summary'];
            self::assertSame(620, $summary['total_comparative_mileage']);
            self::assertSame(620, $summary['final_cumulative_mileage']);
            self::assertSame('6.50', $summary['total_normal_ot_hours']); // 5.00 + 1.50
            self::assertSame('2.00', $summary['total_double_ot_hours']); // 2.00
            self::assertSame(1, $summary['total_night_out']);
            self::assertSame('1600.00', $summary['total_pass']); // 850 + 750
            // Included KM is 500. Cumulative is 620 => Excess KM = 120!
            self::assertSame(120, $summary['excess_km']);

            // 5. Test Preview and Print HTML Views
            $previewHtml = view('reports.vehicle-rental.running-chart-sheet', array_merge($data, ['mode' => 'preview']))->render();
            self::assertStringContainsString('RUNNING CHART FOR LESSEE', $previewHtml);
            self::assertStringContainsString('LAG7000012', $previewHtml);
            self::assertStringContainsString('Unilever Sri Lanka Ltd', $previewHtml);
            self::assertStringContainsString('For Repl. CAP-1302', $previewHtml);

            $printHtml = view('reports.vehicle-rental.running-chart-sheet', array_merge($data, ['mode' => 'print']))->render();
            self::assertStringContainsString('RUNNING CHART FOR LESSEE', $printHtml);
            self::assertStringContainsString('window.print()', $printHtml);

            // 6. Test PDF Builder View
            Pdf::fake();
            $pdf = Pdf::view('reports.vehicle-rental.running-chart-sheet', array_merge($data, ['mode' => 'pdf']))
                ->format('A4')
                ->orientation('landscape');
            self::assertSame('reports.vehicle-rental.running-chart-sheet', $pdf->viewName);
            self::assertSame('landscape', $pdf->orientation);
            self::assertSame('A4', $pdf->format);
        });
    }

    public function test_driver_in_out_times_and_salary_ot_calculation_in_running_chart(): void
    {
        $currencyId = $this->createCurrency();
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);
        $vehicleId = $this->createVehicle($tenantId, 'CAR-1234', '10000.000000');
        $driverId = $this->createEmployee($tenantId, 'Lasantha Karunanayaka', '200.000000', '2500.000000', '06:30', '18:30', 12.0);

        $this->withTenantExecutionContext($tenantId, function () use (
            $tenantId, $customerId, $currencyId, $vehicleId, $driverId
        ): void {
            $agreementService = app(RentalAgreementService::class);
            $allocationService = app(RentalAllocationService::class);
            $custodyService = app(RentalCustodyService::class);
            $usageService = app(RentalUsageService::class);
            $reportService = app(\Modules\VehicleRental\Services\RentalRunningChartReportService::class);

            $agreement = $agreementService->create([
                'agreement_number' => 'RA-NSB-001',
                'agreement_kind' => 'customer_rental',
                'customer_id' => $customerId,
                'agreement_date' => '2026-10-01',
                'starts_at' => '2026-10-01 00:00:00',
                'ends_at' => '2026-10-31 23:59:59',
                'rental_mode' => 'with_driver',
                'billing_cycle' => 'monthly',
                'billing_basis' => 'calendar_month',
                'proration_rule' => 'exact_day_count',
                'currency_id' => $currencyId,
                'rate_version' => [
                    'effective_from' => '2026-10-01 00:00:00',
                    'excess_km_method' => 'period',
                    'included_km' => '1000.000000',
                    'currency_id' => $currencyId,
                    'components' => [[
                        'component_code' => 'base_rental',
                        'unit' => 'month',
                        'rate' => '120000.000000',
                        'calculation_order' => 1,
                    ]],
                ],
                'activate_rate_version' => true,
            ], $tenantId, null, null);
            $agreement->update(['status' => 'active']);

            $allocation = $allocationService->create($agreement, [
                'vehicle_id' => $vehicleId,
                'vehicle_source_type' => 'company_owned',
                'allocated_from' => '2026-10-01 00:00:00',
                'allocated_to' => '2026-10-31 23:59:59',
                'start_odometer' => '10000.000000',
            ], null);

            $driverAssignment = $allocationService->assignDriver($allocation, [
                'employee_id' => $driverId,
                'assigned_from' => '2026-10-01 00:00:00',
                'is_primary' => true,
            ], null);

            $handover = $custodyService->create($allocation, [
                'event_type' => RentalCustodyEventType::CompanyToCustomer->value,
                'occurred_at' => '2026-10-01 06:00:00',
                'odometer' => '10000.000000',
                'from_role' => 'company',
                'to_role' => 'customer',
            ], null);
            $custodyService->confirm($handover, (int) $handover->row_version, null);
            $allocation->refresh();

            $allocation->load(['driverAssignments.employee']);
            $allocationResource = (new \Modules\VehicleRental\Http\Resources\RentalAllocationResource($allocation))->toArray(request());
            self::assertSame('06:30', $allocationResource['drivers'][0]['employee']['work_start_time']);
            self::assertSame('18:30', $allocationResource['drivers'][0]['employee']['work_end_time']);
            self::assertEquals(12, $allocationResource['drivers'][0]['employee']['standard_shift_hours']);

            // Record a daily usage with driver in/out times and calculated overtime salary
            // 06:30 AM to 08:00 PM = 13.5 hours duty. With 12h threshold = 1.5h OT (90 mins).
            // OT rate 200/hr = Rs. 300. Base salary = Rs. 2500. Total day pay = Rs. 2800.
            $usageLog = $usageService->create($allocation, [
                'usage_date' => '2026-10-01',
                'started_at' => '2026-10-01 06:30:00',
                'ended_at' => '2026-10-01 20:00:00',
                'driver_assignment_id' => $driverAssignment->id,
                'start_odometer' => '10000.000000',
                'end_odometer' => '10150.000000',
                'garage_distance_km' => '0',
                'internal_distance_km' => '0',
                'normal_overtime_minutes' => 90,
                'double_overtime_minutes' => 0,
                'triple_overtime_minutes' => 0,
                'night_out_count' => '0',
                'metadata' => [
                    'driver_in_time' => '2026-10-01T06:30',
                    'driver_out_time' => '2026-10-01T20:00',
                    'normal_hours_threshold' => 12,
                    'driver_hourly_rate' => 200,
                    'driver_daily_rate' => 2500,
                    'driver_ot_hours' => 1.5,
                    'driver_ot_amount' => 300,
                    'driver_salary_amount' => 2800,
                ],
            ], null);

            self::assertNotNull($usageLog->id);
            self::assertSame('2026-10-01T06:30', $usageLog->metadata['driver_in_time']);
            self::assertSame('2026-10-01T20:00', $usageLog->metadata['driver_out_time']);
            self::assertEquals(200, $usageLog->metadata['driver_hourly_rate']);
            self::assertEquals(2500, $usageLog->metadata['driver_daily_rate']);
            self::assertEquals(300, $usageLog->metadata['driver_ot_amount']);
            self::assertEquals(2800, $usageLog->metadata['driver_salary_amount']);

            // Record a second usage log that is overnight (finishes on next day)
            $usageLog2 = $usageService->create($allocation, [
                'usage_date' => '2026-10-02',
                'started_at' => '2026-10-02 20:00:00',
                'ended_at' => '2026-10-03 04:00:00',
                'driver_assignment_id' => $driverAssignment->id,
                'start_odometer' => '10150.000000',
                'end_odometer' => '10250.000000',
                'garage_distance_km' => '0',
                'internal_distance_km' => '0',
                'normal_overtime_minutes' => 0,
                'double_overtime_minutes' => 0,
                'triple_overtime_minutes' => 0,
                'night_out_count' => '1',
                'metadata' => [
                    'driver_in_time' => '2026-10-02T20:00',
                    'driver_out_time' => '2026-10-03T04:00',
                    'normal_hours_threshold' => 12,
                    'driver_hourly_rate' => 200,
                    'driver_daily_rate' => 2500,
                    'driver_ot_hours' => 0,
                    'driver_ot_amount' => 0,
                    'driver_salary_amount' => 2500,
                ],
            ], null);

            // Test Report generation with Driver Overtime & Salary Reconciliation
            $reportData = $reportService->generate($tenantId, null, [
                'agreement_id' => $agreement->id,
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-02',
            ]);

            self::assertSame('06:30 AM', $reportData['rows'][0]['on_time']);
            self::assertSame('08:00 PM', $reportData['rows'][0]['off_time']);
            self::assertSame('1.50', $reportData['rows'][0]['ot_hours']);
            self::assertSame('300.00', $reportData['rows'][0]['driver_ot_amount']);
            self::assertSame('2800.00', $reportData['rows'][0]['driver_salary_amount']);

            // Assert overnight row on 02/10 has (+1) indicator on OFF time
            self::assertSame('08:00 PM', $reportData['rows'][1]['on_time']);
            self::assertSame('04:00 AM (+1)', $reportData['rows'][1]['off_time']);
            self::assertSame('1', $reportData['rows'][1]['night_out']);
            self::assertSame('300.00', $reportData['summary']['total_driver_ot_amount']);
            self::assertSame('5300.00', $reportData['summary']['total_driver_salary_amount']);
        });
    }

    private function createCurrency(): int
    {
        return (int) DB::table('currencies')->insertGetId([
            'code' => 'LKR',
            'name' => 'Sri Lankan Rupee',
            'symbol' => 'Rs',
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTenant(int $currencyId): int
    {
        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'VR-REP-'.uniqid(),
            'name' => 'AT Tours & Transport (Pvt) Ltd',
            'slug' => 'at-tours-transport-'.uniqid(),
            'base_currency_id' => $currencyId,
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCustomer(int $tenantId, int $currencyId): int
    {
        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'customer_number' => 'CUS-'.uniqid(),
            'code' => 'CUS-UNILEVER',
            'name' => 'Unilever Sri Lanka Ltd',
            'customer_type' => 'company',
            'status' => 'active',
            'default_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createVehicle(int $tenantId, string $plateNumber, string $odometer): int
    {
        return (int) DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'vehicle_number' => $plateNumber,
            'registration_number' => $plateNumber,
            'odometer_reading' => $odometer,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createEmployee(
        int $tenantId,
        string $name,
        string $hourlyRate = '0.000000',
        string $dailyRate = '0.000000',
        ?string $workStartTime = null,
        ?string $workEndTime = null,
        ?float $standardShiftHours = null
    ): int {
        return (int) DB::table('hr_employees')->insertGetId([
            'tenant_id' => $tenantId,
            'employee_number' => 'EMP-'.uniqid(),
            'code' => 'EMP-'.uniqid(),
            'first_name' => $name,
            'last_name' => 'Driver',
            'display_name' => $name,
            'default_hourly_rate' => $hourlyRate,
            'default_daily_rate' => $dailyRate,
            'status' => 'active',
            'metadata' => json_encode([
                'work_start_time' => $workStartTime,
                'work_end_time' => $workEndTime,
                'standard_shift_hours' => $standardShiftHours,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
