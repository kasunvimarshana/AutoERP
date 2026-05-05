<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Driver\Application\Contracts\ManageAvailabilityServiceInterface;
use Modules\Driver\Application\Contracts\ManageCommissionServiceInterface;
use Modules\Driver\Application\Contracts\ManageDriverServiceInterface;
use Modules\Driver\Application\Contracts\ManageLicenseServiceInterface;
use Modules\Driver\Domain\Entities\Driver;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class DriverEndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    /** @var ManageDriverServiceInterface&MockObject */
    private ManageDriverServiceInterface $manageDriverService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();

        $this->manageDriverService = $this->createMock(ManageDriverServiceInterface::class);

        $this->app->instance(ManageDriverServiceInterface::class, $this->manageDriverService);
        $this->app->instance(ManageLicenseServiceInterface::class, $this->createMock(ManageLicenseServiceInterface::class));
        $this->app->instance(ManageAvailabilityServiceInterface::class, $this->createMock(ManageAvailabilityServiceInterface::class));
        $this->app->instance(ManageCommissionServiceInterface::class, $this->createMock(ManageCommissionServiceInterface::class));

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
            'id' => 509,
            'tenant_id' => 7,
            'email' => 'driver.test@example.com',
            'password' => 'secret',
            'first_name' => 'Driver',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 509);
        $user->setAttribute('tenant_id', 7);

        $this->actingAs($user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));
    }

    public function test_authenticated_driver_index_returns_success_payload(): void
    {
        $driver = $this->buildDriver('driver-uuid-1');

        $this->manageDriverService
            ->expects($this->once())
            ->method('list')
            ->with(7)
            ->willReturn(['data' => [$driver], 'total' => 1, 'page' => 1, 'per_page' => 15]);

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/drivers');

        $response->assertStatus(HttpResponse::HTTP_OK);
    }

    public function test_authenticated_driver_store_validates_required_fields(): void
    {
        $this->manageDriverService
            ->expects($this->never())
            ->method('create');

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/drivers', []);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'phone', 'date_of_birth']);
    }

    private function buildDriver(string $id): Driver
    {
        return new Driver(
            id: $id,
            tenantId: '7',
            employeeId: null,
            firstName: 'John',
            lastName: 'Driver',
            email: 'john.driver@example.com',
            phone: '+1234567890',
            dateOfBirth: new \DateTime('1990-01-01'),
            driverType: 'employee',
            status: 'active',
            baseDailyWage: '100.000000',
            commissionPercentage: '5.000000',
            activeSince: new \DateTime('2020-01-01'),
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
