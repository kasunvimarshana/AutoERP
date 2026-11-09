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
                'customer_id' => $customerData['party_id'],
            ], $reservation->row_version, $context->actorId);

            $this->assertSame('30000.000000', (string) $updated->estimated_amount);
            $this->assertSame(2, $updated->row_version);
            $this->assertNotEmpty($updated->customer_name_snapshot);
            $this->assertNotEmpty($updated->currency_code_snapshot);

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

    public function test_reservation_api_endpoints(): void
    {
        [$context, $customerData] = $this->loginFixture();

        // 1. Index should return 200 without requiring POST fields
        $this->getJson($this->rentalRoot.'/reservations')
            ->assertOk()
            ->assertJsonPath('data', []);

        // 2. Store Draft Reservation
        $createResponse = $this->postJson($this->rentalRoot.'/reservations', [
            'customer_id' => $customerData['party_id'],
            'rental_mode' => DriverMode::SelfDrive->value,
            'billing_cycle' => RentalBasis::Daily->value,
            'requested_start_at' => '2026-11-25T10:00:00+05:30',
            'requested_end_at' => '2026-11-28T18:00:00+05:30',
            'currency_id' => $customerData['currency_id'],
            'estimated_amount' => '18000.000000',
            'estimated_deposit_amount' => '5000.000000',
            'remarks' => 'API Reservation test',
        ])->assertCreated();

        $reservationId = $createResponse->json('data.id');
        $version = $createResponse->json('data.row_version');

        // 3. Show Reservation should return 200
        $this->getJson($this->rentalRoot.'/reservations/'.$reservationId)
            ->assertOk()
            ->assertJsonPath('data.id', $reservationId)
            ->assertJsonPath('data.status', 'draft');

        // 4. Update Reservation
        $updateResponse = $this->putJson($this->rentalRoot.'/reservations/'.$reservationId, [
            'expected_version' => $version,
            'estimated_amount' => '20000.000000',
        ])->assertOk();

        $version = $updateResponse->json('data.row_version');

        // 5. Transition to Confirmed
        $transitionResponse = $this->patchJson($this->rentalRoot.'/reservations/'.$reservationId.'/transition', [
            'expected_version' => $version,
            'status' => RentalReservationStatus::Confirmed->value,
            'reason' => 'Customer confirmed',
        ])->assertOk();

        $version = $transitionResponse->json('data.row_version');

        // 6. Convert to Agreement
        $convertResponse = $this->postJson($this->rentalRoot.'/reservations/'.$reservationId.'/convert', [
            'expected_version' => $version,
        ])->assertCreated();

        $this->assertNotNull($convertResponse->json('data.id'));

        // 7. Verify reservation status is converted
        $this->getJson($this->rentalRoot.'/reservations/'.$reservationId)
            ->assertOk()
            ->assertJsonPath('data.status', 'converted');
    }

    public function test_reservation_vehicle_availability_and_conflict_detection(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData): void {
            $service = app(RentalReservationService::class);
            $vehicleId = (int) $ownerData['vehicle_id'];

            // 1. Create and confirm a reservation for vehicle
            $reservation1 = $service->create([
                'customer_id' => $customerData['party_id'],
                'requested_vehicle_id' => $vehicleId,
                'rental_mode' => DriverMode::SelfDrive->value,
                'billing_cycle' => RentalBasis::Daily->value,
                'requested_start_at' => '2026-12-01T10:00:00+05:30',
                'requested_end_at' => '2026-12-05T18:00:00+05:30',
                'currency_id' => $customerData['currency_id'],
                'estimated_amount' => '20000.000000',
            ], $context->tenantId, $context->organizationUnitId, $context->actorId);

            $confirmed1 = $service->transition(
                $reservation1,
                RentalReservationStatus::Confirmed,
                $reservation1->row_version,
                $context->actorId,
                'First booking confirmed'
            );
            $this->assertSame(RentalReservationStatus::Confirmed, $confirmed1->status);

            // 2. Attempting to create an overlapping reservation for the same vehicle must fail with confirmed conflict
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('The vehicle has a conflicting confirmed reservation.');

            $service->create([
                'customer_id' => $customerData['party_id'],
                'requested_vehicle_id' => $vehicleId,
                'rental_mode' => DriverMode::SelfDrive->value,
                'billing_cycle' => RentalBasis::Daily->value,
                'requested_start_at' => '2026-12-03T09:00:00+05:30',
                'requested_end_at' => '2026-12-07T12:00:00+05:30',
                'currency_id' => $customerData['currency_id'],
                'estimated_amount' => '15000.000000',
            ], $context->tenantId, $context->organizationUnitId, $context->actorId);
        });
    }

    public function test_confirmed_reservation_blocks_vehicle_use_dispatch(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData): void {
            $service = app(RentalReservationService::class);
            $vehicleId = (int) $ownerData['vehicle_id'];

            $reservation = $service->create([
                'customer_id' => $customerData['party_id'],
                'requested_vehicle_id' => $vehicleId,
                'rental_mode' => DriverMode::SelfDrive->value,
                'billing_cycle' => RentalBasis::Daily->value,
                'requested_start_at' => '2026-12-10T10:00:00+05:30',
                'requested_end_at' => '2026-12-15T18:00:00+05:30',
                'currency_id' => $customerData['currency_id'],
                'estimated_amount' => '20000.000000',
            ], $context->tenantId, $context->organizationUnitId, $context->actorId);

            $service->transition(
                $reservation,
                RentalReservationStatus::Confirmed,
                $reservation->row_version,
                $context->actorId,
                'Booking confirmed'
            );

            // VehicleUseAvailabilityBlocker should report conflict for the overlapping period
            $blocker = app(\Modules\VehicleRental\Services\VehicleUseAvailabilityBlocker::class);
            $this->assertTrue($blocker->conflicts(
                $context->tenantId,
                $vehicleId,
                '2026-12-12 00:00:00',
                '2026-12-13 00:00:00'
            ));
            $this->assertNotNull($blocker->blockingReason(
                $context->tenantId,
                $context->organizationUnitId,
                $vehicleId,
                '2026-12-12 00:00:00',
                '2026-12-13 00:00:00'
            ));

            // Outside the reservation window should have no conflict
            $this->assertFalse($blocker->conflicts(
                $context->tenantId,
                $vehicleId,
                '2026-12-20 00:00:00',
                '2026-12-25 00:00:00'
            ));
        });
    }

    public function test_reservation_handles_empty_string_optional_fields_and_clearing(): void
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

            // Create with empty string optional fields
            $reservation = $service->create([
                'customer_id' => $customerData['party_id'],
                'requested_vehicle_id' => '',
                'requested_vehicle_category_id' => '',
                'rental_mode' => DriverMode::SelfDrive->value,
                'billing_cycle' => RentalBasis::Daily->value,
                'requested_start_at' => '2026-11-20T10:00:00+05:30',
                'requested_end_at' => '2026-11-25T18:00:00+05:30',
                'currency_id' => $customerData['currency_id'],
                'estimated_amount' => '',
                'estimated_deposit_amount' => '',
                'source' => '',
                'remarks' => '',
            ], $context->tenantId, $context->organizationUnitId, $context->actorId);

            $this->assertNull($reservation->requested_vehicle_id);
            $this->assertNull($reservation->requested_vehicle_category_id);
            $this->assertSame('0.000000', (string) $reservation->estimated_amount);
            $this->assertSame('0.000000', (string) $reservation->estimated_deposit_amount);
            $this->assertNull($reservation->source);
            $this->assertNull($reservation->remarks);

            // Update with populated values
            $populated = $service->update($reservation, [
                'source' => 'walk-in',
                'remarks' => 'Requires baby seat',
                'estimated_amount' => '15000.000000',
            ], $reservation->row_version, $context->actorId);

            $this->assertSame('walk-in', $populated->source);
            $this->assertSame('Requires baby seat', $populated->remarks);
            $this->assertSame('15000.000000', (string) $populated->estimated_amount);

            // Update with empty strings to clear
            $cleared = $service->update($populated, [
                'source' => '',
                'remarks' => '',
                'estimated_amount' => '',
            ], $populated->row_version, $context->actorId);

            $this->assertNull($cleared->source);
            $this->assertNull($cleared->remarks);
            $this->assertSame('0.000000', (string) $cleared->estimated_amount);
        });
    }

    public function test_reservation_http_endpoints_accept_empty_optional_fields_and_query_filters(): void
    {
        [$context, $customerData] = $this->loginFixture();

        // 1. POST reservation with empty optional strings
        $response = $this->postJson($this->rentalRoot . '/reservations', [
            'customer_id' => $customerData['party_id'],
            'requested_vehicle_id' => '',
            'requested_vehicle_category_id' => '',
            'rental_mode' => DriverMode::SelfDrive->value,
            'billing_cycle' => RentalBasis::Daily->value,
            'requested_start_at' => '2026-11-20T10:00:00+05:30',
            'requested_end_at' => '2026-11-25T18:00:00+05:30',
            'currency_id' => $customerData['currency_id'],
            'estimated_amount' => '',
            'estimated_deposit_amount' => '',
            'source' => '',
            'remarks' => '',
        ]);

        $response->assertCreated();
        $reservationId = $response->json('data.id');
        $version = $response->json('data.row_version');
        $this->assertNull($response->json('data.requested_vehicle_id'));
        $this->assertNull($response->json('data.source'));

        // 2. GET index with empty filter query params
        $indexResponse = $this->getJson($this->rentalRoot . '/reservations?search=&customer_id=&requested_vehicle_id=&status=&from=&until=');
        $indexResponse->assertOk();

        // 3. PUT update reservation with empty strings
        $updateResponse = $this->putJson($this->rentalRoot . '/reservations/' . $reservationId, [
            'expected_version' => $version,
            'customer_id' => $customerData['party_id'],
            'rental_mode' => DriverMode::SelfDrive->value,
            'billing_cycle' => RentalBasis::Daily->value,
            'requested_start_at' => '2026-11-20T10:00:00+05:30',
            'requested_end_at' => '2026-11-26T18:00:00+05:30',
            'currency_id' => $customerData['currency_id'],
            'requested_vehicle_id' => '',
            'source' => '',
            'remarks' => '',
            'estimated_amount' => '',
        ]);
        $updateResponse->assertOk();
        $newVersion = $updateResponse->json('data.row_version');

        // 4. Transition to confirmed
        $transResponse = $this->patchJson($this->rentalRoot . '/reservations/' . $reservationId . '/transition', [
            'expected_version' => $newVersion,
            'status' => RentalReservationStatus::Confirmed->value,
            'reason' => 'Confirmed via phone',
        ]);
        $transResponse->assertOk();
        $confirmedVersion = $transResponse->json('data.row_version');

        // 5. Convert to agreement with empty optional reference and notes
        $convertResponse = $this->postJson($this->rentalRoot . '/reservations/' . $reservationId . '/convert', [
            'expected_version' => $confirmedVersion,
            'reference' => '',
            'notes' => '',
        ]);
        $convertResponse->assertCreated();
        $this->assertNotEmpty($convertResponse->json('data.reference'));
    }

    public function test_rental_reservation_delete_is_strictly_prohibited(): void
    {
        [$context, $customerData] = $this->loginFixture();

        $response = $this->postJson($this->rentalRoot . '/reservations', [
            'customer_id' => $customerData['party_id'],
            'rental_mode' => DriverMode::SelfDrive->value,
            'billing_cycle' => RentalBasis::Daily->value,
            'requested_start_at' => '2026-11-20T10:00:00+05:30',
            'requested_end_at' => '2026-11-25T18:00:00+05:30',
            'currency_id' => $customerData['currency_id'],
        ])->assertCreated();

        $reservationId = (int) $response->json('data.id');

        $this->deleteJson($this->rentalRoot . '/reservations/' . $reservationId)->assertStatus(405);

        $this->withTenantExecutionContext($context->tenantId, function () use ($reservationId): void {
            $this->expectException(\LogicException::class);
            $this->expectExceptionMessage('Rental reservations must be preserved for audit history.');
            \Modules\VehicleRental\Models\RentalReservation::query()->findOrFail($reservationId)->delete();
        });
    }
}
