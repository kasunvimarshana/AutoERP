<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Laravel\Passport\Passport;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Rental\Application\Contracts\AssignDriverServiceInterface;
use Modules\Rental\Application\Contracts\SubstituteDriverServiceInterface;
use Modules\Rental\Domain\Entities\RentalDriverAssignment;
use Modules\Rental\Domain\RepositoryInterfaces\RentalDriverAssignmentRepositoryInterface;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class RentalDriverAssignmentEndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    /** @var RentalDriverAssignmentRepositoryInterface&MockObject */
    private RentalDriverAssignmentRepositoryInterface $assignmentRepository;

    /** @var AssignDriverServiceInterface&MockObject */
    private AssignDriverServiceInterface $assignService;

    /** @var SubstituteDriverServiceInterface&MockObject */
    private SubstituteDriverServiceInterface $substituteService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();

        $this->assignmentRepository = $this->createMock(RentalDriverAssignmentRepositoryInterface::class);
        $this->assignService = $this->createMock(AssignDriverServiceInterface::class);
        $this->substituteService = $this->createMock(SubstituteDriverServiceInterface::class);

        $this->app->instance(RentalDriverAssignmentRepositoryInterface::class, $this->assignmentRepository);
        $this->app->instance(AssignDriverServiceInterface::class, $this->assignService);
        $this->app->instance(SubstituteDriverServiceInterface::class, $this->substituteService);

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
            'email' => 'rental.test@example.com',
            'password' => 'secret',
            'first_name' => 'Rental',
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

    public function test_index_returns_assignments_for_booking(): void
    {
        $this->assignmentRepository
            ->expects($this->once())
            ->method('findByBooking')
            ->with(9, 5, null)
            ->willReturn([$this->buildAssignment(id: 100)]);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/rentals/bookings/5/driver-assignments');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 100)
            ->assertJsonPath('data.0.tenant_id', 9)
            ->assertJsonPath('data.0.assignment_status', 'assigned');
    }

    public function test_index_passes_status_filter(): void
    {
        $this->assignmentRepository
            ->expects($this->once())
            ->method('findByBooking')
            ->with(9, 5, 'assigned')
            ->willReturn([]);

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/rentals/bookings/5/driver-assignments?status=assigned')
            ->assertStatus(HttpResponse::HTTP_OK);
    }

    // ── SHOW ───────────────────────────────────────────────────────────────────

    public function test_show_returns_assignment(): void
    {
        $this->assignmentRepository
            ->expects($this->once())
            ->method('findById')
            ->with(9, 100)
            ->willReturn($this->buildAssignment(id: 100, bookingId: 5));

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/rentals/bookings/5/driver-assignments/100')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 100);
    }

    public function test_show_returns_404_when_not_found(): void
    {
        $this->assignmentRepository
            ->method('findById')
            ->willReturn(null);

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/rentals/bookings/5/driver-assignments/999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // ── STORE ──────────────────────────────────────────────────────────────────

    public function test_store_creates_assignment(): void
    {
        $this->assignService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->buildAssignment(id: 101, bookingId: 5));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/rentals/bookings/5/driver-assignments', [
                'employee_id' => 20,
                'assigned_from' => now()->toDateTimeString(),
            ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 101);
    }

    public function test_store_requires_employee_id(): void
    {
        $this->assignService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/rentals/bookings/5/driver-assignments', [])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['employee_id']);
    }

    // ── SUBSTITUTE ─────────────────────────────────────────────────────────────

    public function test_substitute_creates_new_assignment(): void
    {
        $this->substituteService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->buildAssignment(id: 102, bookingId: 5));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/rentals/bookings/5/driver-assignments/100/substitute', [
                'employee_id' => 21,
                'assigned_from' => now()->toDateTimeString(),
                'substitution_reason' => 'Illness',
            ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 102);
    }

    public function test_substitute_requires_employee_id(): void
    {
        $this->substituteService->expects($this->never())->method('execute');

        $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/rentals/bookings/5/driver-assignments/100/substitute', [])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['employee_id']);
    }

    // ── DESTROY ────────────────────────────────────────────────────────────────

    public function test_destroy_cancels_assignment(): void
    {
        $this->assignmentRepository
            ->method('findById')
            ->willReturn($this->buildAssignment(id: 100, bookingId: 5));

        $this->assignmentRepository
            ->expects($this->once())
            ->method('delete')
            ->with(9, 100)
            ->willReturn(true);

        $this->withHeader('X-Tenant-ID', '9')
            ->deleteJson('/api/rentals/bookings/5/driver-assignments/100')
            ->assertStatus(HttpResponse::HTTP_NO_CONTENT);
    }

    public function test_destroy_returns_404_when_not_found(): void
    {
        $this->assignmentRepository
            ->method('findById')
            ->willReturn(null);

        $this->withHeader('X-Tenant-ID', '9')
            ->deleteJson('/api/rentals/bookings/5/driver-assignments/999')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    private function buildAssignment(int $id, int $bookingId = 5): RentalDriverAssignment
    {
        return new RentalDriverAssignment(
            tenantId: 9,
            rentalBookingId: $bookingId,
            employeeId: 20,
            assignmentStatus: 'assigned',
            id: $id,
        );
    }
}
