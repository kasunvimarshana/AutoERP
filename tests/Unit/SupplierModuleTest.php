<?php

namespace Tests\Unit;

use Modules\Supplier\Application\Contracts\CreateSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\FindSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierServiceInterface;
use Modules\Supplier\Application\DTOs\SupplierData;
use Modules\Supplier\Application\Services\CreateSupplierService;
use Modules\Supplier\Application\Services\DeleteSupplierService;
use Modules\Supplier\Application\Services\FindSupplierService;
use Modules\Supplier\Application\Services\UpdateSupplierService;
use Modules\Supplier\Application\UseCases\CreateSupplier;
use Modules\Supplier\Application\UseCases\DeleteSupplier;
use Modules\Supplier\Application\UseCases\GetSupplier;
use Modules\Supplier\Application\UseCases\ListSuppliers;
use Modules\Supplier\Application\UseCases\UpdateSupplier;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\Events\SupplierCreated;
use Modules\Supplier\Domain\Events\SupplierDeleted;
use Modules\Supplier\Domain\Events\SupplierUpdated;
use Modules\Supplier\Domain\Exceptions\SupplierNotFoundException;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;
use Modules\Supplier\Infrastructure\Http\Controllers\SupplierController;
use Modules\Supplier\Infrastructure\Http\Requests\StoreSupplierRequest;
use Modules\Supplier\Infrastructure\Http\Requests\UpdateSupplierRequest;
use Modules\Supplier\Infrastructure\Http\Resources\SupplierCollection;
use Modules\Supplier\Infrastructure\Http\Resources\SupplierResource;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierRepository;
use Modules\Supplier\Infrastructure\Providers\SupplierServiceProvider;
use PHPUnit\Framework\TestCase;

class SupplierModuleTest extends TestCase
{
    // ── Domain Entities ───────────────────────────────────────────────────────

    public function test_supplier_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(Supplier::class));
    }

    public function test_supplier_entity_can_be_constructed(): void
    {
        $supplier = new Supplier(
            tenantId: 1,
            name: 'Acme Supplies Ltd',
            code: 'SUP-001',
        );

        $this->assertSame(1, $supplier->getTenantId());
        $this->assertSame('Acme Supplies Ltd', $supplier->getName());
        $this->assertSame('SUP-001', $supplier->getCode());
        $this->assertSame('active', $supplier->getStatus());
        $this->assertSame('other', $supplier->getType());
        $this->assertSame('USD', $supplier->getCurrency());
        $this->assertNull($supplier->getUserId());
        $this->assertNull($supplier->getEmail());
        $this->assertNull($supplier->getPhone());
        $this->assertNull($supplier->getAddress());
        $this->assertNull($supplier->getContactPerson());
        $this->assertNull($supplier->getPaymentTerms());
        $this->assertNull($supplier->getTaxNumber());
        $this->assertNull($supplier->getAttributes());
        $this->assertNull($supplier->getMetadata());
        $this->assertNull($supplier->getId());
    }

    public function test_supplier_entity_with_all_fields(): void
    {
        $supplier = new Supplier(
            tenantId: 2,
            name: 'Global Distributor',
            code: 'GD-001',
            userId: 5,
            email: 'info@global.example.com',
            phone: '+1-555-0200',
            address: ['street' => '123 Main St', 'city' => 'Springfield'],
            contactPerson: ['name' => 'John Doe', 'phone' => '+1-555-0201'],
            paymentTerms: 'net30',
            currency: 'EUR',
            taxNumber: 'TAX-999',
            status: 'inactive',
            type: 'distributor',
            attributes: ['tier' => 'gold'],
            metadata: ['source' => 'import'],
            id: 42,
        );

        $this->assertSame(42, $supplier->getId());
        $this->assertSame(2, $supplier->getTenantId());
        $this->assertSame(5, $supplier->getUserId());
        $this->assertSame('Global Distributor', $supplier->getName());
        $this->assertSame('GD-001', $supplier->getCode());
        $this->assertSame('info@global.example.com', $supplier->getEmail());
        $this->assertSame('+1-555-0200', $supplier->getPhone());
        $this->assertSame(['street' => '123 Main St', 'city' => 'Springfield'], $supplier->getAddress());
        $this->assertSame(['name' => 'John Doe', 'phone' => '+1-555-0201'], $supplier->getContactPerson());
        $this->assertSame('net30', $supplier->getPaymentTerms());
        $this->assertSame('EUR', $supplier->getCurrency());
        $this->assertSame('TAX-999', $supplier->getTaxNumber());
        $this->assertSame('inactive', $supplier->getStatus());
        $this->assertSame('distributor', $supplier->getType());
        $this->assertSame(['tier' => 'gold'], $supplier->getAttributes());
        $this->assertSame(['source' => 'import'], $supplier->getMetadata());
    }

    public function test_supplier_entity_update_details(): void
    {
        $supplier = new Supplier(tenantId: 1, name: 'Old Name', code: 'OLD-001');

        $supplier->updateDetails(
            name: 'New Name',
            code: 'NEW-001',
            userId: 10,
            email: 'new@example.com',
            phone: '+1-555-9999',
            address: ['city' => 'Boston'],
            contactPerson: ['name' => 'Jane'],
            paymentTerms: 'net60',
            currency: 'GBP',
            taxNumber: 'TAX-001',
            type: 'manufacturer',
            attributes: ['certified' => true],
            metadata: ['updated' => true],
        );

        $this->assertSame('New Name', $supplier->getName());
        $this->assertSame('NEW-001', $supplier->getCode());
        $this->assertSame(10, $supplier->getUserId());
        $this->assertSame('new@example.com', $supplier->getEmail());
        $this->assertSame('+1-555-9999', $supplier->getPhone());
        $this->assertSame(['city' => 'Boston'], $supplier->getAddress());
        $this->assertSame(['name' => 'Jane'], $supplier->getContactPerson());
        $this->assertSame('net60', $supplier->getPaymentTerms());
        $this->assertSame('GBP', $supplier->getCurrency());
        $this->assertSame('TAX-001', $supplier->getTaxNumber());
        $this->assertSame('manufacturer', $supplier->getType());
        $this->assertSame(['certified' => true], $supplier->getAttributes());
        $this->assertSame(['updated' => true], $supplier->getMetadata());
    }

    public function test_supplier_entity_activate_deactivate(): void
    {
        $supplier = new Supplier(tenantId: 1, name: 'Test', code: 'T-001', status: 'inactive');
        $this->assertFalse($supplier->isActive());

        $supplier->activate();
        $this->assertTrue($supplier->isActive());
        $this->assertSame('active', $supplier->getStatus());

        $supplier->deactivate();
        $this->assertFalse($supplier->isActive());
        $this->assertSame('inactive', $supplier->getStatus());
    }

    public function test_supplier_entity_has_user_access(): void
    {
        $supplier = new Supplier(tenantId: 1, name: 'Test', code: 'T-001');
        $this->assertFalse($supplier->hasUserAccess());

        $supplierWithUser = new Supplier(tenantId: 1, name: 'Test', code: 'T-001', userId: 7);
        $this->assertTrue($supplierWithUser->hasUserAccess());
    }

    // ── Domain Events ─────────────────────────────────────────────────────────

    public function test_all_supplier_event_classes_exist(): void
    {
        $this->assertTrue(class_exists(SupplierCreated::class));
        $this->assertTrue(class_exists(SupplierUpdated::class));
        $this->assertTrue(class_exists(SupplierDeleted::class));
    }

    public function test_supplier_created_event_can_be_instantiated(): void
    {
        $supplier = new Supplier(tenantId: 1, name: 'Test', code: 'T-001', id: 1);
        $event = new SupplierCreated($supplier);

        $this->assertSame($supplier, $event->supplier);
        $this->assertSame(1, $event->tenantId);
    }

    public function test_supplier_updated_event_can_be_instantiated(): void
    {
        $supplier = new Supplier(tenantId: 2, name: 'Updated', code: 'U-001', id: 3);
        $event = new SupplierUpdated($supplier);

        $this->assertSame($supplier, $event->supplier);
        $this->assertSame(2, $event->tenantId);
    }

    public function test_supplier_deleted_event_can_be_instantiated(): void
    {
        $event = new SupplierDeleted(supplierId: 7, tenantId: 3);

        $this->assertSame(7, $event->supplierId);
        $this->assertSame(3, $event->tenantId);
    }

    public function test_supplier_created_event_broadcast_with(): void
    {
        $supplier = new Supplier(tenantId: 1, name: 'My Supplier', code: 'SUP-001', status: 'active', id: 1);
        $event = new SupplierCreated($supplier);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertArrayHasKey('code', $payload);
        $this->assertArrayHasKey('status', $payload);
        $this->assertArrayHasKey('tenantId', $payload);
    }

    // ── Domain Exceptions ─────────────────────────────────────────────────────

    public function test_supplier_exception_class_exists(): void
    {
        $this->assertTrue(class_exists(SupplierNotFoundException::class));
    }

    public function test_supplier_not_found_exception_message(): void
    {
        $e = new SupplierNotFoundException(42);
        $this->assertStringContainsString('Supplier', $e->getMessage());
        $this->assertStringContainsString('42', $e->getMessage());
    }

    // ── Domain Repository Interfaces ─────────────────────────────────────────

    public function test_supplier_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(SupplierRepositoryInterface::class));
    }

    public function test_supplier_repository_interface_has_required_methods(): void
    {
        $reflection = new \ReflectionClass(SupplierRepositoryInterface::class);
        $this->assertTrue($reflection->hasMethod('findByCode'));
        $this->assertTrue($reflection->hasMethod('findByTenant'));
        $this->assertTrue($reflection->hasMethod('findByUserId'));
        $this->assertTrue($reflection->hasMethod('save'));
    }

    // ── Application DTOs ─────────────────────────────────────────────────────

    public function test_supplier_dto_class_exists(): void
    {
        $this->assertTrue(class_exists(SupplierData::class));
    }

    public function test_supplier_data_dto_from_array(): void
    {
        $dto = SupplierData::fromArray([
            'tenant_id'   => 1,
            'name'        => 'Test Supplier',
            'code'        => 'TS-001',
            'email'       => 'test@example.com',
            'status'      => 'active',
            'type'        => 'manufacturer',
            'currency'    => 'USD',
        ]);

        $this->assertSame(1, $dto->tenant_id);
        $this->assertSame('Test Supplier', $dto->name);
        $this->assertSame('TS-001', $dto->code);
        $this->assertSame('test@example.com', $dto->email);
        $this->assertSame('active', $dto->status);
        $this->assertSame('manufacturer', $dto->type);
        $this->assertSame('USD', $dto->currency);
    }

    public function test_supplier_data_dto_defaults(): void
    {
        $dto = new SupplierData;
        $this->assertSame('active', $dto->status);
        $this->assertSame('other', $dto->type);
        $this->assertSame('USD', $dto->currency);
    }

    public function test_supplier_data_dto_to_array(): void
    {
        $dto = SupplierData::fromArray([
            'tenant_id' => 1,
            'name'      => 'Test',
            'code'      => 'T-001',
        ]);

        $array = $dto->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('tenant_id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('code', $array);
    }

    // ── Application Service Contracts ─────────────────────────────────────────

    public function test_all_supplier_service_interfaces_exist(): void
    {
        $this->assertTrue(interface_exists(CreateSupplierServiceInterface::class));
        $this->assertTrue(interface_exists(FindSupplierServiceInterface::class));
        $this->assertTrue(interface_exists(UpdateSupplierServiceInterface::class));
        $this->assertTrue(interface_exists(DeleteSupplierServiceInterface::class));
    }

    // ── Application Services ──────────────────────────────────────────────────

    public function test_all_supplier_service_implementations_exist(): void
    {
        $this->assertTrue(class_exists(CreateSupplierService::class));
        $this->assertTrue(class_exists(FindSupplierService::class));
        $this->assertTrue(class_exists(UpdateSupplierService::class));
        $this->assertTrue(class_exists(DeleteSupplierService::class));
    }

    public function test_supplier_service_implementations_implement_their_interfaces(): void
    {
        $this->assertTrue(
            is_subclass_of(CreateSupplierService::class, CreateSupplierServiceInterface::class),
            'CreateSupplierService must implement CreateSupplierServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(FindSupplierService::class, FindSupplierServiceInterface::class),
            'FindSupplierService must implement FindSupplierServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(UpdateSupplierService::class, UpdateSupplierServiceInterface::class),
            'UpdateSupplierService must implement UpdateSupplierServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(DeleteSupplierService::class, DeleteSupplierServiceInterface::class),
            'DeleteSupplierService must implement DeleteSupplierServiceInterface.'
        );
    }

    public function test_find_supplier_service_does_not_support_write_execute(): void
    {
        $repo = $this->createMock(\Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface::class);
        $service = new FindSupplierService($repo);

        $this->expectException(\BadMethodCallException::class);

        $ref = new \ReflectionMethod($service, 'handle');
        $ref->setAccessible(true);
        $ref->invoke($service, []);
    }

    public function test_supplier_controller_extends_authorized_controller(): void
    {
        $this->assertTrue(
            is_subclass_of(
                \Modules\Supplier\Infrastructure\Http\Controllers\SupplierController::class,
                \Modules\Core\Infrastructure\Http\Controllers\AuthorizedController::class
            ),
            'SupplierController must extend AuthorizedController.'
        );
    }

    public function test_supplier_controller_injects_find_service(): void
    {
        $ref = new \ReflectionClass(\Modules\Supplier\Infrastructure\Http\Controllers\SupplierController::class);
        $constructor = $ref->getConstructor();
        $params = $constructor->getParameters();
        $paramTypes = array_map(fn ($p) => (string) $p->getType(), $params);

        $this->assertContains(FindSupplierServiceInterface::class, $paramTypes,
            'SupplierController constructor must inject FindSupplierServiceInterface.');
    }

    // ── Application Use Cases ─────────────────────────────────────────────────

    public function test_all_supplier_use_case_classes_exist(): void
    {
        $this->assertTrue(class_exists(CreateSupplier::class));
        $this->assertTrue(class_exists(UpdateSupplier::class));
        $this->assertTrue(class_exists(DeleteSupplier::class));
        $this->assertTrue(class_exists(GetSupplier::class));
        $this->assertTrue(class_exists(ListSuppliers::class));
    }

    // ── Infrastructure – Models ───────────────────────────────────────────────

    public function test_supplier_eloquent_model_class_exists(): void
    {
        $this->assertTrue(class_exists(SupplierModel::class));
    }

    public function test_supplier_model_has_expected_fillable(): void
    {
        $model = new SupplierModel;
        $fillable = $model->getFillable();

        $this->assertContains('tenant_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('code', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('currency', $fillable);
        $this->assertContains('user_id', $fillable);
    }

    // ── Infrastructure – Repositories ─────────────────────────────────────────

    public function test_supplier_eloquent_repository_exists(): void
    {
        $this->assertTrue(class_exists(EloquentSupplierRepository::class));
    }

    public function test_supplier_eloquent_repository_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(EloquentSupplierRepository::class, SupplierRepositoryInterface::class),
            'EloquentSupplierRepository must implement SupplierRepositoryInterface.'
        );
    }

    // ── Infrastructure – HTTP ─────────────────────────────────────────────────

    public function test_supplier_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(SupplierController::class));
    }

    public function test_supplier_form_request_classes_exist(): void
    {
        $this->assertTrue(class_exists(StoreSupplierRequest::class));
        $this->assertTrue(class_exists(UpdateSupplierRequest::class));
    }

    public function test_supplier_resource_classes_exist(): void
    {
        $this->assertTrue(class_exists(SupplierResource::class));
        $this->assertTrue(class_exists(SupplierCollection::class));
    }

    // ── Infrastructure – Provider ─────────────────────────────────────────────

    public function test_supplier_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(SupplierServiceProvider::class));
    }

    // ── Domain behaviour: timestamps ─────────────────────────────────────────

    public function test_supplier_entity_timestamps_are_set_on_construction(): void
    {
        $before = new \DateTimeImmutable;
        $supplier = new Supplier(tenantId: 1, name: 'Test', code: 'T-001');
        $after = new \DateTimeImmutable;

        $this->assertGreaterThanOrEqual($before->getTimestamp(), $supplier->getCreatedAt()->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $supplier->getCreatedAt()->getTimestamp());
    }

    public function test_supplier_entity_updated_at_changes_on_update_details(): void
    {
        $supplier = new Supplier(tenantId: 1, name: 'Old', code: 'OLD-001');
        $originalUpdatedAt = $supplier->getUpdatedAt();

        usleep(1000);

        $supplier->updateDetails('New', 'NEW-001', null, null, null, null, null, null, 'USD', null, 'other', null, null);

        $this->assertGreaterThanOrEqual(
            $originalUpdatedAt->getTimestamp(),
            $supplier->getUpdatedAt()->getTimestamp()
        );
    }

    // ── Store request rules ────────────────────────────────────────────────────

    public function test_store_supplier_request_has_required_rules(): void
    {
        $request = new StoreSupplierRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('tenant_id', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('code', $rules);
        $this->assertArrayHasKey('status', $rules);
        $this->assertArrayHasKey('type', $rules);
    }

    public function test_update_supplier_request_has_required_rules(): void
    {
        $request = new UpdateSupplierRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('code', $rules);
        $this->assertArrayHasKey('status', $rules);
        $this->assertArrayHasKey('type', $rules);
    }
}
