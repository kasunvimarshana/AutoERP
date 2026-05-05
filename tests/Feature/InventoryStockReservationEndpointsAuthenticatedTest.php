<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Validation\PresenceVerifierInterface;
use Laravel\Passport\Passport;
use Modules\Inventory\Application\Contracts\CreateStockReservationServiceInterface;
use Modules\Inventory\Application\Contracts\FindStockReservationServiceInterface;
use Modules\Inventory\Application\Contracts\ReleaseExpiredStockReservationsServiceInterface;
use Modules\Inventory\Application\Contracts\ReleaseStockReservationServiceInterface;
use Modules\Inventory\Domain\Exceptions\InsufficientAvailableStockException;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class InventoryStockReservationEndpointsAuthenticatedTest extends TestCase
{
    /** @var CreateStockReservationServiceInterface&MockObject */
    private CreateStockReservationServiceInterface $createStockReservationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createStockReservationService = $this->createMock(CreateStockReservationServiceInterface::class);
        $this->app->instance(CreateStockReservationServiceInterface::class, $this->createStockReservationService);
        $this->app->instance(FindStockReservationServiceInterface::class, $this->createMock(FindStockReservationServiceInterface::class));
        $this->app->instance(ReleaseStockReservationServiceInterface::class, $this->createMock(ReleaseStockReservationServiceInterface::class));
        $this->app->instance(ReleaseExpiredStockReservationsServiceInterface::class, $this->createMock(ReleaseExpiredStockReservationsServiceInterface::class));

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
            'id' => 301,
            'tenant_id' => 9,
            'email' => 'inventory.reservation@test.com',
            'password' => 'secret',
            'first_name' => 'Inventory',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 301);
        $user->setAttribute('tenant_id', 9);

        $this->actingAs($user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));
    }

    public function test_authenticated_store_returns_unprocessable_entity_when_stock_is_insufficient(): void
    {
        $this->createStockReservationService
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(new InsufficientAvailableStockException);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/inventory/stock-reservations', [
                'tenant_id' => 9,
                'product_id' => 1001,
                'location_id' => 501,
                'quantity' => '999.000000',
            ]);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonPath('message', 'Insufficient available stock for reservation.');
    }
}
