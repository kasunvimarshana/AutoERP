<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Product\Application\Contracts\SearchProductCatalogServiceInterface;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class ProductSearchEndpointsAuthenticatedTest extends TestCase
{
    /** @var SearchProductCatalogServiceInterface&MockObject */
    private SearchProductCatalogServiceInterface $searchProductCatalogService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchProductCatalogService = $this->createMock(SearchProductCatalogServiceInterface::class);
        $this->app->instance(SearchProductCatalogServiceInterface::class, $this->searchProductCatalogService);

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
            'id' => 301,
            'tenant_id' => 9,
            'email' => 'search.test@example.com',
            'password' => 'secret',
            'first_name' => 'Search',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 301);
        $user->setAttribute('tenant_id', 9);

        $this->actingAs($user, 'api');
    }

    public function test_authenticated_search_returns_expected_payload(): void
    {
        $this->searchProductCatalogService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function (array $payload): bool {
                return (int) ($payload['tenant_id'] ?? 0) === 9
                    && ($payload['q'] ?? null) === 'WID-100'
                    && (int) ($payload['customer_id'] ?? 0) === 55
                    && (int) ($payload['supplier_id'] ?? 0) === 77;
            }))
            ->willReturn([
                'data' => [
                    [
                        'product_id' => 101,
                        'variant_id' => null,
                        'name' => 'Widget',
                        'sku' => 'WID-100',
                        'uom' => [
                            'id' => 1,
                            'name' => 'Piece',
                            'symbol' => 'pc',
                        ],
                        'pricing' => [
                            'unit_price' => '45.000000',
                        ],
                        'quantity' => [
                            'available' => '12.000000',
                        ],
                    ],
                ],
                'meta' => [
                    'current_page' => 1,
                    'per_page' => 20,
                    'total' => 1,
                    'last_page' => 1,
                ],
            ]);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/products/search?tenant_id=9&q=WID-100&customer_id=55&supplier_id=77');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.product_id', 101)
            ->assertJsonPath('data.0.sku', 'WID-100')
            ->assertJsonPath('data.0.uom.symbol', 'pc')
            ->assertJsonPath('data.0.pricing.unit_price', '45.000000')
            ->assertJsonPath('data.0.quantity.available', '12.000000');
    }

    public function test_search_uses_header_tenant_context_for_execution(): void
    {
        $this->searchProductCatalogService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function (array $payload): bool {
                return (int) ($payload['tenant_id'] ?? 0) === 8;
            }))
            ->willReturn([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'per_page' => 20,
                    'total' => 0,
                    'last_page' => 1,
                ],
            ]);

        $response = $this->withHeader('X-Tenant-ID', '8')
            ->getJson('/api/products/search?tenant_id=9&q=WID-100');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('meta.total', 0);
    }
}
