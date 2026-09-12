<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Core\Tenancy\TenantFeature;
use Modules\User\Constants\UserGuard;
use Modules\User\Constants\UserOrganizationUnitStatus;
use Modules\Vehicle\Enums\VehicleOwnershipType;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\BaseRentPolicy;
use Modules\VehicleRental\Services\RentalAuthorization;
use Tests\Support\ActiveTenantSubscriptionFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantAuthenticationFixture;
use Tests\TestCase;

/** Real Auth login and all middleware; no mocked authorization or injected request context. */
final class AuthenticatedRentalJourneyTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    private const ROOT = '/api/v1/vehicle-rental';

    private const FIXTURE_PASSWORD = 'secret-password';

    public function test_authenticated_base_billing_requires_its_permission_and_invoice_entitlement(): void
    {
        $this->postJson(self::ROOT.'/customer/agreements/1/base-charges', [])->assertUnauthorized();
        [, $customerInput] = $this->loginFixture(modules: [TenantFeature::VEHICLE_RENTAL, TenantFeature::INVOICE]);
        $customerInput['terms']['base_rate'] = '3000';
        $customer = $this->activate('customer', $customerInput);
        $url = self::ROOT.'/customer/agreements/'.$customer['id'].'/base-charges';
        $input = ['policy' => BaseRentPolicy::ActualCalendarDays->value, 'expected_version' => $customer['row_version'],
            'from' => '2026-09-07', 'until' => '2026-10-06', 'invoice_date' => '2026-09-12', 'exchange_rate' => '1'];
        $this->postJson($url, $input)->assertCreated()->assertJsonPath('data.grand_total', '3000.000000');
        $this->getJson($url)->assertOk()->assertJsonPath('data.0.invoices.0.status', 'draft')->assertJsonMissingPath('data.0.tenant_id');
        $this->postJson($url, $input)->assertConflict();
        $this->postJson($url, array_replace($input, ['expected_version' => 1]))->assertConflict();
        $this->loginFixture(permissions: [RentalAuthorization::CUSTOMER_VIEW], modules: [TenantFeature::VEHICLE_RENTAL, TenantFeature::INVOICE]);
        $this->postJson($url, $input)->assertForbidden();
        $this->loginFixture(modules: [TenantFeature::VEHICLE_RENTAL, TenantFeature::INVOICE]);
        $this->postJson($url, $input)->assertNotFound();
        $this->loginFixture();
        $this->postJson($url, $input)->assertForbidden();
        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_authenticated_base_rent_preview_enforces_scope_policy_and_revision_without_writes(): void
    {
        $this->postJson(self::ROOT.'/customer/agreements/1/base-rent-preview', [])->assertUnauthorized();
        [, $customerInput, $ownerInput] = $this->loginFixture();
        $customerInput['terms']['base_rate'] = '3000';
        $customer = $this->activate('customer', $customerInput);
        $url = self::ROOT.'/customer/agreements/'.$customer['id'].'/base-rent-preview';
        $input = ['policy' => BaseRentPolicy::ActualCalendarDays->value, 'expected_version' => $customer['row_version'], 'from' => '2026-09-07', 'until' => '2026-10-06'];
        $this->postJson($url, $input)->assertOk()->assertJsonPath('data.base_rent', '3000.000000')->assertJsonPath('data.segments.0.denominator_days', 30);
        $this->postJson($url, array_replace($input, ['policy' => 'unverified']))->assertUnprocessable();
        $this->postJson($url, array_replace($input, ['expected_version' => 1]))->assertConflict();
        $owner = $this->activate('owner', $ownerInput);
        $this->postJson(self::ROOT.'/owner/agreements/'.$owner['id'].'/base-rent-preview', $input)->assertUnprocessable();
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('vehicle_rental_customer_agreements_history', 2);
        $this->loginFixture(permissions: [RentalAuthorization::OWNER_VIEW]);
        $this->postJson($url, $input)->assertForbidden();
        $this->loginFixture();
        $this->postJson($url, $input)->assertNotFound();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-09-10T12:00:00Z'));
    }

    public function test_authenticated_owner_supply_chart_correction_return_and_closure_journey(): void
    {
        [$context, $customerInput, $ownerInput] = $this->loginFixture();
        $customerInput['executing_on'] = '2026-09-03';
        $customer = $this->activate('customer', $customerInput);
        self::assertSame('2026-09-03', $customer['executing_on']);
        $owner = $this->activate('owner', $ownerInput);
        $customerPath = self::ROOT.'/customer/agreements/'.$customer['id'];
        $this->getJson(self::ROOT.'/vehicles/'.$ownerInput['vehicle_id'].'/sources?'.http_build_query(['starts_at' => '2026-09-07T09:00:00+05:30', 'ends_at' => '']))->assertOk()->assertJsonPath('data.0.id', $owner['id']);
        $plan = ['vehicle_id' => $ownerInput['vehicle_id'], 'owner_agreement_id' => $owner['id'],
            'starts_at' => '2026-09-07T09:00:00+05:30', 'ends_at' => '2026-09-08T09:00:00+05:30', 'expected_version' => $customer['row_version']];
        $use = $this->postJson($customerPath.'/vehicles', $plan)->assertCreated()->assertJsonPath('data.owner_agreement.reference', $owner['reference'])->json('data');
        $usePath = self::ROOT.'/vehicle-uses/'.$use['id'];
        $this->postJson($customerPath.'/close', ['expected_version' => $customer['row_version'], 'reason' => 'Still reserved'])->assertUnprocessable();
        $use = $this->postJson($usePath.'/handover', ['expected_version' => $use['row_version'], 'occurred_at' => $plan['starts_at'], 'odometer' => '100', 'reason' => 'Customer collected'])->assertOk()->assertJsonPath('data.status', 'in_custody')->json('data');
        $facts = ['reference' => 'AUTH-CHART', 'starts_at' => '2026-09-07T09:00:15+05:30', 'ends_at' => '2026-09-07T17:00:30+05:30', 'start_odometer' => '100', 'end_odometer' => '150.25', 'garage_km' => '0', 'expected_version' => $use['row_version']];
        $chart = $this->postJson($usePath.'/running-charts', $facts)->assertCreated()->assertJsonPath('data.total_km', '50.250000')->assertJsonPath('data.commercial_km', null)->json('data');
        $chartPath = self::ROOT.'/running-charts/'.$chart['id'];
        $draftVersion = $chart['row_version'];
        $chart = $this->postJson($chartPath.'/finalize', ['expected_version' => $draftVersion])->assertOk()->assertJsonPath('data.status', 'finalized')->json('data');
        $this->postJson($chartPath.'/reverse', ['expected_version' => $draftVersion, 'reason' => 'Stale reversal'])->assertConflict();
        $this->putJson($chartPath, array_replace($facts, ['expected_version' => $chart['row_version'], 'end_odometer' => '999']))->assertUnprocessable();
        $this->postJson($chartPath.'/reverse', ['expected_version' => $chart['row_version'], 'reason' => 'Correct signed reading'])->assertOk()->assertJsonPath('data.status', 'reversed');
        $correction = $this->postJson($usePath.'/running-charts', array_replace($facts, ['reference' => 'AUTH-CORRECTION', 'corrects_chart_id' => $chart['id'], 'end_odometer' => '155']))->assertCreated()->assertJsonPath('data.corrects_chart.reference', 'AUTH-CHART')->json('data');
        $this->postJson(self::ROOT.'/running-charts/'.$correction['id'].'/finalize', ['expected_version' => $correction['row_version']])->assertOk();
        $this->getJson(self::ROOT.'/running-charts?chart_status=finalized')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.reference', 'AUTH-CORRECTION');
        $this->postJson($usePath.'/return', ['expected_version' => $use['row_version'], 'occurred_at' => $plan['ends_at'], 'odometer' => '155', 'reason' => 'Vehicle inspected and returned'])->assertOk()->assertJsonPath('data.status', 'returned');
        $this->postJson($customerPath.'/close', ['expected_version' => $customer['row_version'], 'reason' => 'Completed'])->assertOk()->assertJsonPath('data.status', 'closed');
        $this->postJson(self::ROOT.'/owner/agreements/'.$owner['id'].'/close', ['expected_version' => $owner['row_version'], 'reason' => 'Supply complete'])->assertOk()->assertJsonPath('data.status', 'closed');
        $this->getJson($chartPath.'/history')->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('data.0.actor.name', 'Test User');
        $this->getJson($usePath.'/history')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson(self::ROOT.'/vehicle-uses?use_status=returned')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.customer_agreement.reference', $customerInput['reference']);
        $this->assertDatabaseHas('vehicle_rental_running_charts', ['id' => $chart['id'], 'end_odometer' => '150.25', 'status' => 'reversed']);
        $this->assertDatabaseHas('vehicles', ['id' => $ownerInput['vehicle_id'], 'tenant_id' => $context->tenantId, 'status' => 'active']);
    }

    public function test_authentication_permissions_and_feature_gates_prevent_writes(): void
    {
        $this->getJson(self::ROOT.'/running-charts')->assertUnauthorized();
        $this->getJson(self::ROOT.'/vehicle-uses')->assertUnauthorized();
        [, $input] = $this->loginFixture(permissions: []);
        $this->getJson(self::ROOT.'/vehicle-uses')->assertForbidden();
        $this->getJson(self::ROOT.'/customer/agreements')->assertForbidden();
        $this->postJson(self::ROOT.'/customer/agreements', $input)->assertForbidden();
        $this->assertDatabaseCount('vehicle_rental_customer_agreements', 0);

        [, $input] = $this->loginFixture(modules: []);
        $this->getJson(self::ROOT.'/vehicle-uses')->assertForbidden();
        $this->postJson(self::ROOT.'/customer/agreements', $input)->assertForbidden();
        $this->getJson(self::ROOT.'/running-charts')->assertForbidden();
        $this->assertDatabaseCount('vehicle_rental_customer_agreements', 0);
    }

    public function test_use_view_alone_allows_register_but_not_agreements_or_writes(): void
    {
        [, $input] = $this->loginFixture(permissions: [RentalAuthorization::USE_VIEW]);
        $this->getJson(self::ROOT.'/vehicle-uses')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson(self::ROOT.'/vehicle-uses?use_status=invented')->assertUnprocessable()->assertJsonValidationErrors('use_status');
        $this->getJson(self::ROOT.'/customer/agreements')->assertForbidden();
        $this->postJson(self::ROOT.'/customer/agreements', $input)->assertForbidden();
    }

    public function test_foreign_ids_and_payload_context_cannot_change_the_authenticated_scope(): void
    {
        [$first, $firstInput, $firstOwner] = $this->loginFixture();
        $foreign = $this->activate('customer', $firstInput);
        [$second, $secondInput] = $this->loginFixture();
        $this->getJson(self::ROOT.'/customer/agreements/'.$foreign['id'])->assertNotFound();
        $this->getJson(self::ROOT.'/customer/agreements/'.$foreign['id'].'/history')->assertNotFound();
        $this->postJson(self::ROOT.'/customer/agreements/'.$foreign['id'].'/vehicles', ['expected_version' => $foreign['row_version'], 'vehicle_id' => $firstOwner['vehicle_id'], 'starts_at' => '2026-09-07T09:00:00+00:00', 'ends_at' => null])->assertNotFound();
        $created = $this->postJson(self::ROOT.'/customer/agreements', array_replace($secondInput, ['tenant_id' => $first->tenantId, 'organization_unit_id' => $first->organizationUnitId]))->assertCreated()->json('data');
        $this->assertDatabaseHas('vehicle_rental_customer_agreements', ['id' => $created['id'], 'tenant_id' => $second->tenantId, 'organization_unit_id' => $second->organizationUnitId]);
        $this->getJson(self::ROOT.'/customer/agreements')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.reference', $secondInput['reference']);
    }

    public function test_customer_and_chart_read_permissions_do_not_grant_other_actions(): void
    {
        [, $customer, $owner] = $this->loginFixture(permissions: [RentalAuthorization::CUSTOMER_MANAGE, RentalAuthorization::CHART_VIEW]);
        $this->postJson(self::ROOT.'/customer/agreements', $customer)->assertCreated();
        $this->getJson(self::ROOT.'/customer/agreements')->assertForbidden();
        $this->postJson(self::ROOT.'/owner/agreements', $owner)->assertForbidden();
        $this->getJson(self::ROOT.'/running-charts')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson(self::ROOT.'/vehicle-uses')->assertForbidden();
        $unknownChartId = 999999;
        foreach (['finalize', 'reverse'] as $action) {
            $this->postJson(self::ROOT.'/running-charts/'.$unknownChartId.'/'.$action, ['expected_version' => 1, 'reason' => 'Unauthorized'])->assertForbidden();
        }
        $this->assertDatabaseCount('vehicle_rental_owner_agreements', 0);
        $this->assertDatabaseCount('vehicle_rental_running_chart_history', 0);
    }

    public function test_authenticated_company_replacement_rolls_back_failed_source_and_preserves_lineage(): void
    {
        [$context, $customerInput, $ownerInput] = $this->loginFixture();
        $customer = $this->activate('customer', $customerInput);
        $owner = $this->activate('owner', $ownerInput);
        $start = '2026-09-07T09:00:00+05:30';
        $end = '2026-09-08T09:00:00+05:30';
        $use = $this->postJson(self::ROOT.'/customer/agreements/'.$customer['id'].'/vehicles', ['expected_version' => $customer['row_version'], 'vehicle_id' => $ownerInput['vehicle_id'], 'owner_agreement_id' => $owner['id'], 'starts_at' => $start, 'ends_at' => $end])->assertCreated()->json('data');
        $path = self::ROOT.'/vehicle-uses/'.$use['id'];
        $use = $this->postJson($path.'/handover', ['expected_version' => $use['row_version'], 'occurred_at' => $start, 'reason' => 'Collected'])->assertOk()->json('data');
        $replacementVehicle = DB::table('vehicles')->insertGetId(['tenant_id' => $context->tenantId, 'vehicle_number' => 'AUTH-COMPANY', 'registration_number' => 'AUTH-COMPANY', 'status' => 'active']);
        $exchange = ['expected_version' => $use['row_version'], 'vehicle_id' => $replacementVehicle, 'owner_agreement_id' => null, 'starts_at' => '2026-09-07T12:00:00+05:30', 'ends_at' => $end, 'reason' => 'Actual vehicle exchange'];
        $this->postJson($path.'/replace', $exchange)->assertUnprocessable();
        $this->assertDatabaseHas('vehicle_rental_uses', ['id' => $use['id'], 'row_version' => $use['row_version'], 'status' => 'in_custody']);
        $this->getJson($path.'/history')->assertOk()->assertJsonCount(2, 'data');
        // Canonical company ownership is a Vehicle-owned fixture, never inferred from a missing owner agreement.
        DB::table('vehicle_ownerships')->insert(['tenant_id' => $context->tenantId, 'vehicle_id' => $replacementVehicle, 'owner_type' => VehicleOwnerType::Company->value, 'owner_key' => 'company', 'owner_code_snapshot' => 'COMPANY', 'owner_name_snapshot' => 'Company', 'ownership_type' => VehicleOwnershipType::CompanyOwned->value, 'started_at' => '2026-09-01 00:00:00']);
        $next = $this->postJson($path.'/replace', $exchange)->assertOk()->assertJsonPath('data.status', 'in_custody')->assertJsonPath('data.owner_agreement', null)->assertJsonPath('data.replaces_use.id', $use['id'])->assertJsonPath('data.replaces_use.vehicle_label', $use['vehicle']['label'])->json('data');
        $this->assertDatabaseHas('vehicle_rental_uses', ['id' => $use['id'], 'status' => 'returned', 'vehicle_id' => $ownerInput['vehicle_id']]);
        $this->postJson($path.'/replace', $exchange)->assertConflict();
        $this->postJson(self::ROOT.'/vehicle-uses/'.$next['id'].'/return', ['expected_version' => $next['row_version'], 'occurred_at' => $end, 'reason' => 'Replacement returned'])->assertOk();
        $this->assertDatabaseCount('vehicle_rental_uses', 2);
    }

    public function test_authenticated_branch_session_isolates_agreements_and_rechecks_membership(): void
    {
        [$context, $input] = $this->loginFixture();
        $first = $this->activate('customer', $input);
        $branch = OrganizationUnitFixture::create(['tenant_id' => $context->tenantId, 'code' => 'SECOND', 'name' => 'Second branch']);
        DB::table('user_organization_units')->insert(['tenant_id' => $context->tenantId, 'organization_unit_id' => $branch, 'user_id' => $context->actorId, 'status' => UserOrganizationUnitStatus::ACTIVE, 'is_default' => false, 'default_marker' => null, 'row_version' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $email = DB::table('users')->where('id', $context->actorId)->value('email');
        $this->flushHeaders();
        $token = $this->withHeader('X-Tenant-Id', (string) $context->tenantId)->postJson('/api/v1/auth/login', ['organization_unit_id' => $branch, 'identifier' => $email, 'password' => self::FIXTURE_PASSWORD])->assertOk()->json('token');
        $this->withToken($token);
        $this->getJson(self::ROOT.'/customer/agreements')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson(self::ROOT.'/customer/agreements/'.$first['id'])->assertNotFound();
        $created = $this->postJson(self::ROOT.'/customer/agreements', array_replace($input, ['reference' => 'SECOND-BRANCH', 'organization_unit_id' => $context->organizationUnitId]))->assertCreated()->json('data');
        $this->assertDatabaseHas('vehicle_rental_customer_agreements', ['id' => $created['id'], 'organization_unit_id' => $branch]);
        DB::table('user_organization_units')->where('user_id', $context->actorId)->where('organization_unit_id', $branch)->update(['status' => UserOrganizationUnitStatus::INACTIVE]);
        $this->getJson(self::ROOT.'/customer/agreements')->assertForbidden();
    }

    private function activate(string $kind, array $input): array
    {
        $path = self::ROOT.'/'.$kind.'/agreements';
        $draft = $this->postJson($path, $input)->assertCreated()->json('data');

        return $this->postJson($path.'/'.$draft['id'].'/activate', ['expected_version' => $draft['row_version']])->assertOk()->json('data');
    }

    private function loginFixture(?array $permissions = null, ?array $modules = null): array
    {
        [$context, $customer, $owner] = $this->fixture();
        ActiveTenantSubscriptionFixture::create($context->tenantId, $modules ?? [TenantFeature::VEHICLE_RENTAL]);
        DB::table('user_organization_units')->insert(['tenant_id' => $context->tenantId, 'organization_unit_id' => $context->organizationUnitId, 'user_id' => $context->actorId, 'status' => UserOrganizationUnitStatus::ACTIVE, 'is_default' => true, 'default_marker' => UserOrganizationUnitStatus::DEFAULT_MARKER, 'row_version' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $email = DB::table('users')->where('id', $context->actorId)->value('email');
        TenantAuthenticationFixture::provision($context->tenantId, $context->actorId, $email);
        $this->grant($context, $permissions ?? array_keys(RentalAuthorization::descriptions()));
        $this->flushHeaders();
        $token = $this->withHeader('X-Tenant-Id', (string) $context->tenantId)->postJson('/api/v1/auth/login', ['organization_unit_id' => $context->organizationUnitId, 'identifier' => $email, 'password' => self::FIXTURE_PASSWORD])->assertOk()->json('token');
        $this->withToken($token);

        return [$context, $customer, $owner];
    }

    private function grant(AgreementContext $context, array $permissions): void
    {
        foreach ($permissions as $name) {
            $permission = DB::table('permissions')->insertGetId(['tenant_id' => $context->tenantId, 'name' => $name, 'guard_name' => UserGuard::TENANT_API, 'module' => 'VehicleRental', 'description' => RentalAuthorization::descriptions()[$name], 'row_version' => 1, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('user_permissions')->insert(['tenant_id' => $context->tenantId, 'user_id' => $context->actorId, 'permission_id' => $permission, 'row_version' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
