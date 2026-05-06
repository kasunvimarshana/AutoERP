<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Supplier\Application\Contracts\CreateSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\CreateSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\CreateSupplierProductServiceInterface;
use Modules\Supplier\Application\Contracts\CreateSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierProductServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\FindSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\FindSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\FindSupplierProductServiceInterface;
use Modules\Supplier\Application\Contracts\FindSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierProductServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierServiceInterface;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\Entities\SupplierAddress;
use Modules\Supplier\Domain\Entities\SupplierContact;
use Modules\Supplier\Domain\Entities\SupplierProduct;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

class SupplierEndpointsAuthenticatedTest extends TestCase
{
    private UserModel $authUser;

    private Supplier $supplier;

    private SupplierAddress $address;

    private SupplierContact $contact;

    private SupplierProduct $supplierProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authUser = new UserModel([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'hashed',
        ]);
        $this->authUser->setAttribute('id', 99);
        $this->authUser->setAttribute('tenant_id', 1);

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturnCallback(
            static function (string $collection, string $column): int {
                if (
                    ($collection === 'suppliers' && in_array($column, ['supplier_code', 'user_id'], true))
                    || ($collection === 'users' && $column === 'email')
                ) {
                    return 0;
                }

                return 1;
            }
        );
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app->instance(PresenceVerifierInterface::class, $presenceVerifier);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        $this->supplier = new Supplier(
            tenantId: 1,
            userId: 2,
            name: 'Test Supplier',
            type: 'company',
            supplierCode: 'SUP-001',
            currencyId: 1,
            paymentTermsDays: 30,
            status: 'active',
            id: 1,
        );

        $this->address = new SupplierAddress(
            tenantId: 1,
            supplierId: 1,
            type: 'billing',
            addressLine1: '123 Main Street',
            city: 'Colombo',
            postalCode: '10000',
            countryId: 1,
            label: 'HQ',
            id: 10,
        );

        $this->contact = new SupplierContact(
            tenantId: 1,
            supplierId: 1,
            name: 'Jane Doe',
            role: 'Manager',
            email: 'jane@example.com',
            phone: '+94 77 123 4567',
            isPrimary: true,
            id: 20,
        );

        $this->supplierProduct = new SupplierProduct(
            tenantId: 1,
            supplierId: 1,
            productId: 100,
            supplierSku: 'SUP-SKU-100',
            leadTimeDays: 7,
            minOrderQty: '1.000000',
            isPreferred: true,
            lastPurchasePrice: '99.500000',
            id: 30,
        );

        $findSupplierService = $this->createMock(FindSupplierServiceInterface::class);
        $findSupplierService->method('find')->willReturn($this->supplier);
        $findSupplierService->method('list')->willReturn($this->makePaginator([$this->supplier]));
        $this->app->instance(FindSupplierServiceInterface::class, $findSupplierService);

        $createSupplierService = $this->createMock(CreateSupplierServiceInterface::class);
        $createSupplierService->method('execute')->willReturn($this->supplier);
        $this->app->instance(CreateSupplierServiceInterface::class, $createSupplierService);

        $updateSupplierService = $this->createMock(UpdateSupplierServiceInterface::class);
        $updateSupplierService->method('execute')->willReturn($this->supplier);
        $this->app->instance(UpdateSupplierServiceInterface::class, $updateSupplierService);

        $deleteSupplierService = $this->createMock(DeleteSupplierServiceInterface::class);
        $this->app->instance(DeleteSupplierServiceInterface::class, $deleteSupplierService);

        $findAddressService = $this->createMock(FindSupplierAddressServiceInterface::class);
        $findAddressService->method('find')->willReturn($this->address);
        $findAddressService->method('list')->willReturn($this->makePaginator([$this->address]));
        $findAddressService->method('paginateBySupplier')->willReturn($this->makePaginator([$this->address]));
        $this->app->instance(FindSupplierAddressServiceInterface::class, $findAddressService);

        $createAddressService = $this->createMock(CreateSupplierAddressServiceInterface::class);
        $createAddressService->method('execute')->willReturn($this->address);
        $this->app->instance(CreateSupplierAddressServiceInterface::class, $createAddressService);

        $updateAddressService = $this->createMock(UpdateSupplierAddressServiceInterface::class);
        $updateAddressService->method('execute')->willReturn($this->address);
        $this->app->instance(UpdateSupplierAddressServiceInterface::class, $updateAddressService);

        $deleteAddressService = $this->createMock(DeleteSupplierAddressServiceInterface::class);
        $this->app->instance(DeleteSupplierAddressServiceInterface::class, $deleteAddressService);

        $findContactService = $this->createMock(FindSupplierContactServiceInterface::class);
        $findContactService->method('find')->willReturn($this->contact);
        $findContactService->method('list')->willReturn($this->makePaginator([$this->contact]));
        $findContactService->method('paginateBySupplier')->willReturn($this->makePaginator([$this->contact]));
        $this->app->instance(FindSupplierContactServiceInterface::class, $findContactService);

        $createContactService = $this->createMock(CreateSupplierContactServiceInterface::class);
        $createContactService->method('execute')->willReturn($this->contact);
        $this->app->instance(CreateSupplierContactServiceInterface::class, $createContactService);

        $updateContactService = $this->createMock(UpdateSupplierContactServiceInterface::class);
        $updateContactService->method('execute')->willReturn($this->contact);
        $this->app->instance(UpdateSupplierContactServiceInterface::class, $updateContactService);

        $deleteContactService = $this->createMock(DeleteSupplierContactServiceInterface::class);
        $this->app->instance(DeleteSupplierContactServiceInterface::class, $deleteContactService);

        $findProductService = $this->createMock(FindSupplierProductServiceInterface::class);
        $findProductService->method('find')->willReturn($this->supplierProduct);
        $findProductService->method('list')->willReturn($this->makePaginator([$this->supplierProduct]));
        $findProductService->method('paginateBySupplier')->willReturn($this->makePaginator([$this->supplierProduct]));
        $this->app->instance(FindSupplierProductServiceInterface::class, $findProductService);

        $createProductService = $this->createMock(CreateSupplierProductServiceInterface::class);
        $createProductService->method('execute')->willReturn($this->supplierProduct);
        $this->app->instance(CreateSupplierProductServiceInterface::class, $createProductService);

        $updateProductService = $this->createMock(UpdateSupplierProductServiceInterface::class);
        $updateProductService->method('execute')->willReturn($this->supplierProduct);
        $this->app->instance(UpdateSupplierProductServiceInterface::class, $updateProductService);

        $deleteProductService = $this->createMock(DeleteSupplierProductServiceInterface::class);
        $this->app->instance(DeleteSupplierProductServiceInterface::class, $deleteProductService);
    }

    private function actingAsUser(): static
    {
        return $this->withHeader('X-Tenant-ID', '1')->actingAs(
            $this->authUser,
            (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api'))
        );
    }

    /**
     * @param  array<int, mixed>  $items
     */
    private function makePaginator(array $items): LengthAwarePaginator
    {
        return new LengthAwarePaginator($items, count($items), 15, 1);
    }

    public function test_index_returns_paginated_suppliers(): void
    {
        $response = $this->actingAsUser()->getJson('/api/suppliers');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_show_returns_supplier_resource(): void
    {
        $response = $this->actingAsUser()->getJson('/api/suppliers/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_store_creates_supplier(): void
    {
        $response = $this->actingAsUser()->postJson('/api/suppliers', [
            'tenant_id' => 1,
            'user_id' => 2,
            'supplier_code' => 'SUP-001',
            'name' => 'Test Supplier',
            'type' => 'company',
            'currency_id' => 1,
            'payment_terms_days' => 30,
            'status' => 'active',
        ]);

        $response->assertCreated()->assertJsonPath('data.id', 1);
    }

    public function test_update_modifies_supplier(): void
    {
        $response = $this->actingAsUser()->putJson('/api/suppliers/1', [
            'tenant_id' => 1,
            'name' => 'Updated Supplier',
            'type' => 'company',
            'currency_id' => 1,
            'payment_terms_days' => 45,
            'status' => 'active',
            'row_version' => 1,
        ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_destroy_deletes_supplier(): void
    {
        $response = $this->actingAsUser()->deleteJson('/api/suppliers/1');

        $response->assertOk()->assertJsonPath('message', 'Supplier deleted successfully');
    }

    public function test_address_index_returns_supplier_addresses(): void
    {
        $response = $this->actingAsUser()->getJson('/api/suppliers/1/addresses');

        $response->assertOk()->assertJsonPath('data.0.id', 10);
    }

    public function test_address_store_creates_supplier_address(): void
    {
        $response = $this->actingAsUser()->postJson('/api/suppliers/1/addresses', [
            'type' => 'billing',
            'label' => 'HQ',
            'address_line1' => '123 Main Street',
            'city' => 'Colombo',
            'postal_code' => '10000',
            'country_id' => 1,
            'is_default' => true,
        ]);

        $response->assertCreated()->assertJsonPath('data.id', 10);
    }

    public function test_address_update_modifies_supplier_address(): void
    {
        $response = $this->actingAsUser()->putJson('/api/suppliers/1/addresses/10', [
            'type' => 'billing',
            'label' => 'Head Office',
            'address_line1' => '123 Main Street',
            'city' => 'Colombo',
            'postal_code' => '10000',
            'country_id' => 1,
            'is_default' => true,
        ]);

        $response->assertOk()->assertJsonPath('data.id', 10);
    }

    public function test_address_destroy_deletes_supplier_address(): void
    {
        $response = $this->actingAsUser()->deleteJson('/api/suppliers/1/addresses/10');

        $response->assertOk()->assertJsonPath('message', 'Supplier address deleted successfully');
    }

    public function test_contact_index_returns_supplier_contacts(): void
    {
        $response = $this->actingAsUser()->getJson('/api/suppliers/1/contacts');

        $response->assertOk()->assertJsonPath('data.0.id', 20);
    }

    public function test_contact_store_creates_supplier_contact(): void
    {
        $response = $this->actingAsUser()->postJson('/api/suppliers/1/contacts', [
            'name' => 'Jane Doe',
            'role' => 'Manager',
            'email' => 'jane@example.com',
            'phone' => '+94 77 123 4567',
            'is_primary' => true,
        ]);

        $response->assertCreated()->assertJsonPath('data.id', 20);
    }

    public function test_contact_update_modifies_supplier_contact(): void
    {
        $response = $this->actingAsUser()->putJson('/api/suppliers/1/contacts/20', [
            'name' => 'Jane Doe Updated',
            'role' => 'Procurement Manager',
            'email' => 'jane.updated@example.com',
            'phone' => '+94 77 123 9999',
            'is_primary' => true,
        ]);

        $response->assertOk()->assertJsonPath('data.id', 20);
    }

    public function test_contact_destroy_deletes_supplier_contact(): void
    {
        $response = $this->actingAsUser()->deleteJson('/api/suppliers/1/contacts/20');

        $response->assertOk()->assertJsonPath('message', 'Supplier contact deleted successfully');
    }

    public function test_product_index_returns_supplier_products(): void
    {
        $response = $this->actingAsUser()->getJson('/api/suppliers/1/products');

        $response->assertOk()->assertJsonPath('data.0.id', 30);
    }

    public function test_product_store_creates_supplier_product(): void
    {
        $response = $this->actingAsUser()->postJson('/api/suppliers/1/products', [
            'product_id' => 100,
            'supplier_sku' => 'SUP-SKU-100',
            'lead_time_days' => 7,
            'min_order_qty' => 1,
            'is_preferred' => true,
            'last_purchase_price' => 99.5,
        ]);

        $response->assertCreated()->assertJsonPath('data.id', 30);
    }

    public function test_product_update_modifies_supplier_product(): void
    {
        $response = $this->actingAsUser()->putJson('/api/suppliers/1/products/30', [
            'product_id' => 100,
            'supplier_sku' => 'SUP-SKU-100-UPDATED',
            'lead_time_days' => 10,
            'min_order_qty' => 2,
            'is_preferred' => false,
            'last_purchase_price' => 95.0,
        ]);

        $response->assertOk()->assertJsonPath('data.id', 30);
    }

    public function test_product_destroy_deletes_supplier_product(): void
    {
        $response = $this->actingAsUser()->deleteJson('/api/suppliers/1/products/30');

        $response->assertOk()->assertJsonPath('message', 'Supplier product deleted successfully');
    }
}
