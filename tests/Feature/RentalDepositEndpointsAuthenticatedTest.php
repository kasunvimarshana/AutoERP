<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Rental\Application\Contracts\HoldRentalDepositServiceInterface;
use Modules\Rental\Application\Contracts\ReleaseRentalDepositServiceInterface;
use Modules\Rental\Domain\Entities\RentalDeposit;
use Modules\Rental\Domain\RepositoryInterfaces\RentalDepositRepositoryInterface;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class RentalDepositEndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    /** @var RentalDepositRepositoryInterface&MockObject */
    private RentalDepositRepositoryInterface $depositRepository;

    /** @var HoldRentalDepositServiceInterface&MockObject */
    private HoldRentalDepositServiceInterface $holdService;

    /** @var ReleaseRentalDepositServiceInterface&MockObject */
    private ReleaseRentalDepositServiceInterface $releaseService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();

        $this->depositRepository = $this->createMock(RentalDepositRepositoryInterface::class);
        $this->holdService = $this->createMock(HoldRentalDepositServiceInterface::class);
        $this->releaseService = $this->createMock(ReleaseRentalDepositServiceInterface::class);

        $this->app->instance(RentalDepositRepositoryInterface::class, $this->depositRepository);
        $this->app->instance(HoldRentalDepositServiceInterface::class, $this->holdService);
        $this->app->instance(ReleaseRentalDepositServiceInterface::class, $this->releaseService);

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
            'id' => 1,
            'tenant_id' => 9,
            'email' => 'deposit.test@example.com',
            'password' => 'secret',
            'first_name' => 'Deposit',
            'last_name' => 'Tester',
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

    public function test_index_returns_deposits_for_booking(): void
    {
        $this->depositRepository
            ->expects($this->once())
            ->method('findByBooking')
            ->with(9, 5)
            ->willReturn([$this->buildDeposit(id: 200)]);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/rentals/bookings/5/deposits');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 200)
            ->assertJsonPath('data.0.tenant_id', 9)
            ->assertJsonPath('data.0.status', 'held');
    }

    // ── SHOW ───────────────────────────────────────────────────────────────────

    public function test_show_returns_deposit(): void
    {
        $this->depositRepository
            ->expects($this->once())
            ->method('findById')
            ->with(9, 200)
            ->willReturn($this->buildDeposit(id: 200, bookingId: 5));

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/rentals/bookings/5/deposits/200')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 200);
    }

    public function test_show_returns_404_when_not_found(): void
    {
        $this->depositRepository
            ->method('findById')
            ->willReturn(null);

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/rentals/bookings/5/deposits/999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // ── STORE (HOLD) ───────────────────────────────────────────────────────────

    public function test_store_holds_deposit(): void
    {
        $this->holdService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->buildDeposit(id: 201, bookingId: 5));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/rentals/bookings/5/deposits', [
                'currency_id' => 1,
                'held_amount' => 1000.0,
            ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 201);
    }

    public function test_store_requires_required_fields(): void
    {
        $this->holdService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/rentals/bookings/5/deposits', [])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['currency_id', 'held_amount']);
    }

    // ── RELEASE ────────────────────────────────────────────────────────────────

    public function test_release_processes_deposit_release(): void
    {
        $this->depositRepository
            ->method('findById')
            ->willReturn($this->buildDeposit(id: 200, bookingId: 5));

        $this->releaseService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->buildDeposit(id: 200, bookingId: 5, status: 'released', releasedAmount: 1000.0));

        $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/rentals/bookings/5/deposits/200/release', [
                'release_amount' => 1000.0,
            ])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 200);
    }

    public function test_release_returns_404_when_deposit_not_found(): void
    {
        $this->depositRepository
            ->method('findById')
            ->willReturn(null);

        $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/rentals/bookings/5/deposits/999/release', [
                'release_amount' => 100.0,
            ])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // ── DESTROY ────────────────────────────────────────────────────────────────

    public function test_destroy_deletes_deposit(): void
    {
        $this->depositRepository
            ->method('findById')
            ->willReturn($this->buildDeposit(id: 200, bookingId: 5));

        $this->depositRepository
            ->expects($this->once())
            ->method('delete')
            ->with(9, 200)
            ->willReturn(true);

        $this->withHeader('X-Tenant-ID', '9')
            ->deleteJson('/api/rentals/bookings/5/deposits/200')
            ->assertStatus(HttpResponse::HTTP_NO_CONTENT);
    }

    public function test_destroy_returns_404_when_not_found(): void
    {
        $this->depositRepository
            ->method('findById')
            ->willReturn(null);

        $this->withHeader('X-Tenant-ID', '9')
            ->deleteJson('/api/rentals/bookings/5/deposits/999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    private function buildDeposit(int $id, int $bookingId = 5, string $status = 'held', float $releasedAmount = 0.0): RentalDeposit
    {
        return new RentalDeposit(
            tenantId: 9,
            rentalBookingId: $bookingId,
            currencyId: 1,
            heldAmount: 1000.0,
            status: $status,
            releasedAmount: $releasedAmount,
            forfeitedAmount: 0.0,
            id: $id,
        );
    }
}
