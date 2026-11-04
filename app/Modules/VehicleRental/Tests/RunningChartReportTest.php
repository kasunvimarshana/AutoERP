<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Core\Tenancy\TenantFeature;
use Modules\User\Constants\UserGuard;
use Modules\User\Constants\UserOrganizationUnitStatus;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RunningChartReportService;
use Modules\VehicleRental\Services\RunningChartService;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\Support\ActiveTenantSubscriptionFixture;
use Tests\Support\TenantAuthenticationFixture;
use Tests\TestCase;

final class RunningChartReportTest extends TestCase
{
    use BuildsRentalFixture, RefreshDatabase;

    private const ROOT = '/api/v1/vehicle-rental';

    public function test_running_chart_report_matrix_generation(): void
    {
        $this->mock(RentalAuthorization::class, function ($mock): void {
            $mock->shouldReceive('assert')->zeroOrMoreTimes();
            $mock->shouldReceive('assertUse')->zeroOrMoreTimes();
            $mock->shouldReceive('assertChart')->zeroOrMoreTimes();
        });

        $this->custodyFixture(function ($context, $use, $facts): void {
            $charts = app(RunningChartService::class);
            $chart = $charts->create($context, (int) $use->id, (int) $use->row_version, $facts);
            $charts->change($context, (int) $chart->id, (int) $chart->row_version, RunningChartAction::Finalize);

            $reports = app(RunningChartReportService::class);
            $data = $reports->generate($context, [
                'agreement_id' => (int) $use->customer_agreement_id,
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
            ]);

            self::assertArrayHasKey('header', $data);
            self::assertArrayHasKey('rows', $data);
            self::assertArrayHasKey('summary', $data);

            self::assertCount(30, $data['rows']);

            // Find the 2026-09-07 row
            $activeRows = array_values(array_filter($data['rows'], fn ($r) => $r['is_active_day']));
            self::assertCount(1, $activeRows);

            $activeDay = $activeRows[0];
            self::assertSame('07/09/2026', $activeDay['date']);
            self::assertSame('100', $activeDay['start_odometer']);
            self::assertSame('150', $activeDay['finish_odometer']);
            self::assertSame(50, $activeDay['comparative_mileage']);
            self::assertSame('1.50', $activeDay['ot_hours']);
            self::assertSame('N', $activeDay['ot_type']);

            self::assertSame(50, $data['summary']['total_comparative_mileage']);
            self::assertSame(50, $data['summary']['final_cumulative_mileage']);
            self::assertSame('1.50', $data['summary']['total_normal_ot_hours']);
            self::assertSame('1.50', $data['summary']['total_ot_hours']);

            // Verify that agreed included_km in terms is accurately read and excess_km is computed
            $currentTerms = DB::table('vehicle_rental_customer_agreements')
                ->where('id', $use->customer_agreement_id)
                ->value('terms');
            $decodedTerms = json_decode((string) $currentTerms, true) ?? [];
            $decodedTerms['included_km'] = '30.000000';
            DB::table('vehicle_rental_customer_agreements')
                ->where('id', $use->customer_agreement_id)
                ->update(['terms' => json_encode($decodedTerms)]);

            $dataWithTerms = $reports->generate($context, [
                'agreement_id' => (int) $use->customer_agreement_id,
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
            ]);

            self::assertSame(30, $dataWithTerms['summary']['included_km']);
            self::assertSame(20, $dataWithTerms['summary']['excess_km']); // 50 km total - 30 km included = 20 km excess
        });
    }

    public function test_running_chart_report_controller_endpoints(): void
    {
        [$context, $customerInput, $ownerInput] = $this->loginFixture();

        // 1. Activate Customer Agreement
        $c = $this->postJson(self::ROOT.'/customer/agreements', $customerInput)->assertCreated()->json('data');
        $c = $this->postJson(self::ROOT.'/customer/agreements/'.$c['id'].'/activate', ['expected_version' => $c['row_version']])->assertOk()->json('data');

        // 2. Activate Owner Agreement
        $o = $this->postJson(self::ROOT.'/owner/agreements', $ownerInput)->assertCreated()->json('data');
        $o = $this->postJson(self::ROOT.'/owner/agreements/'.$o['id'].'/activate', ['expected_version' => $o['row_version']])->assertOk()->json('data');

        // 3. Plan Vehicle Use
        $use = $this->postJson(self::ROOT.'/customer/agreements/'.$c['id'].'/vehicles', [
            'expected_version' => $c['row_version'],
            'vehicle_id' => $ownerInput['vehicle_id'],
            'owner_agreement_id' => $o['id'],
            'starts_at' => '2026-09-07T09:00:00+05:30',
            'ends_at' => '2026-09-08T09:00:00+05:30',
        ])->assertCreated()->json('data');

        // 4. Handover
        $use = $this->postJson(self::ROOT.'/vehicle-uses/'.$use['id'].'/handover', [
            'expected_version' => $use['row_version'],
            'occurred_at' => '2026-09-07T09:00:00+05:30',
            'odometer' => '100',
            'reason' => 'Collected',
        ])->assertOk()->json('data');

        // 5. Create & Finalize Running Chart
        $chart = $this->postJson(self::ROOT.'/vehicle-uses/'.$use['id'].'/running-charts', [
            'expected_version' => $use['row_version'],
            'reference' => 'CHART-TEST-001',
            'starts_at' => '2026-09-07T09:00:15+05:30',
            'ends_at' => '2026-09-07T17:00:30+05:30',
            'start_odometer' => '100',
            'end_odometer' => '150.25',
            'garage_km' => '0',
            'normal_ot_minutes' => 90,
        ])->assertCreated()->json('data');

        $this->postJson(self::ROOT.'/running-charts/'.$chart['id'].'/finalize', [
            'expected_version' => $chart['row_version'],
        ])->assertOk();

        $params = [
            'agreement_id' => $c['id'],
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
        ];

        // 6. JSON endpoint
        $response = $this->getJson(self::ROOT.'/reports/running-chart-sheet?'.http_build_query($params));
        $response->assertOk()
            ->assertJsonStructure([
                'header' => ['company_name', 'report_title', 'agreement_number', 'client_name'],
                'rows',
                'summary' => ['total_comparative_mileage', 'final_cumulative_mileage', 'total_ot_hours'],
            ]);

        // 7. HTML print view
        $printResponse = $this->get(self::ROOT.'/reports/running-chart-sheet/export/print?'.http_build_query($params));
        $printResponse->assertOk()
            ->assertSee('RUNNING CHART FOR LESSEE')
            ->assertSee('07/09/2026')
            ->assertSee('window.print()', false);

        // 8. HTML preview view
        $htmlResponse = $this->get(self::ROOT.'/reports/running-chart-sheet/export/html?'.http_build_query($params));
        $htmlResponse->assertOk()
            ->assertSee('Running Chart Sheet Preview');

        // 9. PDF export
        Pdf::fake();
        $pdfResponse = $this->get(self::ROOT.'/reports/running-chart-sheet/export/pdf?'.http_build_query($params));
        $pdfResponse->assertOk();
        Pdf::assertRespondedWithPdf(
            fn (PdfBuilder $pdf): bool => $pdf->viewName === 'reports.vehicle-rental.running-chart-sheet'
                && $pdf->viewData['mode'] === 'pdf',
        );
    }

    private function loginFixture(): array
    {
        [$context, $customer, $owner] = $this->fixture();
        ActiveTenantSubscriptionFixture::create($context->tenantId, [TenantFeature::VEHICLE_RENTAL]);
        DB::table('user_organization_units')->insert([
            'tenant_id' => $context->tenantId,
            'organization_unit_id' => $context->organizationUnitId,
            'user_id' => $context->actorId,
            'status' => UserOrganizationUnitStatus::ACTIVE,
            'is_default' => true,
            'default_marker' => UserOrganizationUnitStatus::DEFAULT_MARKER,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $email = DB::table('users')->where('id', $context->actorId)->value('email');
        TenantAuthenticationFixture::provision($context->tenantId, $context->actorId, $email);
        foreach (array_keys(RentalAuthorization::descriptions()) as $name) {
            $permission = DB::table('permissions')->insertGetId([
                'tenant_id' => $context->tenantId,
                'name' => $name,
                'guard_name' => UserGuard::TENANT_API,
                'module' => 'VehicleRental',
                'description' => RentalAuthorization::descriptions()[$name],
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('user_permissions')->insert([
                'tenant_id' => $context->tenantId,
                'user_id' => $context->actorId,
                'permission_id' => $permission,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->flushHeaders();
        $token = $this->withHeader('X-Tenant-Id', (string) $context->tenantId)->postJson('/api/v1/auth/login', [
            'organization_unit_id' => $context->organizationUnitId,
            'identifier' => $email,
            'password' => 'secret-password',
        ])->assertOk()->json('token');
        $this->withToken($token);

        return [$context, $customer, $owner];
    }
}
