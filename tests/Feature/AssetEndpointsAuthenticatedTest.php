<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Asset\Application\Contracts\ManageAssetDepreciationServiceInterface;
use Modules\Asset\Application\Contracts\ManageAssetDocumentServiceInterface;
use Modules\Asset\Application\Contracts\ManageAssetOwnerServiceInterface;
use Modules\Asset\Application\Contracts\ManageAssetServiceInterface;
use Modules\Asset\Application\Contracts\ManageVehicleServiceInterface;
use Modules\Asset\Domain\Entities\Asset;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class AssetEndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    /** @var ManageAssetServiceInterface&MockObject */
    private ManageAssetServiceInterface $manageAssetService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();

        $this->manageAssetService = $this->createMock(ManageAssetServiceInterface::class);

        $this->app->instance(ManageAssetServiceInterface::class, $this->manageAssetService);
        $this->app->instance(ManageVehicleServiceInterface::class, $this->createMock(ManageVehicleServiceInterface::class));
        $this->app->instance(ManageAssetOwnerServiceInterface::class, $this->createMock(ManageAssetOwnerServiceInterface::class));
        $this->app->instance(ManageAssetDocumentServiceInterface::class, $this->createMock(ManageAssetDocumentServiceInterface::class));
        $this->app->instance(ManageAssetDepreciationServiceInterface::class, $this->createMock(ManageAssetDepreciationServiceInterface::class));

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
            'id' => 508,
            'tenant_id' => 7,
            'email' => 'asset.test@example.com',
            'password' => 'secret',
            'first_name' => 'Asset',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 508);
        $user->setAttribute('tenant_id', 7);

        $this->actingAs($user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));
    }

    public function test_authenticated_asset_index_returns_success_payload(): void
    {
        $asset = $this->buildAsset('asset-uuid-1');

        $this->manageAssetService
            ->expects($this->once())
            ->method('list')
            ->with(7)
            ->willReturn(['data' => [$asset], 'total' => 1, 'page' => 1, 'per_page' => 15]);

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/assets');

        $response->assertStatus(HttpResponse::HTTP_OK);
    }

    public function test_authenticated_asset_show_returns_success_payload(): void
    {
        $asset = $this->buildAsset('asset-uuid-1');

        $this->manageAssetService
            ->expects($this->once())
            ->method('find')
            ->with(7, 'asset-uuid-1')
            ->willReturn($asset);

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/assets/asset-uuid-1');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('id', 'asset-uuid-1')
            ->assertJsonPath('tenant_id', '7')
            ->assertJsonPath('status', 'active');
    }

    public function test_authenticated_asset_create_validates_required_fields(): void
    {
        $this->manageAssetService
            ->expects($this->never())
            ->method('create');

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/assets', []);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name', 'type']);
    }

    private function buildAsset(string $id): Asset
    {
        return new Asset(
            id: $id,
            tenantId: '7',
            name: 'Company Vehicle',
            type: 'vehicle',
            serialNumber: 'SN-12345',
            assetOwnerId: 'owner-uuid-1',
            purchaseDate: new \DateTime('2023-01-01'),
            acquisitionCost: '50000.000000',
            status: 'active',
            depreciationMethod: 'straight_line',
            usefulLifeYears: 5,
            salvageValue: '5000.000000',
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
