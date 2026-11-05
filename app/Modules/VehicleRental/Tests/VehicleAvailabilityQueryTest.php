<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Core\Tenancy\TenantFeature;
use Modules\User\Constants\UserGuard;
use Modules\User\Constants\UserOrganizationUnitStatus;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\VehicleUseService;
use Tests\Support\ActiveTenantSubscriptionFixture;
use Tests\Support\TenantAuthenticationFixture;
use Tests\TestCase;

final class VehicleAvailabilityQueryTest extends TestCase
{
    use BuildsRentalFixture, RefreshDatabase;

    private const ROOT = '/api/v1/vehicle-rental';

    public function test_available_vehicles_query_filters_conflicts_and_categories(): void
    {
        [$context, $customerInput, $ownerInput] = $this->loginFixture();

        // Create a second active vehicle
        $vehicle2Id = DB::table('vehicles')->insertGetId([
            'tenant_id' => $context->tenantId,
            'vehicle_number' => 'V-AVAIL-02',
            'registration_number' => 'WP-CAB-2222',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 1. Query availability before any use via API
        $response = $this->getJson(
            self::ROOT.'/vehicles/available?starts_at='.urlencode('2026-09-07T09:00:00+05:30').'&ends_at='.urlencode('2026-09-08T09:00:00+05:30')
        );
        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'registration_number', 'display_label']]]);
        $ids = array_column($response->json('data'), 'id');

        self::assertContains((int) $ownerInput['vehicle_id'], $ids);
        self::assertContains($vehicle2Id, $ids);

        // 2. Activate customer and owner agreements
        $c = $this->postJson(self::ROOT.'/customer/agreements', $customerInput)->assertCreated()->json('data');
        $c = $this->postJson(self::ROOT.'/customer/agreements/'.$c['id'].'/activate', ['expected_version' => $c['row_version']])->assertOk()->json('data');

        $o = $this->postJson(self::ROOT.'/owner/agreements', $ownerInput)->assertCreated()->json('data');
        $o = $this->postJson(self::ROOT.'/owner/agreements/'.$o['id'].'/activate', ['expected_version' => $o['row_version']])->assertOk()->json('data');

        // 3. Plan use on vehicle 1
        $this->postJson(self::ROOT.'/customer/agreements/'.$c['id'].'/vehicles', [
            'expected_version' => $c['row_version'],
            'vehicle_id' => $ownerInput['vehicle_id'],
            'owner_agreement_id' => $o['id'],
            'starts_at' => '2026-09-07T09:00:00+05:30',
            'ends_at' => '2026-09-08T09:00:00+05:30',
        ])->assertCreated();

        // 4. Query availability during overlapping period: vehicle 1 MUST be excluded
        $overlapResponse = $this->getJson(
            self::ROOT.'/vehicles/available?starts_at='.urlencode('2026-09-07T12:00:00+05:30').'&ends_at='.urlencode('2026-09-07T18:00:00+05:30')
        );
        $overlapResponse->assertOk();
        $overlapIds = array_column($overlapResponse->json('data'), 'id');

        self::assertNotContains((int) $ownerInput['vehicle_id'], $overlapIds);
        self::assertContains($vehicle2Id, $overlapIds);

        // 5. Query availability outside planned period: vehicle 1 is available
        $outsideResponse = $this->getJson(
            self::ROOT.'/vehicles/available?starts_at='.urlencode('2026-09-10T09:00:00+05:30').'&ends_at='.urlencode('2026-09-11T09:00:00+05:30')
        );
        $outsideResponse->assertOk();
        $outsideIds = array_column($outsideResponse->json('data'), 'id');

        self::assertContains((int) $ownerInput['vehicle_id'], $outsideIds);
        self::assertContains($vehicle2Id, $outsideIds);
    }

    public function test_available_vehicles_query_with_empty_ends_at_and_category_id(): void
    {
        [$context, , $ownerInput] = $this->loginFixture();

        $response = $this->getJson(
            self::ROOT.'/vehicles/available?starts_at='.urlencode('2026-09-07T09:00:00+05:30').'&ends_at=&category_id='
        );
        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'registration_number', 'display_label']]]);
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
