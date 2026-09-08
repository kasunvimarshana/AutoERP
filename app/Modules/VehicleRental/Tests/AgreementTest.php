<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Core\Tenancy\TenantFeature;
use Modules\User\Constants\UserGuard;
use Modules\User\Services\UserAccessResolver;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Http\Requests\AgreementRequest;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\Support\CurrencyFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class AgreementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(RentalAuthorization::class, fn ($mock) => $mock->shouldReceive('assert')->zeroOrMoreTimes());
    }

    public function test_commercial_sides_and_unknown_zero_are_independent(): void
    {
        [$context, $customer, $owner] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customer, $owner): void {
            $service = app(AgreementService::class);
            $lessee = $service->create(AgreementKind::Customer, $context, $customer);
            $lessor = $service->create(AgreementKind::Owner, $context, $owner);
            self::assertSame('90.000000', $lessee->terms['excess_km_rate']);
            self::assertSame('60.000000', $lessor->terms['excess_km_rate']);
            self::assertNull($lessee->terms['included_km']);
            self::assertSame('0.000000', $lessor->terms['included_km']);
            self::assertSame($customer['party_id'], (int) $lessee->party->getKey());
            self::assertSame($owner['party_id'], (int) $lessor->party->getKey());
            self::assertSame($owner['vehicle_id'], (int) $lessor->vehicle->getKey());
            self::assertSame(1, $lessee->history()->count());
            self::assertSame(1, $lessor->history()->count());
        });
    }

    public function test_stale_edit_conflicts_and_activated_terms_are_immutable(): void
    {
        [$context, $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $s = app(AgreementService::class);
            $record = $s->create(AgreementKind::Customer, $context, $input);
            $updated = $s->change(AgreementKind::Customer, $context, $record->id, 1, AgreementAction::Update, array_replace($input, ['terms' => ['excess_km_rate' => '95']]));
            self::assertSame(2, $updated->row_version);
            try {
                $s->change(AgreementKind::Customer, $context, $record->id, 1, AgreementAction::Update, $input);
                self::fail('Stale edit must conflict.');
            } catch (ConflictHttpException) {
                self::assertSame(2, $record->history()->count());
            }
            $active = $s->change(AgreementKind::Customer, $context, $record->id, 2, AgreementAction::Activate);
            self::assertSame(AgreementStatus::Active, $active->status);
            try {
                $s->change(AgreementKind::Customer, $context, $record->id, 3, AgreementAction::Update, $input);
                self::fail('Activated terms must be immutable.');
            } catch (ValidationException) {
                self::assertSame('95.000000', $record->refresh()->terms['excess_km_rate']);
            }
            $closed = $s->change(AgreementKind::Customer, $context, $record->id, 3, AgreementAction::Close, reason: 'Agreement ended');
            self::assertSame(AgreementStatus::Closed, $closed->status);
            self::assertSame(4, $closed->history()->count());
            self::assertSame('90.000000', $closed->history()->first()->snapshot['terms']['excess_km_rate']);
        });
    }

    public function test_invalid_structured_dates_and_amounts_write_nothing(): void
    {
        [$context, $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            foreach ([['starts_on' => '2026-09-31'], ['ends_on' => '2026-08-01'], ['terms' => ['base_rate' => -1]], ['terms' => ['invented_rate' => '10']], ['terms' => ['base_rate' => '1e3']]] as $invalid) {
                try {
                    app(AgreementService::class)->create(AgreementKind::Customer, $context, array_replace($input, $invalid));
                    self::fail('Invalid facts must not be persisted.');
                } catch (ValidationException) {
                    $this->assertDatabaseCount('vehicle_rental_customer_agreements', 0);
                }
            }
        });
    }

    public function test_tenant_party_and_organization_boundaries(): void
    {
        [$context, $input] = $this->fixture();
        [, $foreign] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input, $foreign): void {
            try {
                app(AgreementService::class)->create(AgreementKind::Customer, $context, array_replace($input, ['party_id' => $foreign['party_id']]));
                self::fail('Foreign tenant party must be rejected.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('vehicle_rental_customer_agreements', 0);
            }
            $record = app(AgreementService::class)->create(AgreementKind::Customer, $context, $input);
            $branch = OrganizationUnitFixture::create(['tenant_id' => $context->tenantId, 'code' => 'SECOND', 'name' => 'Second branch']);
            $this->expectException(ModelNotFoundException::class);
            app(AgreementService::class)->find(AgreementKind::Customer, new AgreementContext($context->tenantId, $branch, $context->actorId), $record->id);
        });
    }

    public function test_duplicate_reference_rolls_back_without_extra_history(): void
    {
        [$context, $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $s = app(AgreementService::class);
            $s->create(AgreementKind::Customer, $context, $input);
            try {
                $s->create(AgreementKind::Customer, $context, $input);
                self::fail('Duplicate reference must fail.');
            } catch (ConflictHttpException) {
                $this->assertDatabaseCount('vehicle_rental_customer_agreements', 1);
                $this->assertDatabaseCount('vehicle_rental_customer_agreements_history', 1);
            }
        });
    }

    public function test_history_and_canonical_labels_survive_party_rename(): void
    {
        [$context, $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $record = app(AgreementService::class)->create(AgreementKind::Customer, $context, $input);
            $original = $record->party_name_snapshot;
            DB::table('customers')->where('id', $input['party_id'])->update(['name' => 'Changed name']);
            self::assertSame($original, $record->refresh()->party_name_snapshot);
            self::assertSame($original, $record->history()->first()->snapshot['party_name_snapshot']);
            $this->expectException(\LogicException::class);
            $record->history()->first()->delete();
        });
    }

    public function test_actual_permission_mapping_rejects_owner_write_with_only_customer_permission(): void
    {
        [$context] = $this->fixture();
        $permission = DB::table('permissions')->insertGetId(['tenant_id' => $context->tenantId, 'name' => RentalAuthorization::CUSTOMER_MANAGE, 'guard_name' => UserGuard::TENANT_API, 'module' => TenantFeature::VEHICLE_RENTAL, 'is_active' => true]);
        DB::table('user_permissions')->insert(['tenant_id' => $context->tenantId, 'user_id' => $context->actorId, 'permission_id' => $permission]);
        $authorization = new RentalAuthorization(app(UserAccessResolver::class));
        $authorization->assert($context, AgreementKind::Customer, true);
        $this->expectException(AuthorizationException::class);
        $authorization->assert($context, AgreementKind::Owner, true);
    }

    public function test_controller_uses_trusted_context_and_requires_versions(): void
    {
        [$context, $input] = $this->fixture();
        $this->withoutMiddleware();
        $this->app->resolving(AgreementRequest::class, function ($request) use ($context): void {
            $request->attributes->set(config('core.current_tenant.id_attribute', 'current_tenant_id'), $context->tenantId);
            $request->attributes->set(config('core.current_organization_unit.id_attribute', 'current_organization_unit_id'), $context->organizationUnitId);
            $request->attributes->set(config('core.current_user.id_attribute', 'current_user_id'), $context->actorId);
        });
        $path = '/api/v1/vehicle-rental/customer/agreements';
        $response = $this->tenantPostJson($context->tenantId, $path, array_merge($input, ['tenant_id' => -1, 'organization_unit_id' => -1]));
        $response->assertCreated()->assertJsonPath('data.reference', $input['reference'])->assertJsonPath('data.row_version', 1);
        $id = $response->json('data.id');
        $this->assertDatabaseHas('vehicle_rental_customer_agreements', ['id' => $id, 'tenant_id' => $context->tenantId, 'organization_unit_id' => $context->organizationUnitId]);
        $this->tenantPostJson($context->tenantId, $path.'/'.$id.'/activate')->assertUnprocessable()->assertJsonValidationErrors('expected_version');
        $this->tenantPostJson($context->tenantId, $path.'/'.$id.'/activate', ['expected_version' => 1])->assertOk()->assertJsonPath('data.status', AgreementStatus::Active->value);
        $this->tenantPostJson($context->tenantId, $path.'/'.$id.'/close', ['expected_version' => 1, 'reason' => 'Ended'])->assertConflict();
        $this->tenantGetJson($context->tenantId, $path.'/'.$id.'/history')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.actor.name', 'Test User')->assertJsonPath('data.0.terms.excess_km_rate', '90.000000');
        $this->tenantPostJson($context->tenantId, $path, array_merge($input, ['status' => AgreementStatus::Active->value]))->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_routes_reject_unauthenticated_access(): void
    {
        $this->getJson('/api/v1/vehicle-rental/customer/agreements')->assertUnauthorized();
    }

    private function fixture(): array
    {
        $code = Str::upper(Str::random(10));
        $currency = CurrencyFixture::create(['name' => 'Rental '.$code, 'symbol' => 'R']);
        $tenant = DB::table('tenants')->insertGetId(['uuid' => (string) Str::uuid(), 'code' => $code, 'name' => $code, 'slug' => strtolower($code), 'status' => 'active', 'status_changed_at' => now(), 'base_currency_id' => $currency, 'created_at' => now(), 'updated_at' => now()]);
        $org = OrganizationUnitFixture::create(['tenant_id' => $tenant, 'code' => $code, 'name' => 'Rental '.$code]);
        $actor = TenantUserFixture::create(['tenant_id' => $tenant, 'email' => strtolower($code).'@example.test']);
        $customer = DB::table('customers')->insertGetId(['tenant_id' => $tenant, 'customer_number' => $code, 'code' => $code, 'name' => 'Customer '.$code, 'customer_type' => 'individual', 'status' => 'active']);
        $supplier = DB::table('suppliers')->insertGetId(['tenant_id' => $tenant, 'supplier_number' => $code, 'code' => $code, 'name' => 'Owner '.$code, 'supplier_type' => 'individual', 'status' => 'active']);
        $vehicle = DB::table('vehicles')->insertGetId(['tenant_id' => $tenant, 'vehicle_number' => $code, 'registration_number' => $code, 'status' => 'active']);
        $input = ['reference' => 'AG-'.$code, 'party_id' => $customer, 'currency_id' => $currency, 'agreed_on' => '2026-09-01', 'starts_on' => '2026-09-07', 'ends_on' => null, 'basis' => 'monthly', 'driver_mode' => 'self_drive', 'terms' => ['excess_km_rate' => '90']];

        return [new AgreementContext($tenant, $org, $actor), $input, array_replace($input, ['party_id' => $supplier, 'vehicle_id' => $vehicle, 'terms' => ['excess_km_rate' => '60', 'included_km' => '0']])];
    }
}
