<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Supplier\Application\Contracts\CreateSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\FindSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\CreateSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\FindSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\CreateSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\FindSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\CreateSupplierProductServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierProductServiceInterface;
use Modules\Supplier\Application\Contracts\FindSupplierProductServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierProductServiceInterface;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class SupplierEndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    /** @var FindSupplierServiceInterface&MockObject */
    private FindSupplierServiceInterface $findSupplierService;

    /** @var CreateSupplierServiceInterface&MockObject */
    private CreateSupplierServiceInterface $createSupplierService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();

        $this->findSupplierService = $this->createMock(FindSupplierServiceInterface::class);
        $this->createSupplierService = $this->createMock(CreateSupplierServiceInterface::class);

        $this->app->instance(FindSupplierServiceInterface::class, $this->findSupplierService);
        $this->app->instance(CreateSupplierServiceInterface::class, $this->createSupplierService);
        $this->app->instance(UpdateSupplierServiceInterface::class, $this->createMock(UpdateSupplierServiceInterface::class));
        $this->app->instance(DeleteSupplierServiceInterface::class, $this->createMock(DeleteSupplierServiceInterface::class));

        $this->app->instance(FindSupplierAddressServiceInterface::class, $this->createMock(FindSupplierAddressServiceInterface::class));
        $this->app->instance(CreateSupplierAddressServiceInterface::class, $this->createMock(CreateSupplierAddressServiceInterface::class));
        $this->app->instance(UpdateSupplierAddressServiceInterface::class, $this->createMock(UpdateSupplierAddressServiceInterface::class));
        $this->app->instance(DeleteSupplierAddressServiceInterface::class, $this->createMock(DeleteSupplierAddressServiceInterface::class));

        $this->app->instance(FindSupplierContactServiceInterface::class, $this->createMock(FindSupplierContactServiceInterface::class));
        $this->app->instance(CreateSupplierContactServiceInterface::class, $this->createMock(CreateSupplierContactServiceInterface::class));
        $this->app->instance(UpdateSupplierContactServiceInterface::class, $this->createMock(UpdateSupplierContactServiceInterface::class));
        $this->app->instance(DeleteSupplierContactServiceInterface::class, $this->createMock(DeleteSupplierContactServiceInterface::class));

        $this->app->instance(FindSupplierProductServiceInterface::class, $this->createMock(FindSupplierProductServiceInterface::class));
        $this->app->instance(CreateSupplierProductServiceInterface::class, $this->createMock(CreateSupplierProductServiceInterface::class));
        $this->app->instance(UpdateSupplierProductServiceInterface::class, $this->createMock(UpdateSupplierProductServiceInterface::class));
        $this->app->instance(DeleteSupplierProductServiceInterface::class, $this->createMock(DeleteSupplierProductServiceInterface::class));

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $tenantConfigManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $tenantConfigManager);

        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturnCallback(
            static function (string $collection, string $column): int {
                if ($collection === 'suppliers' && in_array($column, ['supplier_code', 'user_id'], true)) {
                    return 0;
                }

                return 1;
            }
        );
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app->instance(PresenceVerifierInterface::class, $presenceVerifier);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        $user = new UserModel([
            'id' => 501,
            'tenant_id' => 7,
            'email' => 'supplier.test@example.com',
            'password' => 'secret',
            'first_name' => 'Supplier',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 501);
        $user->setAttribute('tenant_id', 7);

        $this->actingAs($user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));
    }

    public function test_authenticated_supplier_index_returns_success_payload(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildSupplier(id: 11)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findSupplierService
            ->expects($this->once())
            ->method('list')
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/suppliers?tenant_id=7');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 11)
            ->assertJsonPath('data.0.tenant_id', 7)
            ->assertJsonPath('data.0.name', 'Acme Supplies');
    }

    public function test_authenticated_supplier_show_returns_success_payload(): void
    {
        $this->findSupplierService
            ->expects($this->once())
            ->method('find')
            ->with(11)
            ->willReturn($this->buildSupplier(id: 11));

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/suppliers/11');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 11)
            ->assertJsonPath('data.tenant_id', 7)
            ->assertJsonPath('data.name', 'Acme Supplies');
    }

    public function test_authenticated_supplier_store_validates_required_fields(): void
    {
        $this->createSupplierService
            ->expects($this->never())
            ->method('execute');

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/suppliers', []);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name']);
    }

    private function buildSupplier(int $id): Supplier
    {
        return new Supplier(
            tenantId: 7,
            userId: 501,
            name: 'Acme Supplies',
            type: 'company',
            supplierCode: 'SUP-011',
            status: 'active',
            paymentTermsDays: 30,
            id: $id,
            createdAt: new \DateTimeImmutable('2024-01-01'),
            updatedAt: new \DateTimeImmutable('2024-01-01'),
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
