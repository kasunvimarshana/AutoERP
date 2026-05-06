<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Rental\Application\Contracts\CreateRentalIncidentServiceInterface;
use Modules\Rental\Application\Contracts\UpdateRentalIncidentServiceInterface;
use Modules\Rental\Domain\Entities\RentalIncident;
use Modules\Rental\Domain\RepositoryInterfaces\RentalIncidentRepositoryInterface;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class RentalIncidentEndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    /** @var RentalIncidentRepositoryInterface&MockObject */
    private RentalIncidentRepositoryInterface $incidentRepository;

    /** @var CreateRentalIncidentServiceInterface&MockObject */
    private CreateRentalIncidentServiceInterface $createService;

    /** @var UpdateRentalIncidentServiceInterface&MockObject */
    private UpdateRentalIncidentServiceInterface $updateService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();

        $this->incidentRepository = $this->createMock(RentalIncidentRepositoryInterface::class);
        $this->createService = $this->createMock(CreateRentalIncidentServiceInterface::class);
        $this->updateService = $this->createMock(UpdateRentalIncidentServiceInterface::class);

        $this->app->instance(RentalIncidentRepositoryInterface::class, $this->incidentRepository);
        $this->app->instance(CreateRentalIncidentServiceInterface::class, $this->createService);
        $this->app->instance(UpdateRentalIncidentServiceInterface::class, $this->updateService);

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
            'email' => 'incident.test@example.com',
            'password' => 'secret',
            'first_name' => 'Incident',
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

    public function test_index_returns_incident_list(): void
    {
        $this->incidentRepository
            ->expects($this->once())
            ->method('findByTenant')
            ->with(9, null, [])
            ->willReturn([$this->buildIncident(id: 50)]);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/rentals/incidents');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 50)
            ->assertJsonPath('data.0.incident_type', 'damage')
            ->assertJsonPath('data.0.status', 'open');
    }

    public function test_index_passes_filter_params(): void
    {
        $this->incidentRepository
            ->expects($this->once())
            ->method('findByTenant')
            ->with(9, null, ['status' => 'open'])
            ->willReturn([]);

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/rentals/incidents?status=open')
            ->assertStatus(HttpResponse::HTTP_OK);
    }

    // ── SHOW ───────────────────────────────────────────────────────────────────

    public function test_show_returns_incident(): void
    {
        $this->incidentRepository
            ->expects($this->once())
            ->method('findById')
            ->with(9, 50)
            ->willReturn($this->buildIncident(id: 50));

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/rentals/incidents/50')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 50);
    }

    public function test_show_returns_404_when_not_found(): void
    {
        $this->incidentRepository
            ->method('findById')
            ->willReturn(null);

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/rentals/incidents/999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // ── STORE ──────────────────────────────────────────────────────────────────

    public function test_store_creates_incident(): void
    {
        $this->createService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->buildIncident(id: 51));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/rentals/incidents', [
                'rental_booking_id' => 1,
                'asset_id' => 10,
                'incident_type' => 'damage',
                'estimated_cost' => 500.0,
            ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 51);
    }

    public function test_store_requires_required_fields(): void
    {
        $this->createService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/rentals/incidents', [])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['rental_booking_id', 'asset_id', 'incident_type']);
    }

    // ── UPDATE ─────────────────────────────────────────────────────────────────

    public function test_update_modifies_incident(): void
    {
        $this->incidentRepository
            ->method('findById')
            ->willReturn($this->buildIncident(id: 50));

        $this->updateService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->buildIncident(id: 50, status: 'resolved'));

        $this->withHeader('X-Tenant-ID', '9')
            ->putJson('/api/rentals/incidents/50', [
                'status' => 'resolved',
            ])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 50);
    }

    public function test_update_returns_404_when_not_found(): void
    {
        $this->incidentRepository
            ->method('findById')
            ->willReturn(null);

        $this->withHeader('X-Tenant-ID', '9')
            ->putJson('/api/rentals/incidents/999', ['status' => 'resolved'])
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // ── DESTROY ────────────────────────────────────────────────────────────────

    public function test_destroy_deletes_incident(): void
    {
        $this->incidentRepository
            ->method('findById')
            ->willReturn($this->buildIncident(id: 50));

        $this->incidentRepository
            ->expects($this->once())
            ->method('delete')
            ->with(9, 50)
            ->willReturn(true);

        $this->withHeader('X-Tenant-ID', '9')
            ->deleteJson('/api/rentals/incidents/50')
            ->assertStatus(HttpResponse::HTTP_NO_CONTENT);
    }

    public function test_destroy_returns_404_when_not_found(): void
    {
        $this->incidentRepository
            ->method('findById')
            ->willReturn(null);

        $this->withHeader('X-Tenant-ID', '9')
            ->deleteJson('/api/rentals/incidents/999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    private function buildIncident(int $id, string $status = 'open'): RentalIncident
    {
        return new RentalIncident(
            tenantId: 9,
            rentalBookingId: 1,
            assetId: 10,
            incidentType: 'damage',
            status: $status,
            estimatedCost: 500.0,
            recoveredAmount: 0.0,
            recoveryStatus: 'none',
            id: $id,
        );
    }
}
