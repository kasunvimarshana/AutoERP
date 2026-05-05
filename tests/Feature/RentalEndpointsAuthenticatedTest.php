<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Rental\Application\Contracts\ActivateRentalBookingServiceInterface;
use Modules\Rental\Application\Contracts\CancelRentalBookingServiceInterface;
use Modules\Rental\Application\Contracts\CompleteRentalBookingServiceInterface;
use Modules\Rental\Application\Contracts\CreateRentalBookingServiceInterface;
use Modules\Rental\Application\Contracts\UpdateRentalBookingServiceInterface;
use Modules\Rental\Domain\Entities\RentalBooking;
use Modules\Rental\Domain\RepositoryInterfaces\RentalBookingRepositoryInterface;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class RentalEndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    /** @var RentalBookingRepositoryInterface&MockObject */
    private RentalBookingRepositoryInterface $bookingRepository;

    /** @var CreateRentalBookingServiceInterface&MockObject */
    private CreateRentalBookingServiceInterface $createService;

    /** @var UpdateRentalBookingServiceInterface&MockObject */
    private UpdateRentalBookingServiceInterface $updateService;

    /** @var ActivateRentalBookingServiceInterface&MockObject */
    private ActivateRentalBookingServiceInterface $activateService;

    /** @var CompleteRentalBookingServiceInterface&MockObject */
    private CompleteRentalBookingServiceInterface $completeService;

    /** @var CancelRentalBookingServiceInterface&MockObject */
    private CancelRentalBookingServiceInterface $cancelService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();

        $this->bookingRepository = $this->createMock(RentalBookingRepositoryInterface::class);
        $this->createService     = $this->createMock(CreateRentalBookingServiceInterface::class);
        $this->updateService     = $this->createMock(UpdateRentalBookingServiceInterface::class);
        $this->activateService   = $this->createMock(ActivateRentalBookingServiceInterface::class);
        $this->completeService   = $this->createMock(CompleteRentalBookingServiceInterface::class);
        $this->cancelService     = $this->createMock(CancelRentalBookingServiceInterface::class);

        $this->app->instance(RentalBookingRepositoryInterface::class, $this->bookingRepository);
        $this->app->instance(CreateRentalBookingServiceInterface::class, $this->createService);
        $this->app->instance(UpdateRentalBookingServiceInterface::class, $this->updateService);
        $this->app->instance(ActivateRentalBookingServiceInterface::class, $this->activateService);
        $this->app->instance(CompleteRentalBookingServiceInterface::class, $this->completeService);
        $this->app->instance(CancelRentalBookingServiceInterface::class, $this->cancelService);

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $tenantConfigManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $tenantConfigManager);

        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturn(1);
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app->instance(PresenceVerifierInterface::class, $presenceVerifier);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        $user = new UserModel([
            'id'         => 1,
            'tenant_id'  => 9,
            'email'      => 'rental.test@example.com',
            'password'   => 'secret',
            'first_name' => 'Rental',
            'last_name'  => 'Tester',
        ]);
        $user->setAttribute('id', 1);
        $user->setAttribute('tenant_id', 9);

        $this->actingAs($user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));
    }

    private function clearRoutesCacheOnce(): void
    {
        if (self::$routesCleared) {
            return;
        }

        Artisan::call('route:clear');
        self::$routesCleared = true;
    }

    // ── INDEX ──────────────────────────────────────────────────────────────────

    public function test_index_returns_list_of_bookings(): void
    {
        $this->bookingRepository
            ->expects($this->once())
            ->method('findByTenant')
            ->with(9, null, [])
            ->willReturn([$this->buildBooking(id: 10)]);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/rentals/bookings');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 10)
            ->assertJsonPath('data.0.tenant_id', 9)
            ->assertJsonPath('data.0.status', 'draft');
    }

    public function test_index_passes_status_filter(): void
    {
        $this->bookingRepository
            ->expects($this->once())
            ->method('findByTenant')
            ->with(9, null, ['status' => 'active'])
            ->willReturn([]);

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/rentals/bookings?status=active')
            ->assertStatus(HttpResponse::HTTP_OK);
    }

    // ── SHOW ───────────────────────────────────────────────────────────────────

    public function test_show_returns_booking(): void
    {
        $this->bookingRepository
            ->expects($this->once())
            ->method('findById')
            ->with(9, 10)
            ->willReturn($this->buildBooking(id: 10));

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/rentals/bookings/10')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 10);
    }

    public function test_show_returns_404_when_not_found(): void
    {
        $this->bookingRepository
            ->method('findById')
            ->willReturn(null);

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/rentals/bookings/999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // ── STORE ──────────────────────────────────────────────────────────────────

    public function test_store_creates_booking(): void
    {
        $this->createService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->buildBooking(id: 11));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/rentals/bookings', $this->validStorePayload());

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 11);
    }

    public function test_store_requires_customer_id(): void
    {
        $this->createService->expects($this->never())->method('execute');

        $payload = $this->validStorePayload();
        unset($payload['customer_id']);

        $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/rentals/bookings', $payload)
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['customer_id']);
    }

    public function test_store_requires_rental_mode(): void
    {
        $this->createService->expects($this->never())->method('execute');

        $payload = $this->validStorePayload();
        unset($payload['rental_mode']);

        $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/rentals/bookings', $payload)
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['rental_mode']);
    }

    public function test_store_requires_asset_ids(): void
    {
        $this->createService->expects($this->never())->method('execute');

        $payload = $this->validStorePayload();
        unset($payload['asset_ids']);

        $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/rentals/bookings', $payload)
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['asset_ids']);
    }

    public function test_store_requires_return_due_at_after_pickup_at(): void
    {
        $this->createService->expects($this->never())->method('execute');

        $payload = $this->validStorePayload();
        $payload['return_due_at'] = '2026-05-01 10:00:00'; // before pickup_at

        $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/rentals/bookings', $payload)
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['return_due_at']);
    }

    // ── UPDATE ─────────────────────────────────────────────────────────────────

    public function test_update_modifies_booking(): void
    {
        $this->updateService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->buildBooking(id: 10, ratePlan: 'weekly'));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->putJson('/api/rentals/bookings/10', [
                'rate_plan'   => 'weekly',
                'rate_amount' => 800.00,
            ]);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 10);
    }

    public function test_update_rejects_invalid_rental_mode(): void
    {
        $this->updateService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '9')
            ->putJson('/api/rentals/bookings/10', [
                'rental_mode' => 'invalid_mode',
            ])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['rental_mode']);
    }

    // ── ACTIVATE ───────────────────────────────────────────────────────────────

    public function test_activate_transitions_booking_to_active(): void
    {
        $active = $this->buildBooking(id: 10, status: 'active');

        $this->activateService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($active);

        $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/rentals/bookings/10/activate')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.status', 'active');
    }

    // ── COMPLETE ───────────────────────────────────────────────────────────────

    public function test_complete_closes_booking(): void
    {
        $completed = $this->buildBooking(id: 10, status: 'completed');

        $this->completeService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($completed);

        $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/rentals/bookings/10/complete', [
                'actual_return_at' => '2026-06-09 14:00:00',
                'final_amount'     => 1200.00,
            ])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.status', 'completed');
    }

    // ── CANCEL ─────────────────────────────────────────────────────────────────

    public function test_cancel_aborts_booking(): void
    {
        $cancelled = $this->buildBooking(id: 10, status: 'cancelled');

        $this->cancelService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($cancelled);

        $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/rentals/bookings/10/cancel', [
                'notes' => 'Customer requested cancellation.',
            ])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.status', 'cancelled');
    }

    // ── DESTROY ────────────────────────────────────────────────────────────────

    public function test_destroy_deletes_booking(): void
    {
        $this->bookingRepository
            ->expects($this->once())
            ->method('delete')
            ->with(9, 10)
            ->willReturn(true);

        $this->withHeader('X-Tenant-ID', '9')
            ->deleteJson('/api/rentals/bookings/10')
            ->assertStatus(HttpResponse::HTTP_NO_CONTENT);
    }

    // ── HELPERS ────────────────────────────────────────────────────────────────

    private function buildBooking(
        int $id,
        string $status = 'draft',
        string $ratePlan = 'daily',
    ): RentalBooking {
        return new RentalBooking(
            tenantId: 9,
            customerId: 1,
            rentalMode: 'with_driver',
            ownershipModel: 'owned_fleet',
            pickupAt: '2026-06-01 10:00:00',
            returnDueAt: '2026-06-10 10:00:00',
            currencyId: 1,
            ratePlan: $ratePlan,
            rateAmount: 150.0,
            status: $status,
            id: $id,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validStorePayload(): array
    {
        return [
            'customer_id'    => 1,
            'rental_mode'    => 'with_driver',
            'ownership_model' => 'owned_fleet',
            'pickup_at'      => '2026-06-01 10:00:00',
            'return_due_at'  => '2026-06-10 10:00:00',
            'currency_id'    => 1,
            'rate_plan'      => 'daily',
            'rate_amount'    => 150.00,
            'asset_ids'      => [1],
        ];
    }
}
