<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Rental\Application\Contracts\CalculateRentalChargeServiceInterface;
use Modules\Rental\Application\Contracts\CheckInVehicleServiceInterface;
use Modules\Rental\Application\Contracts\CheckOutVehicleServiceInterface;
use Modules\Rental\Application\Contracts\CreateRentalAgreementServiceInterface;
use Modules\Rental\Application\Contracts\CreateRentalReservationServiceInterface;
use Modules\Rental\Application\Contracts\ManageRentalAgreementServiceInterface;
use Modules\Rental\Application\Contracts\ManageRentalReservationServiceInterface;
use Modules\Rental\Application\Contracts\ManageRentalTransactionServiceInterface;
use Modules\Rental\Domain\Entities\RentalReservation;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class RentalEndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    /** @var ManageRentalReservationServiceInterface&MockObject */
    private ManageRentalReservationServiceInterface $manageReservationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();

        $this->manageReservationService = $this->createMock(ManageRentalReservationServiceInterface::class);

        $this->app->instance(ManageRentalReservationServiceInterface::class, $this->manageReservationService);
        $this->app->instance(ManageRentalAgreementServiceInterface::class, $this->createMock(ManageRentalAgreementServiceInterface::class));
        $this->app->instance(ManageRentalTransactionServiceInterface::class, $this->createMock(ManageRentalTransactionServiceInterface::class));
        $this->app->instance(CreateRentalAgreementServiceInterface::class, $this->createMock(CreateRentalAgreementServiceInterface::class));
        $this->app->instance(CreateRentalReservationServiceInterface::class, $this->createMock(CreateRentalReservationServiceInterface::class));
        $this->app->instance(CalculateRentalChargeServiceInterface::class, $this->createMock(CalculateRentalChargeServiceInterface::class));
        $this->app->instance(CheckInVehicleServiceInterface::class, $this->createMock(CheckInVehicleServiceInterface::class));
        $this->app->instance(CheckOutVehicleServiceInterface::class, $this->createMock(CheckOutVehicleServiceInterface::class));

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $tenantConfigManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $tenantConfigManager);

        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturn(0);
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app->instance(PresenceVerifierInterface::class, $presenceVerifier);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        $user = new UserModel([
            'id' => 507,
            'tenant_id' => 7,
            'email' => 'rental.test@example.com',
            'password' => 'secret',
            'first_name' => 'Rental',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 507);
        $user->setAttribute('tenant_id', 7);

        $this->actingAs($user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));
    }

    public function test_authenticated_rental_reservation_index_returns_success_payload(): void
    {
        $reservation = $this->buildRentalReservation('res-uuid-1');

        $this->manageReservationService
            ->expects($this->once())
            ->method('list')
            ->with(7)
            ->willReturn(['data' => [$reservation], 'total' => 1, 'page' => 1, 'per_page' => 15]);

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/rentals/reservations');

        $response->assertStatus(HttpResponse::HTTP_OK);
    }

    public function test_authenticated_rental_reservation_show_returns_success_payload(): void
    {
        $reservation = $this->buildRentalReservation('res-uuid-1');

        $this->manageReservationService
            ->expects($this->once())
            ->method('find')
            ->with(7, 'res-uuid-1')
            ->willReturn($reservation);

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/rentals/reservations/res-uuid-1');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('id', 'res-uuid-1')
            ->assertJsonPath('tenant_id', '7')
            ->assertJsonPath('status', 'pending');
    }

    public function test_authenticated_rental_reservation_create_validates_required_fields(): void
    {
        $this->manageReservationService
            ->expects($this->never())
            ->method('create');

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/rentals/reservations', []);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['vehicle_id', 'customer_id']);
    }

    private function buildRentalReservation(string $id): RentalReservation
    {
        return new RentalReservation(
            id: $id,
            tenantId: '7',
            vehicleId: 'vehicle-uuid-1',
            customerId: 'customer-uuid-1',
            driverId: null,
            reservationNumber: 'RES-001',
            startAt: new \DateTime('2024-01-10 08:00:00'),
            expectedReturnAt: new \DateTime('2024-01-15 08:00:00'),
            billingUnit: 'day',
            baseRate: '50.000000',
            estimatedDistance: '0',
            estimatedAmount: '250.000000',
            status: 'pending',
        );
    }

    private function clearRoutesCacheOnce(): void
    {
        if (self::$routesCleared) {
            return;
        }

        Artisan::call('route:clear');
        self::$routesCleared = true;
    }
}
