<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Passport\Passport;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Product\Application\Contracts\CreateUnitOfMeasureServiceInterface;
use Modules\Product\Application\Contracts\DeleteUnitOfMeasureServiceInterface;
use Modules\Product\Application\Contracts\FindUnitOfMeasureServiceInterface;
use Modules\Product\Application\Contracts\UpdateUnitOfMeasureServiceInterface;
use Modules\Product\Domain\Entities\UnitOfMeasure;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class UnitOfMeasureEndpointsAuthenticatedTest extends TestCase
{
    /** @var FindUnitOfMeasureServiceInterface&MockObject */
    private FindUnitOfMeasureServiceInterface $findUnitOfMeasureService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->findUnitOfMeasureService = $this->createMock(FindUnitOfMeasureServiceInterface::class);
        $this->app->instance(FindUnitOfMeasureServiceInterface::class, $this->findUnitOfMeasureService);

        $this->app->instance(CreateUnitOfMeasureServiceInterface::class, $this->createMock(CreateUnitOfMeasureServiceInterface::class));
        $this->app->instance(UpdateUnitOfMeasureServiceInterface::class, $this->createMock(UpdateUnitOfMeasureServiceInterface::class));
        $this->app->instance(DeleteUnitOfMeasureServiceInterface::class, $this->createMock(DeleteUnitOfMeasureServiceInterface::class));

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $tenantConfigManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $tenantConfigManager);

        $user = new UserModel([
            'id' => 241,
            'tenant_id' => 9,
            'email' => 'uom.test@example.com',
            'password' => 'secret',
            'first_name' => 'Uom',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 241);
        $user->setAttribute('tenant_id', 9);

        Passport::actingAs($user, [], 'api');
    }

    public function test_authenticated_index_returns_success_payload(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildUnitOfMeasure(id: 91)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findUnitOfMeasureService
            ->expects($this->once())
            ->method('list')
            ->with(
                [
                    'tenant_id' => 9,
                    'type' => 'unit',
                ],
                15,
                1,
                '-created_at'
            )
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/units-of-measure?tenant_id=9&type=unit&sort=-created_at');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 91)
            ->assertJsonPath('data.0.symbol', 'EA');
    }

    public function test_authenticated_show_returns_success_payload(): void
    {
        $this->findUnitOfMeasureService
            ->expects($this->once())
            ->method('find')
            ->with(92)
            ->willReturn($this->buildUnitOfMeasure(id: 92));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/units-of-measure/92');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 92)
            ->assertJsonPath('data.tenant_id', 9)
            ->assertJsonPath('data.symbol', 'EA');
    }

    public function test_authenticated_index_returns_forbidden_when_authorization_fails(): void
    {
        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(false);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $this->findUnitOfMeasureService
            ->expects($this->never())
            ->method('list');

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/units-of-measure');

        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    private function buildUnitOfMeasure(int $id): UnitOfMeasure
    {
        return new UnitOfMeasure(
            id: $id,
            tenantId: 9,
            name: 'Each',
            symbol: 'EA',
            type: 'unit',
            isBase: true,
        );
    }
}
