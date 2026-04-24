<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Passport\Passport;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Product\Application\Contracts\CreateUomConversionServiceInterface;
use Modules\Product\Application\Contracts\DeleteUomConversionServiceInterface;
use Modules\Product\Application\Contracts\FindUomConversionServiceInterface;
use Modules\Product\Application\Contracts\UomConversionResolverServiceInterface;
use Modules\Product\Application\Contracts\UpdateUomConversionServiceInterface;
use Modules\Product\Domain\Entities\UomConversion;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class UomConversionEndpointsAuthenticatedTest extends TestCase
{
    /** @var FindUomConversionServiceInterface&MockObject */
    private FindUomConversionServiceInterface $findUomConversionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->findUomConversionService = $this->createMock(FindUomConversionServiceInterface::class);
        $this->app->instance(FindUomConversionServiceInterface::class, $this->findUomConversionService);

        $this->app->instance(CreateUomConversionServiceInterface::class, $this->createMock(CreateUomConversionServiceInterface::class));
        $this->app->instance(UpdateUomConversionServiceInterface::class, $this->createMock(UpdateUomConversionServiceInterface::class));
        $this->app->instance(DeleteUomConversionServiceInterface::class, $this->createMock(DeleteUomConversionServiceInterface::class));
        $this->app->instance(UomConversionResolverServiceInterface::class, $this->createMock(UomConversionResolverServiceInterface::class));

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $tenantConfigManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $tenantConfigManager);

        $user = new UserModel([
            'id' => 421,
            'tenant_id' => 15,
            'email' => 'uom.conversion@example.com',
            'password' => 'secret',
            'first_name' => 'UOM',
            'last_name' => 'Conversion',
        ]);
        $user->setAttribute('id', 421);
        $user->setAttribute('tenant_id', 15);

        $this->actingAs($user, 'api');
    }

    public function test_authenticated_index_returns_success_payload(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildUomConversion(id: 301)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findUomConversionService
            ->expects($this->once())
            ->method('list')
            ->with(
                [
                    'tenant_id' => 15,
                    'from_uom_id' => 11,
                    'to_uom_id' => 12,
                ],
                15,
                1,
                '-created_at'
            )
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '15')
            ->getJson('/api/uom-conversions?from_uom_id=11&to_uom_id=12&sort=-created_at');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 301)
            ->assertJsonPath('data.0.from_uom_id', 11)
            ->assertJsonPath('data.0.to_uom_id', 12)
            ->assertJsonPath('data.0.factor', '12.5000000000');
    }

    public function test_authenticated_show_returns_success_payload(): void
    {
        $this->findUomConversionService
            ->expects($this->once())
            ->method('find')
            ->with(302)
            ->willReturn($this->buildUomConversion(id: 302));

        $response = $this->withHeader('X-Tenant-ID', '15')
            ->getJson('/api/uom-conversions/302');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 302)
            ->assertJsonPath('data.from_uom_id', 11)
            ->assertJsonPath('data.to_uom_id', 12);
    }

    public function test_authenticated_index_returns_forbidden_when_authorization_fails(): void
    {
        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(false);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $this->findUomConversionService
            ->expects($this->never())
            ->method('list');

        $response = $this->withHeader('X-Tenant-ID', '15')
            ->getJson('/api/uom-conversions');

        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    public function test_authenticated_resolve_returns_conversion_path_and_quantity(): void
    {
        $resolver = $this->createMock(UomConversionResolverServiceInterface::class);
        $resolver->expects($this->once())
            ->method('convertQuantity')
            ->with(15, null, 11, 13, '2.5', 6)
            ->willReturn([
                'quantity' => '2500.000000',
                'factor' => '1000',
                'path' => [11, 12, 13],
                'from_uom_id' => 11,
                'to_uom_id' => 13,
            ]);
        $this->app->instance(UomConversionResolverServiceInterface::class, $resolver);

        $response = $this->withHeader('X-Tenant-ID', '15')
            ->postJson('/api/uom-conversions/resolve', [
                'from_uom_id' => 11,
                'to_uom_id' => 13,
                'quantity' => '2.5',
            ]);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.quantity', '2500.000000')
            ->assertJsonPath('data.factor', '1000')
            ->assertJsonPath('data.path.0', 11)
            ->assertJsonPath('data.path.2', 13);
    }

    private function buildUomConversion(int $id): UomConversion
    {
        return new UomConversion(
            id: $id,
            tenantId: 15,
            fromUomId: 11,
            toUomId: 12,
            factor: '12.5000000000',
        );
    }
}
