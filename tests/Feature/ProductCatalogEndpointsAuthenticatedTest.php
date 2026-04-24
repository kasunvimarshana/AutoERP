<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\PresenceVerifierInterface;
use Laravel\Passport\Passport;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Product\Application\Contracts\CreateComboItemServiceInterface;
use Modules\Product\Application\Contracts\CreateProductAttributeGroupServiceInterface;
use Modules\Product\Application\Contracts\CreateProductAttributeServiceInterface;
use Modules\Product\Application\Contracts\CreateProductAttributeValueServiceInterface;
use Modules\Product\Application\Contracts\CreateVariantAttributeServiceInterface;
use Modules\Product\Application\Contracts\DeleteComboItemServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductAttributeGroupServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductAttributeServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductAttributeValueServiceInterface;
use Modules\Product\Application\Contracts\DeleteVariantAttributeServiceInterface;
use Modules\Product\Application\Contracts\FindComboItemServiceInterface;
use Modules\Product\Application\Contracts\FindProductAttributeGroupServiceInterface;
use Modules\Product\Application\Contracts\FindProductAttributeServiceInterface;
use Modules\Product\Application\Contracts\FindProductAttributeValueServiceInterface;
use Modules\Product\Application\Contracts\FindVariantAttributeServiceInterface;
use Modules\Product\Application\Contracts\UpdateComboItemServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductAttributeGroupServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductAttributeServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductAttributeValueServiceInterface;
use Modules\Product\Application\Contracts\UpdateVariantAttributeServiceInterface;
use Modules\Product\Domain\Entities\ComboItem;
use Modules\Product\Domain\Entities\ProductAttribute;
use Modules\Product\Domain\Entities\ProductAttributeGroup;
use Modules\Product\Domain\Entities\ProductAttributeValue;
use Modules\Product\Domain\Entities\VariantAttribute;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class ProductCatalogEndpointsAuthenticatedTest extends TestCase
{
    /** @var FindProductAttributeGroupServiceInterface&MockObject */
    private FindProductAttributeGroupServiceInterface $findProductAttributeGroupService;

    /** @var FindProductAttributeServiceInterface&MockObject */
    private FindProductAttributeServiceInterface $findProductAttributeService;

    /** @var FindProductAttributeValueServiceInterface&MockObject */
    private FindProductAttributeValueServiceInterface $findProductAttributeValueService;

    /** @var FindVariantAttributeServiceInterface&MockObject */
    private FindVariantAttributeServiceInterface $findVariantAttributeService;

    /** @var FindComboItemServiceInterface&MockObject */
    private FindComboItemServiceInterface $findComboItemService;

    /** @var CreateProductAttributeGroupServiceInterface&MockObject */
    private CreateProductAttributeGroupServiceInterface $createProductAttributeGroupService;

    /** @var CreateProductAttributeServiceInterface&MockObject */
    private CreateProductAttributeServiceInterface $createProductAttributeService;

    /** @var CreateProductAttributeValueServiceInterface&MockObject */
    private CreateProductAttributeValueServiceInterface $createProductAttributeValueService;

    /** @var CreateVariantAttributeServiceInterface&MockObject */
    private CreateVariantAttributeServiceInterface $createVariantAttributeService;

    /** @var UpdateProductAttributeGroupServiceInterface&MockObject */
    private UpdateProductAttributeGroupServiceInterface $updateProductAttributeGroupService;

    /** @var DeleteProductAttributeGroupServiceInterface&MockObject */
    private DeleteProductAttributeGroupServiceInterface $deleteProductAttributeGroupService;

    /** @var CreateComboItemServiceInterface&MockObject */
    private CreateComboItemServiceInterface $createComboItemService;

    /** @var UpdateComboItemServiceInterface&MockObject */
    private UpdateComboItemServiceInterface $updateComboItemService;

    /** @var DeleteComboItemServiceInterface&MockObject */
    private DeleteComboItemServiceInterface $deleteComboItemService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->findProductAttributeGroupService = $this->createMock(FindProductAttributeGroupServiceInterface::class);
        $this->findProductAttributeService = $this->createMock(FindProductAttributeServiceInterface::class);
        $this->findProductAttributeValueService = $this->createMock(FindProductAttributeValueServiceInterface::class);
        $this->findVariantAttributeService = $this->createMock(FindVariantAttributeServiceInterface::class);
        $this->findComboItemService = $this->createMock(FindComboItemServiceInterface::class);

        $this->createProductAttributeGroupService = $this->createMock(CreateProductAttributeGroupServiceInterface::class);
        $this->createProductAttributeService = $this->createMock(CreateProductAttributeServiceInterface::class);
        $this->createProductAttributeValueService = $this->createMock(CreateProductAttributeValueServiceInterface::class);
        $this->createVariantAttributeService = $this->createMock(CreateVariantAttributeServiceInterface::class);
        $this->updateProductAttributeGroupService = $this->createMock(UpdateProductAttributeGroupServiceInterface::class);
        $this->deleteProductAttributeGroupService = $this->createMock(DeleteProductAttributeGroupServiceInterface::class);

        $this->createComboItemService = $this->createMock(CreateComboItemServiceInterface::class);
        $this->updateComboItemService = $this->createMock(UpdateComboItemServiceInterface::class);
        $this->deleteComboItemService = $this->createMock(DeleteComboItemServiceInterface::class);

        $this->app->instance(FindProductAttributeGroupServiceInterface::class, $this->findProductAttributeGroupService);
        $this->app->instance(FindProductAttributeServiceInterface::class, $this->findProductAttributeService);
        $this->app->instance(FindProductAttributeValueServiceInterface::class, $this->findProductAttributeValueService);
        $this->app->instance(FindVariantAttributeServiceInterface::class, $this->findVariantAttributeService);
        $this->app->instance(FindComboItemServiceInterface::class, $this->findComboItemService);

        $this->app->instance(CreateProductAttributeGroupServiceInterface::class, $this->createProductAttributeGroupService);
        $this->app->instance(CreateProductAttributeServiceInterface::class, $this->createProductAttributeService);
        $this->app->instance(CreateProductAttributeValueServiceInterface::class, $this->createProductAttributeValueService);
        $this->app->instance(CreateVariantAttributeServiceInterface::class, $this->createVariantAttributeService);
        $this->app->instance(UpdateProductAttributeGroupServiceInterface::class, $this->updateProductAttributeGroupService);
        $this->app->instance(DeleteProductAttributeGroupServiceInterface::class, $this->deleteProductAttributeGroupService);

        $this->app->instance(CreateComboItemServiceInterface::class, $this->createComboItemService);
        $this->app->instance(UpdateComboItemServiceInterface::class, $this->updateComboItemService);
        $this->app->instance(DeleteComboItemServiceInterface::class, $this->deleteComboItemService);

        $this->app->instance(UpdateProductAttributeServiceInterface::class, $this->createMock(UpdateProductAttributeServiceInterface::class));
        $this->app->instance(DeleteProductAttributeServiceInterface::class, $this->createMock(DeleteProductAttributeServiceInterface::class));

        $this->app->instance(UpdateProductAttributeValueServiceInterface::class, $this->createMock(UpdateProductAttributeValueServiceInterface::class));
        $this->app->instance(DeleteProductAttributeValueServiceInterface::class, $this->createMock(DeleteProductAttributeValueServiceInterface::class));

        $this->app->instance(UpdateVariantAttributeServiceInterface::class, $this->createMock(UpdateVariantAttributeServiceInterface::class));
        $this->app->instance(DeleteVariantAttributeServiceInterface::class, $this->createMock(DeleteVariantAttributeServiceInterface::class));

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
            'id' => 291,
            'tenant_id' => 9,
            'email' => 'product.catalog.test@example.com',
            'password' => 'secret',
            'first_name' => 'Product',
            'last_name' => 'CatalogTester',
        ]);
        $user->setAttribute('id', 291);
        $user->setAttribute('tenant_id', 9);

        $this->actingAs($user, 'api');
    }

    public function test_authenticated_catalog_index_endpoints_return_success_payloads(): void
    {
        $this->findProductAttributeGroupService
            ->expects($this->once())
            ->method('list')
            ->with(['tenant_id' => 9, 'name' => 'Visual'], 15, 1, '-created_at')
            ->willReturn(new LengthAwarePaginator([$this->buildProductAttributeGroup(701)], 1, 15, 1));

        $this->findProductAttributeService
            ->expects($this->once())
            ->method('list')
            ->with(['tenant_id' => 9, 'name' => 'Color'], 15, 1, '-created_at')
            ->willReturn(new LengthAwarePaginator([$this->buildProductAttribute(702)], 1, 15, 1));

        $this->findProductAttributeValueService
            ->expects($this->once())
            ->method('list')
            ->with(['tenant_id' => 9, 'attribute_id' => 702, 'value' => 'Blue'], 15, 1, '-created_at')
            ->willReturn(new LengthAwarePaginator([$this->buildProductAttributeValue(703)], 1, 15, 1));

        $this->findVariantAttributeService
            ->expects($this->once())
            ->method('list')
            ->with(['tenant_id' => 9, 'product_id' => 801], 15, 1, '-created_at')
            ->willReturn(new LengthAwarePaginator([$this->buildVariantAttribute(704)], 1, 15, 1));

        $this->findComboItemService
            ->expects($this->once())
            ->method('list')
            ->with(['tenant_id' => 9, 'combo_product_id' => 901], 15, 1, '-created_at')
            ->willReturn(new LengthAwarePaginator([$this->buildComboItem(705)], 1, 15, 1));

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-attribute-groups?tenant_id=9&name=Visual&sort=-created_at')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 701);

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-attributes?tenant_id=9&name=Color&sort=-created_at')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 702);

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-attribute-values?tenant_id=9&attribute_id=702&value=Blue&sort=-created_at')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 703);

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/variant-attributes?tenant_id=9&product_id=801&sort=-created_at')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 704);

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/combo-items?tenant_id=9&combo_product_id=901&sort=-created_at')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 705);
    }

    public function test_authenticated_catalog_show_endpoints_return_success_payloads(): void
    {
        $this->findProductAttributeGroupService
            ->expects($this->once())
            ->method('find')
            ->with(711)
            ->willReturn($this->buildProductAttributeGroup(711));

        $this->findProductAttributeService
            ->expects($this->once())
            ->method('find')
            ->with(712)
            ->willReturn($this->buildProductAttribute(712));

        $this->findProductAttributeValueService
            ->expects($this->once())
            ->method('find')
            ->with(713)
            ->willReturn($this->buildProductAttributeValue(713));

        $this->findVariantAttributeService
            ->expects($this->once())
            ->method('find')
            ->with(714)
            ->willReturn($this->buildVariantAttribute(714));

        $this->findComboItemService
            ->expects($this->once())
            ->method('find')
            ->with(715)
            ->willReturn($this->buildComboItem(715));

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-attribute-groups/711')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 711);

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-attributes/712')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 712);

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-attribute-values/713')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 713);

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/variant-attributes/714')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 714);

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/combo-items/715')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 715);
    }

    public function test_authenticated_write_flows_for_attribute_groups_and_combo_items(): void
    {
        $this->createProductAttributeGroupService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static fn (array $payload): bool =>
                (int) ($payload['tenant_id'] ?? 0) === 9 && ($payload['name'] ?? null) === 'Dimensions'
            ))
            ->willReturn($this->buildProductAttributeGroup(721, 'Dimensions'));

        $this->findProductAttributeGroupService
            ->expects($this->exactly(2))
            ->method('find')
            ->willReturnCallback(function (int $id): ProductAttributeGroup {
                $this->assertSame(722, $id);

                return $this->buildProductAttributeGroup(722, 'Dimensions');
            });

        $this->updateProductAttributeGroupService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static fn (array $payload): bool =>
                (int) ($payload['id'] ?? 0) === 722 && ($payload['name'] ?? null) === 'Physical Dimensions'
            ))
            ->willReturn($this->buildProductAttributeGroup(722, 'Physical Dimensions'));

        $this->deleteProductAttributeGroupService
            ->expects($this->once())
            ->method('execute')
            ->with(['id' => 722])
            ->willReturn(true);

        $this->createComboItemService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static fn (array $payload): bool =>
                (int) ($payload['combo_product_id'] ?? 0) === 901
                && (int) ($payload['component_product_id'] ?? 0) === 902
            ))
            ->willReturn($this->buildComboItem(731));

        $this->findComboItemService
            ->expects($this->exactly(2))
            ->method('find')
            ->willReturnCallback(function (int $id): ComboItem {
                $this->assertSame(732, $id);

                return $this->buildComboItem(732);
            });

        $this->updateComboItemService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static fn (array $payload): bool =>
                (int) ($payload['id'] ?? 0) === 732
                && (string) ($payload['quantity'] ?? '') === '3.000000'
            ))
            ->willReturn($this->buildComboItem(732, '3.000000'));

        $this->deleteComboItemService
            ->expects($this->once())
            ->method('execute')
            ->with(['id' => 732])
            ->willReturn(true);

        $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/product-attribute-groups', [
                'tenant_id' => 9,
                'name' => 'Dimensions',
            ])
            ->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 721);

        $this->withHeader('X-Tenant-ID', '9')
            ->putJson('/api/product-attribute-groups/722', [
                'tenant_id' => 9,
                'name' => 'Physical Dimensions',
            ])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 722)
            ->assertJsonPath('data.name', 'Physical Dimensions');

        $this->withHeader('X-Tenant-ID', '9')
            ->deleteJson('/api/product-attribute-groups/722')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('message', 'Product attribute group deleted successfully');

        $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/combo-items', [
                'tenant_id' => 9,
                'combo_product_id' => 901,
                'component_product_id' => 902,
                'component_variant_id' => 903,
                'quantity' => '2.500000',
                'uom_id' => 904,
                'metadata' => ['source' => 'feature-test'],
            ])
            ->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 731);

        $this->withHeader('X-Tenant-ID', '9')
            ->putJson('/api/combo-items/732', [
                'tenant_id' => 9,
                'combo_product_id' => 901,
                'component_product_id' => 902,
                'component_variant_id' => 903,
                'quantity' => '3.000000',
                'uom_id' => 904,
                'metadata' => ['source' => 'feature-test'],
            ])
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 732)
            ->assertJsonPath('data.quantity', '3.000000');

        $this->withHeader('X-Tenant-ID', '9')
            ->deleteJson('/api/combo-items/732')
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('message', 'Combo item deleted successfully');
    }

    public function test_authenticated_catalog_index_returns_forbidden_when_authorization_fails(): void
    {
        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(false);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $this->findProductAttributeGroupService
            ->expects($this->never())
            ->method('list');

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-attribute-groups')
            ->assertStatus(HttpResponse::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    public function test_catalog_store_endpoints_return_validation_errors_for_invalid_payloads(): void
    {
        $this->createProductAttributeGroupService
            ->expects($this->never())
            ->method('execute');
        $this->createProductAttributeService
            ->expects($this->never())
            ->method('execute');
        $this->createProductAttributeValueService
            ->expects($this->never())
            ->method('execute');
        $this->createVariantAttributeService
            ->expects($this->never())
            ->method('execute');
        $this->createComboItemService
            ->expects($this->never())
            ->method('execute');

        $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/product-attribute-groups', [
                'tenant_id' => 9,
            ])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name']);

        $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/product-attributes', [
                'type' => 'select',
            ])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name']);

        $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/product-attribute-values', [
                'tenant_id' => 9,
                'value' => 'Blue',
            ])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['attribute_id']);

        $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/variant-attributes', [
                'tenant_id' => 9,
                'attribute_id' => 702,
                'is_required' => true,
            ])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['product_id']);

        $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/combo-items', [
                'tenant_id' => 9,
                'combo_product_id' => 901,
                'component_product_id' => 902,
                'uom_id' => 904,
            ])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_catalog_show_endpoints_return_not_found_when_entity_missing(): void
    {
        $this->findProductAttributeGroupService
            ->expects($this->once())
            ->method('find')
            ->with(9991)
            ->willReturn(null);

        $this->findProductAttributeService
            ->expects($this->once())
            ->method('find')
            ->with(9993)
            ->willReturn(null);

        $this->findProductAttributeValueService
            ->expects($this->once())
            ->method('find')
            ->with(9994)
            ->willReturn(null);

        $this->findVariantAttributeService
            ->expects($this->once())
            ->method('find')
            ->with(9995)
            ->willReturn(null);

        $this->findComboItemService
            ->expects($this->once())
            ->method('find')
            ->with(9992)
            ->willReturn(null);

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-attribute-groups/9991')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/combo-items/9992')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-attributes/9993')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-attribute-values/9994')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);

        $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/variant-attributes/9995')
            ->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_catalog_update_endpoints_return_validation_errors_for_invalid_payloads(): void
    {
        $updateProductAttributeService = $this->createMock(UpdateProductAttributeServiceInterface::class);
        $updateProductAttributeService
            ->expects($this->never())
            ->method('execute');
        $this->app->instance(UpdateProductAttributeServiceInterface::class, $updateProductAttributeService);

        $updateProductAttributeValueService = $this->createMock(UpdateProductAttributeValueServiceInterface::class);
        $updateProductAttributeValueService
            ->expects($this->never())
            ->method('execute');
        $this->app->instance(UpdateProductAttributeValueServiceInterface::class, $updateProductAttributeValueService);

        $updateVariantAttributeService = $this->createMock(UpdateVariantAttributeServiceInterface::class);
        $updateVariantAttributeService
            ->expects($this->never())
            ->method('execute');
        $this->app->instance(UpdateVariantAttributeServiceInterface::class, $updateVariantAttributeService);

        $this->updateProductAttributeGroupService
            ->expects($this->never())
            ->method('execute');

        $this->updateComboItemService
            ->expects($this->never())
            ->method('execute');

        $this->withHeader('X-Tenant-ID', '9')
            ->putJson('/api/product-attribute-groups/722', [
                'tenant_id' => 9,
            ])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name']);

        $this->withHeader('X-Tenant-ID', '9')
            ->putJson('/api/product-attributes/712', [
                'tenant_id' => 9,
                'name' => 'Color',
            ])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['type']);

        $this->withHeader('X-Tenant-ID', '9')
            ->putJson('/api/product-attribute-values/713', [
                'tenant_id' => 9,
                'attribute_id' => 702,
            ])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['value']);

        $this->withHeader('X-Tenant-ID', '9')
            ->putJson('/api/variant-attributes/714', [
                'tenant_id' => 9,
                'product_id' => 801,
            ])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['attribute_id']);

        $this->withHeader('X-Tenant-ID', '9')
            ->putJson('/api/combo-items/732', [
                'tenant_id' => 9,
                'combo_product_id' => 901,
                'component_product_id' => 902,
                'uom_id' => 904,
            ])
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['quantity']);
    }

    private function buildProductAttributeGroup(int $id, string $name = 'Visual'): ProductAttributeGroup
    {
        return new ProductAttributeGroup(
            tenantId: 9,
            name: $name,
            id: $id,
        );
    }

    private function buildProductAttribute(int $id): ProductAttribute
    {
        return new ProductAttribute(
            tenantId: 9,
            name: 'Color',
            type: 'select',
            isRequired: true,
            groupId: 701,
            id: $id,
        );
    }

    private function buildProductAttributeValue(int $id): ProductAttributeValue
    {
        return new ProductAttributeValue(
            attributeId: 702,
            value: 'Blue',
            sortOrder: 1,
            tenantId: 9,
            id: $id,
        );
    }

    private function buildVariantAttribute(int $id): VariantAttribute
    {
        return new VariantAttribute(
            tenantId: 9,
            productId: 801,
            attributeId: 702,
            isRequired: true,
            isVariationAxis: true,
            displayOrder: 1,
            id: $id,
        );
    }

    private function buildComboItem(int $id, string $quantity = '2.500000'): ComboItem
    {
        return new ComboItem(
            comboProductId: 901,
            componentProductId: 902,
            quantity: $quantity,
            uomId: 904,
            tenantId: 9,
            componentVariantId: 903,
            metadata: ['source' => 'feature-test'],
            id: $id,
        );
    }
}
