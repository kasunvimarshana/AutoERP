<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Core\Tenancy\TenantFeature;
use Modules\User\Constants\UserGuard;
use Modules\VehicleRental\Enums\DriverMode;
use Modules\VehicleRental\Enums\RentalBasis;
use Modules\VehicleRental\Enums\RentalReservationStatus;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RentalReservationService;
use Tests\TestCase;

final class RentalReservationTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    private function grantPermissions(int $tenantId, int $userId, array $permissions): void
    {
        foreach ($permissions as $name) {
            $permissionId = DB::table('permissions')->insertGetId([
                'tenant_id' => $tenantId,
                'name' => $name,
                'guard_name' => UserGuard::TENANT_API,
                'module' => TenantFeature::VEHICLE_RENTAL,
                'is_active' => true,
            ]);
            DB::table('user_permissions')->insert([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    public function test_reservation_lifecycle_and_conversion(): void
    {
        [$context, $customerData] = $this->fixture();
        $this->grantPermissions($context->tenantId, $context->actorId, [
            RentalAuthorization::CUSTOMER_MANAGE,
            RentalAuthorization::CUSTOMER_VIEW,
            RentalAuthorization::RESERVATION_MANAGE,
            RentalAuthorization::RESERVATION_VIEW,
        ]);

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData): void {
            $service = app(RentalReservationService::class);

            // 1. Create Draft Reservation
            $reservation = $service->create([
                'customer_id' => $customerData['party_id'],
                'requested_vehicle_id' => null,
                'rental_mode' => DriverMode::SelfDrive->value,
                'billing_cycle' => RentalBasis::Daily->value,
                'requested_start_at' => '2026-11-10T10:00:00+05:30',
                'requested_end_at' => '2026-11-15T18:00:00+05:30',
                'currency_id' => $customerData['currency_id'],
                'estimated_amount' => '25000.000000',
                'estimated_deposit_amount' => '10000.000000',
                'remarks' => 'Airport pickup inquiry',
            ], $context->tenantId, $context->organizationUnitId, $context->actorId);

            $this->assertSame(RentalReservationStatus::Draft, $reservation->status);
            $this->assertSame('25000.000000', (string) $reservation->estimated_amount);
            $this->assertCount(1, $reservation->history);

            // 2. Update Draft
            $updated = $service->update($reservation, [
                'estimated_amount' => '30000.000000',
            ], $reservation->row_version, $context->actorId);

            $this->assertSame('30000.000000', (string) $updated->estimated_amount);
            $this->assertSame(2, $updated->row_version);

            // 3. Transition to Confirmed
            $confirmed = $service->transition($updated, RentalReservationStatus::Confirmed, $updated->row_version, $context->actorId, 'Customer confirmed booking');
            $this->assertSame(RentalReservationStatus::Confirmed, $confirmed->status);
            $this->assertNotNull($confirmed->confirmed_at);

            // 4. Convert to Customer Agreement
            $agreement = $service->convertToAgreement($confirmed, [
                'reference' => 'CA-'.Str::upper(Str::random(6)),
            ], $confirmed->row_version, $context);

            $this->assertNotNull($agreement);
            $this->assertSame($customerData['party_id'], $agreement->customer_id);

            $refreshed = $confirmed->refresh();
            $this->assertSame(RentalReservationStatus::Converted, $refreshed->status);
            $this->assertSame($agreement->id, $refreshed->converted_agreement_id);
        });
    }

    public function test_reservation_cancellation_and_invalid_transition(): void
    {
        [$context, $customerData] = $this->fixture();

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData): void {
            $service = app(RentalReservationService::class);

            $reservation = $service->create([
                'customer_id' => $customerData['party_id'],
                'rental_mode' => DriverMode::SelfDrive->value,
                'billing_cycle' => RentalBasis::Daily->value,
                'requested_start_at' => '2026-11-20T10:00:00+05:30',
                'requested_end_at' => '2026-11-22T18:00:00+05:30',
                'currency_id' => $customerData['currency_id'],
                'estimated_amount' => '15000.000000',
            ], $context->tenantId, $context->organizationUnitId, $context->actorId);

            $cancelled = $service->transition($reservation, RentalReservationStatus::Cancelled, $reservation->row_version, $context->actorId, 'Cancelled by customer');
            $this->assertSame(RentalReservationStatus::Cancelled, $cancelled->status);

            $this->expectException(InvalidArgumentException::class);
            $service->transition($cancelled, RentalReservationStatus::Confirmed, $cancelled->row_version, $context->actorId);
        });
    }
}
