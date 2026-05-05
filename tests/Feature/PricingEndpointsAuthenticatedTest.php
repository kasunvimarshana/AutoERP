<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Pricing\Application\Contracts\CreateCustomerPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\CreatePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\CreateSupplierPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\DeleteCustomerPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\DeletePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\DeleteSupplierPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\FindCustomerPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\FindPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\FindSupplierPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\ResolvePriceServiceInterface;
use Modules\Pricing\Application\Contracts\UpdatePriceListServiceInterface;
use Modules\Pricing\Domain\Entities\CustomerPriceList;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\Entities\SupplierPriceList;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class PricingEndpointsAuthenticatedTest extends TestCase
{
    use RefreshDatabase;

    private static bool $routesCleared = false;

    /** @var FindPriceListServiceInterface&MockObject */
    private FindPriceListServiceInterface $findPriceListService;

    /** @var FindCustomerPriceListServiceInterface&MockObject */
    private FindCustomerPriceListServiceInterface $findCustomerPriceListService;

    /** @var ResolvePriceServiceInterface&MockObject */
    private ResolvePriceServiceInterface $resolvePriceService;

    /** @var FindSupplierPriceListServiceInterface&MockObject */
    private FindSupplierPriceListServiceInterface $findSupplierPriceListService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();

        $this->findPriceListService = $this->createMock(FindPriceListServiceInterface::class);
        $this->findCustomerPriceListService = $this->createMock(FindCustomerPriceListServiceInterface::class);
        $this->findSupplierPriceListService = $this->createMock(FindSupplierPriceListServiceInterface::class);
        $this->resolvePriceService = $this->createMock(ResolvePriceServiceInterface::class);

        $this->app->instance(FindPriceListServiceInterface::class, $this->findPriceListService);
        $this->app->instance(CreatePriceListServiceInterface::class, $this->createMock(CreatePriceListServiceInterface::class));
        $this->app->instance(UpdatePriceListServiceInterface::class, $this->createMock(UpdatePriceListServiceInterface::class));
        $this->app->instance(DeletePriceListServiceInterface::class, $this->createMock(DeletePriceListServiceInterface::class));

        $this->app->instance(FindCustomerPriceListServiceInterface::class, $this->findCustomerPriceListService);
        $this->app->instance(CreateCustomerPriceListServiceInterface::class, $this->createMock(CreateCustomerPriceListServiceInterface::class));
        $this->app->instance(DeleteCustomerPriceListServiceInterface::class, $this->createMock(DeleteCustomerPriceListServiceInterface::class));

        $this->app->instance(FindSupplierPriceListServiceInterface::class, $this->findSupplierPriceListService);
        $this->app->instance(CreateSupplierPriceListServiceInterface::class, $this->createMock(CreateSupplierPriceListServiceInterface::class));
        $this->app->instance(DeleteSupplierPriceListServiceInterface::class, $this->createMock(DeleteSupplierPriceListServiceInterface::class));

        $this->app->instance(ResolvePriceServiceInterface::class, $this->resolvePriceService);

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $tenantConfigManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $tenantConfigManager);

        $user = new UserModel([
            'id' => 811,
            'tenant_id' => 9,
            'email' => 'pricing.test@example.com',
            'password' => 'secret',
            'first_name' => 'Pricing',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 811);
        $user->setAttribute('tenant_id', 9);

        $this->actingAs($user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));

        $this->seedResolveValidationData();
    }

    public function test_authenticated_price_list_index_returns_paginated_payload(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [
                new PriceList(
                    id: 71,
                    tenantId: 9,
                    name: 'Retail Sales',
                    type: 'sales',
                    currencyId: 1,
                    isDefault: true,
                ),
            ],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findPriceListService
            ->expects($this->once())
            ->method('list')
            ->with(
                [
                    'tenant_id' => 9,
                    'name' => 'Retail',
                    'type' => 'sales',
                ],
                15,
                1,
                '-created_at'
            )
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/pricing/price-lists?tenant_id=9&name=Retail&type=sales&sort=-created_at');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 71)
            ->assertJsonPath('data.0.tenant_id', 9)
            ->assertJsonPath('data.0.name', 'Retail Sales')
            ->assertJsonPath('data.0.type', 'sales');
    }

    public function test_authenticated_customer_assignment_index_binds_route_parameter_and_returns_payload(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [
                new CustomerPriceList(
                    id: 91,
                    tenantId: 9,
                    customerId: 41,
                    priceListId: 71,
                    priority: 5,
                ),
            ],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findCustomerPriceListService
            ->expects($this->once())
            ->method('paginateByCustomer')
            ->with(9, 41, 15, 1)
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/pricing/customers/41/price-lists');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 91)
            ->assertJsonPath('data.0.customer_id', 41)
            ->assertJsonPath('data.0.price_list_id', 71)
            ->assertJsonPath('data.0.priority', 5);
    }

    public function test_authenticated_resolve_endpoint_returns_service_payload(): void
    {
        $this->resolvePriceService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function (array $payload): bool {
                return (int) $payload['tenant_id'] === 9
                    && $payload['type'] === 'sales'
                    && (int) $payload['product_id'] === 100
                    && (int) $payload['uom_id'] === 1
                    && (int) $payload['currency_id'] === 1
                    && (int) $payload['customer_id'] === 41;
            }))
            ->willReturn([
                'price_list_id' => 71,
                'price_list_item_id' => 901,
                'base_price' => '100.000000',
                'discount_pct' => '5.000000',
                'unit_price' => '95.000000',
                'total_price' => '190.000000',
            ]);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/pricing/resolve', [
                'tenant_id' => 9,
                'type' => 'sales',
                'product_id' => 100,
                'uom_id' => 1,
                'quantity' => '2.000000',
                'currency_id' => 1,
                'customer_id' => 41,
            ]);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.price_list_id', 71)
            ->assertJsonPath('data.price_list_item_id', 901)
            ->assertJsonPath('data.base_price', '100.000000')
            ->assertJsonPath('data.unit_price', '95.000000')
            ->assertJsonPath('data.total_price', '190.000000');
    }

    public function test_authenticated_supplier_assignment_index_preserves_priority_order(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [
                new SupplierPriceList(
                    id: 101,
                    tenantId: 9,
                    supplierId: 55,
                    priceListId: 80,
                    priority: 10,
                ),
                new SupplierPriceList(
                    id: 102,
                    tenantId: 9,
                    supplierId: 55,
                    priceListId: 81,
                    priority: 3,
                ),
            ],
            total: 2,
            perPage: 15,
            currentPage: 1,
        );

        $this->findSupplierPriceListService
            ->expects($this->once())
            ->method('paginateBySupplier')
            ->with(9, 55, 15, 1)
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/pricing/suppliers/55/price-lists');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 101)
            ->assertJsonPath('data.0.priority', 10)
            ->assertJsonPath('data.1.id', 102)
            ->assertJsonPath('data.1.priority', 3);
    }

    public function test_authenticated_resolve_purchase_requires_supplier_id(): void
    {
        $this->resolvePriceService
            ->expects($this->never())
            ->method('execute');

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/pricing/resolve', [
                'tenant_id' => 9,
                'type' => 'purchase',
                'product_id' => 100,
                'uom_id' => 1,
                'quantity' => '2.000000',
                'currency_id' => 1,
            ]);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['supplier_id']);
    }

    private function clearRoutesCacheOnce(): void
    {
        if (self::$routesCleared) {
            return;
        }

        Artisan::call('route:clear');
        self::$routesCleared = true;
    }

    private function seedResolveValidationData(): void
    {
        DB::table('tenants')->insert([
            'id' => 9,
            'name' => 'Tenant 9',
            'slug' => 'tenant-9',
            'domain' => null,
            'logo_path' => null,
            'database_config' => null,
            'mail_config' => null,
            'cache_config' => null,
            'queue_config' => null,
            'feature_flags' => null,
            'api_keys' => null,
            'settings' => null,
            'plan' => 'free',
            'tenant_plan_id' => null,
            'status' => 'active',
            'active' => true,
            'trial_ends_at' => null,
            'subscription_ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('currencies')->insert([
            'id' => 1,
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('units_of_measure')->insert([
            'id' => 1,
            'tenant_id' => 9,
            'name' => 'Each',
            'symbol' => 'EA',
            'type' => 'unit',
            'is_base' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('products')->insert([
            'id' => 100,
            'tenant_id' => 9,
            'category_id' => null,
            'brand_id' => null,
            'org_unit_id' => null,
            'type' => 'physical',
            'name' => 'Product 100',
            'slug' => 'product-100',
            'sku' => 'SKU-100',
            'description' => null,
            'image_path' => null,
            'base_uom_id' => 1,
            'purchase_uom_id' => null,
            'sales_uom_id' => null,
            'tax_group_id' => null,
            'uom_conversion_factor' => '1.0000000000',
            'is_batch_tracked' => false,
            'is_lot_tracked' => false,
            'is_serial_tracked' => false,
            'valuation_method' => 'fifo',
            'standard_cost' => null,
            'income_account_id' => null,
            'cogs_account_id' => null,
            'inventory_account_id' => null,
            'expense_account_id' => null,
            'is_active' => true,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('customers')->insert([
            'id' => 41,
            'tenant_id' => 9,
            'user_id' => null,
            'org_unit_id' => null,
            'customer_code' => 'CUS-041',
            'name' => 'Customer 41',
            'type' => 'company',
            'tax_number' => null,
            'registration_number' => null,
            'currency_id' => 1,
            'credit_limit' => '0.000000',
            'payment_terms_days' => 30,
            'ar_account_id' => null,
            'status' => 'active',
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }
}
