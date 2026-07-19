<?php

declare(strict_types=1);

namespace Tests\Feature\VehicleRental;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Enums\RentalReservationStatus;
use Modules\VehicleRental\Models\RentalReservation;
use Modules\VehicleRental\Services\RentalReservationService;
use Tests\TestCase;

final class RentalReservationConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_cannot_be_confirmed_without_a_specific_vehicle(): void
    {
        $currencyId = $this->createCurrency();
        $tenantId = $this->createTenant($currencyId);
        $customerId = $this->createCustomer($tenantId, $currencyId);

        $reservation = $this->withTenantExecutionContext(
            $tenantId,
            function () use ($tenantId, $customerId, $currencyId): RentalReservation {
                return RentalReservation::query()->create([
                    'tenant_id' => $tenantId,
                    'reservation_number' => 'RR-NO-VEHICLE-001',
                    'customer_id' => $customerId,
                    'requested_vehicle_id' => null,
                    'requested_vehicle_category_id' => null,
                    'rental_mode' => 'with_driver',
                    'billing_cycle' => 'daily',
                    'requested_start_at' => '2026-07-20 08:00:00',
                    'requested_end_at' => '2026-07-21 08:00:00',
                    'currency_id' => $currencyId,
                    'estimated_amount' => '0.000000',
                    'estimated_deposit_amount' => '0.000000',
                    'status' => RentalReservationStatus::Pending->value,
                ])->refresh();
            },
        );

        try {
            $this->withTenantExecutionContext(
                $tenantId,
                fn () => app(RentalReservationService::class)->transition(
                    $reservation,
                    RentalReservationStatus::Confirmed,
                    (int) $reservation->row_version,
                ),
            );
            self::fail('A reservation without a specific vehicle must not be confirmed.');
        } catch (ValidationException $exception) {
            self::assertSame(
                'Select a specific available vehicle before confirming the reservation.',
                $exception->errors()['requested_vehicle_id'][0] ?? null,
            );
        }

        $reservation = $this->withTenantExecutionContext(
            $tenantId,
            fn (): RentalReservation => $reservation->refresh(),
        );

        self::assertSame(RentalReservationStatus::Pending, $reservation->status);
        self::assertNull($reservation->confirmed_at);
    }

    private function createCurrency(): int
    {
        return (int) DB::table('currencies')->insertGetId([
            'code' => 'RRC',
            'name' => 'Rental Reservation Currency',
            'symbol' => 'RRC',
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
            'code' => 'RR-CONFIRM',
            'name' => 'Rental Reservation Confirmation Tenant',
            'slug' => 'rental-reservation-confirmation-tenant',
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
            'customer_number' => 'CUS-RR-001',
            'code' => 'CUS-RR',
            'name' => 'Rental Reservation Customer',
            'customer_type' => 'company',
            'status' => 'active',
            'default_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
