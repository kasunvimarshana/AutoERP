<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Passport\Passport;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Product\Application\Contracts\RebuildProductSearchProjectionServiceInterface;
use Modules\Product\Application\Contracts\SearchProductsServiceInterface;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class ProductSearchEndpointsAuthenticatedTest extends TestCase
{
    /** @var SearchProductsServiceInterface&MockObject */
    private SearchProductsServiceInterface $searchService;

    /** @var RebuildProductSearchProjectionServiceInterface&MockObject */
    private RebuildProductSearchProjectionServiceInterface $rebuildService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchService = $this->createMock(SearchProductsServiceInterface::class);
        $this->rebuildService = $this->createMock(RebuildProductSearchProjectionServiceInterface::class);

        $this->app->instance(SearchProductsServiceInterface::class, $this->searchService);
        $this->app->instance(RebuildProductSearchProjectionServiceInterface::class, $this->rebuildService);

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $tenantConfigManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $tenantConfigManager);

        $user = new UserModel([
            'id' => 301,
            'tenant_id' => 9,
            'email' => 'product.search@example.com',
            'password' => 'secret',
            'first_name' => 'Search',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 301);
        $user->setAttribute('tenant_id', 9);

        Passport::actingAs($user, [], 'api');
    }

    public function test_authenticated_search_returns_paginated_results(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [
                (object) [
                    'id' => 1,
                    'tenant_id' => 9,
                    'product_id' => 10,
                    'variant_id' => null,
                    'product_name' => 'Widget A',
                    'product_sku' => 'WID-A',
                    'stock_available' => '12.000000',
                ],
            ],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->searchService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static fn (array $filters): bool =>
                (int) ($filters['tenant_id'] ?? 0) === 9
                && ($filters['q'] ?? '') === 'Widget'
            ))
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/products/search?q=Widget');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.product_id', 10)
            ->assertJsonPath('data.0.product_name', 'Widget A');
    }

    public function test_authenticated_rebuild_returns_indexed_rows(): void
    {
        $this->rebuildService
            ->expects($this->once())
            ->method('execute')
            ->with(9)
            ->willReturn(27);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/products/search/rebuild');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.tenant_id', 9)
            ->assertJsonPath('data.indexed_rows', 27);
    }
}
