<?php

namespace Tests\Unit;

use Modules\Customer\Application\Contracts\CreateCustomerServiceInterface;
use Modules\Customer\Application\Contracts\DeleteCustomerServiceInterface;
use Modules\Customer\Application\Contracts\UpdateCustomerServiceInterface;
use Modules\Customer\Application\DTOs\CustomerData;
use Modules\Customer\Application\Services\CreateCustomerService;
use Modules\Customer\Application\Services\DeleteCustomerService;
use Modules\Customer\Application\Services\UpdateCustomerService;
use Modules\Customer\Application\UseCases\CreateCustomer;
use Modules\Customer\Application\UseCases\DeleteCustomer;
use Modules\Customer\Application\UseCases\GetCustomer;
use Modules\Customer\Application\UseCases\ListCustomers;
use Modules\Customer\Application\UseCases\UpdateCustomer;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\Events\CustomerCreated;
use Modules\Customer\Domain\Events\CustomerDeleted;
use Modules\Customer\Domain\Events\CustomerUpdated;
use Modules\Customer\Domain\Exceptions\CustomerNotFoundException;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;
use Modules\Customer\Infrastructure\Http\Controllers\CustomerController;
use Modules\Customer\Infrastructure\Http\Requests\StoreCustomerRequest;
use Modules\Customer\Infrastructure\Http\Requests\UpdateCustomerRequest;
use Modules\Customer\Infrastructure\Http\Resources\CustomerCollection;
use Modules\Customer\Infrastructure\Http\Resources\CustomerResource;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerRepository;
use Modules\Customer\Infrastructure\Providers\CustomerServiceProvider;
use PHPUnit\Framework\TestCase;

class CustomerModuleTest extends TestCase
{
    // ── Domain Entities ───────────────────────────────────────────────────────

    public function test_customer_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(Customer::class));
    }

    public function test_customer_entity_can_be_constructed(): void
    {
        $customer = new Customer(
            tenantId: 1,
            name: 'Jane Smith',
            code: 'CUST-001',
        );

        $this->assertSame(1, $customer->getTenantId());
        $this->assertSame('Jane Smith', $customer->getName());
        $this->assertSame('CUST-001', $customer->getCode());
        $this->assertSame('active', $customer->getStatus());
        $this->assertSame('retail', $customer->getType());
        $this->assertSame('USD', $customer->getCurrency());
        $this->assertNull($customer->getUserId());
        $this->assertNull($customer->getEmail());
        $this->assertNull($customer->getPhone());
        $this->assertNull($customer->getBillingAddress());
        $this->assertNull($customer->getShippingAddress());
        $this->assertNull($customer->getDateOfBirth());
        $this->assertNull($customer->getLoyaltyTier());
        $this->assertNull($customer->getCreditLimit());
        $this->assertNull($customer->getPaymentTerms());
        $this->assertNull($customer->getTaxNumber());
        $this->assertNull($customer->getAttributes());
        $this->assertNull($customer->getMetadata());
        $this->assertNull($customer->getId());
    }

    public function test_customer_entity_with_all_fields(): void
    {
        $customer = new Customer(
            tenantId: 2,
            name: 'Acme Corp',
            code: 'CC-001',
            userId: 5,
            email: 'info@acme.example.com',
            phone: '+1-555-0200',
            billingAddress: ['street' => '123 Main St', 'city' => 'Springfield'],
            shippingAddress: ['street' => '456 Oak Ave', 'city' => 'Portland'],
            dateOfBirth: '1985-06-15',
            loyaltyTier: 'gold',
            creditLimit: 10000.00,
            paymentTerms: 'net30',
            currency: 'EUR',
            taxNumber: 'TAX-999',
            status: 'inactive',
            type: 'corporate',
            attributes: ['tier' => 'platinum'],
            metadata: ['source' => 'import'],
            id: 42,
        );

        $this->assertSame(42, $customer->getId());
        $this->assertSame(2, $customer->getTenantId());
        $this->assertSame(5, $customer->getUserId());
        $this->assertSame('Acme Corp', $customer->getName());
        $this->assertSame('CC-001', $customer->getCode());
        $this->assertSame('info@acme.example.com', $customer->getEmail());
        $this->assertSame('+1-555-0200', $customer->getPhone());
        $this->assertSame(['street' => '123 Main St', 'city' => 'Springfield'], $customer->getBillingAddress());
        $this->assertSame(['street' => '456 Oak Ave', 'city' => 'Portland'], $customer->getShippingAddress());
        $this->assertSame('1985-06-15', $customer->getDateOfBirth());
        $this->assertSame('gold', $customer->getLoyaltyTier());
        $this->assertSame(10000.00, $customer->getCreditLimit());
        $this->assertSame('net30', $customer->getPaymentTerms());
        $this->assertSame('EUR', $customer->getCurrency());
        $this->assertSame('TAX-999', $customer->getTaxNumber());
        $this->assertSame('inactive', $customer->getStatus());
        $this->assertSame('corporate', $customer->getType());
        $this->assertSame(['tier' => 'platinum'], $customer->getAttributes());
        $this->assertSame(['source' => 'import'], $customer->getMetadata());
    }

    public function test_customer_entity_update_details(): void
    {
        $customer = new Customer(tenantId: 1, name: 'Old Name', code: 'OLD-001');

        $customer->updateDetails(
            name: 'New Name',
            code: 'NEW-001',
            userId: 10,
            email: 'new@example.com',
            phone: '+1-555-9999',
            billingAddress: ['city' => 'Boston'],
            shippingAddress: ['city' => 'Seattle'],
            dateOfBirth: '1990-03-20',
            loyaltyTier: 'platinum',
            creditLimit: 25000.00,
            paymentTerms: 'net60',
            currency: 'GBP',
            taxNumber: 'TAX-001',
            type: 'wholesale',
            attributes: ['certified' => true],
            metadata: ['updated' => true],
        );

        $this->assertSame('New Name', $customer->getName());
        $this->assertSame('NEW-001', $customer->getCode());
        $this->assertSame(10, $customer->getUserId());
        $this->assertSame('new@example.com', $customer->getEmail());
        $this->assertSame('+1-555-9999', $customer->getPhone());
        $this->assertSame(['city' => 'Boston'], $customer->getBillingAddress());
        $this->assertSame(['city' => 'Seattle'], $customer->getShippingAddress());
        $this->assertSame('1990-03-20', $customer->getDateOfBirth());
        $this->assertSame('platinum', $customer->getLoyaltyTier());
        $this->assertSame(25000.00, $customer->getCreditLimit());
        $this->assertSame('net60', $customer->getPaymentTerms());
        $this->assertSame('GBP', $customer->getCurrency());
        $this->assertSame('TAX-001', $customer->getTaxNumber());
        $this->assertSame('wholesale', $customer->getType());
        $this->assertSame(['certified' => true], $customer->getAttributes());
        $this->assertSame(['updated' => true], $customer->getMetadata());
    }

    public function test_customer_entity_activate_deactivate(): void
    {
        $customer = new Customer(tenantId: 1, name: 'Test', code: 'T-001', status: 'inactive');
        $this->assertFalse($customer->isActive());

        $customer->activate();
        $this->assertTrue($customer->isActive());
        $this->assertSame('active', $customer->getStatus());

        $customer->deactivate();
        $this->assertFalse($customer->isActive());
        $this->assertSame('inactive', $customer->getStatus());
    }

    public function test_customer_entity_has_user_access(): void
    {
        $customer = new Customer(tenantId: 1, name: 'Test', code: 'T-001');
        $this->assertFalse($customer->hasUserAccess());

        $customerWithUser = new Customer(tenantId: 1, name: 'Test', code: 'T-001', userId: 7);
        $this->assertTrue($customerWithUser->hasUserAccess());
    }

    // ── Domain Events ─────────────────────────────────────────────────────────

    public function test_all_customer_event_classes_exist(): void
    {
        $this->assertTrue(class_exists(CustomerCreated::class));
        $this->assertTrue(class_exists(CustomerUpdated::class));
        $this->assertTrue(class_exists(CustomerDeleted::class));
    }

    public function test_customer_created_event_can_be_instantiated(): void
    {
        $customer = new Customer(tenantId: 1, name: 'Test', code: 'T-001', id: 1);
        $event    = new CustomerCreated($customer);

        $this->assertSame($customer, $event->customer);
        $this->assertSame(1, $event->tenantId);
    }

    public function test_customer_updated_event_can_be_instantiated(): void
    {
        $customer = new Customer(tenantId: 2, name: 'Updated', code: 'U-001', id: 3);
        $event    = new CustomerUpdated($customer);

        $this->assertSame($customer, $event->customer);
        $this->assertSame(2, $event->tenantId);
    }

    public function test_customer_deleted_event_can_be_instantiated(): void
    {
        $event = new CustomerDeleted(customerId: 7, tenantId: 3);

        $this->assertSame(7, $event->customerId);
        $this->assertSame(3, $event->tenantId);
    }

    public function test_customer_created_event_broadcast_with(): void
    {
        $customer = new Customer(tenantId: 1, name: 'My Customer', code: 'CUST-001', status: 'active', id: 1);
        $event    = new CustomerCreated($customer);
        $payload  = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertArrayHasKey('code', $payload);
        $this->assertArrayHasKey('status', $payload);
        $this->assertArrayHasKey('tenantId', $payload);
    }

    // ── Domain Exceptions ─────────────────────────────────────────────────────

    public function test_customer_exception_class_exists(): void
    {
        $this->assertTrue(class_exists(CustomerNotFoundException::class));
    }

    public function test_customer_not_found_exception_message(): void
    {
        $e = new CustomerNotFoundException(42);
        $this->assertStringContainsString('Customer', $e->getMessage());
        $this->assertStringContainsString('42', $e->getMessage());
    }

    // ── Domain Repository Interfaces ─────────────────────────────────────────

    public function test_customer_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(CustomerRepositoryInterface::class));
    }

    public function test_customer_repository_interface_has_required_methods(): void
    {
        $reflection = new \ReflectionClass(CustomerRepositoryInterface::class);
        $this->assertTrue($reflection->hasMethod('findByCode'));
        $this->assertTrue($reflection->hasMethod('findByTenant'));
        $this->assertTrue($reflection->hasMethod('findByUserId'));
        $this->assertTrue($reflection->hasMethod('save'));
    }

    // ── Application DTOs ─────────────────────────────────────────────────────

    public function test_customer_dto_class_exists(): void
    {
        $this->assertTrue(class_exists(CustomerData::class));
    }

    public function test_customer_data_dto_from_array(): void
    {
        $dto = CustomerData::fromArray([
            'tenant_id'    => 1,
            'name'         => 'Test Customer',
            'code'         => 'TC-001',
            'email'        => 'test@example.com',
            'status'       => 'active',
            'type'         => 'wholesale',
            'currency'     => 'USD',
            'loyalty_tier' => 'silver',
            'credit_limit' => 2000.00,
        ]);

        $this->assertSame(1, $dto->tenant_id);
        $this->assertSame('Test Customer', $dto->name);
        $this->assertSame('TC-001', $dto->code);
        $this->assertSame('test@example.com', $dto->email);
        $this->assertSame('active', $dto->status);
        $this->assertSame('wholesale', $dto->type);
        $this->assertSame('USD', $dto->currency);
        $this->assertSame('silver', $dto->loyalty_tier);
        $this->assertSame(2000.00, $dto->credit_limit);
    }

    public function test_customer_data_dto_defaults(): void
    {
        $dto = new CustomerData;
        $this->assertSame('active', $dto->status);
        $this->assertSame('retail', $dto->type);
        $this->assertSame('USD', $dto->currency);
    }

    public function test_customer_data_dto_to_array(): void
    {
        $dto = CustomerData::fromArray([
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

    public function test_all_customer_service_interfaces_exist(): void
    {
        $this->assertTrue(interface_exists(CreateCustomerServiceInterface::class));
        $this->assertTrue(interface_exists(UpdateCustomerServiceInterface::class));
        $this->assertTrue(interface_exists(DeleteCustomerServiceInterface::class));
    }

    // ── Application Services ──────────────────────────────────────────────────

    public function test_all_customer_service_implementations_exist(): void
    {
        $this->assertTrue(class_exists(CreateCustomerService::class));
        $this->assertTrue(class_exists(UpdateCustomerService::class));
        $this->assertTrue(class_exists(DeleteCustomerService::class));
    }

    public function test_customer_service_implementations_implement_their_interfaces(): void
    {
        $this->assertTrue(
            is_subclass_of(CreateCustomerService::class, CreateCustomerServiceInterface::class),
            'CreateCustomerService must implement CreateCustomerServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(UpdateCustomerService::class, UpdateCustomerServiceInterface::class),
            'UpdateCustomerService must implement UpdateCustomerServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(DeleteCustomerService::class, DeleteCustomerServiceInterface::class),
            'DeleteCustomerService must implement DeleteCustomerServiceInterface.'
        );
    }

    // ── Application Use Cases ─────────────────────────────────────────────────

    public function test_all_customer_use_case_classes_exist(): void
    {
        $this->assertTrue(class_exists(CreateCustomer::class));
        $this->assertTrue(class_exists(UpdateCustomer::class));
        $this->assertTrue(class_exists(DeleteCustomer::class));
        $this->assertTrue(class_exists(GetCustomer::class));
        $this->assertTrue(class_exists(ListCustomers::class));
    }

    // ── Infrastructure – Models ───────────────────────────────────────────────

    public function test_customer_eloquent_model_class_exists(): void
    {
        $this->assertTrue(class_exists(CustomerModel::class));
    }

    public function test_customer_model_has_expected_fillable(): void
    {
        $model    = new CustomerModel;
        $fillable = $model->getFillable();

        $this->assertContains('tenant_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('code', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('currency', $fillable);
        $this->assertContains('user_id', $fillable);
        $this->assertContains('billing_address', $fillable);
        $this->assertContains('shipping_address', $fillable);
        $this->assertContains('loyalty_tier', $fillable);
        $this->assertContains('credit_limit', $fillable);
    }

    // ── Infrastructure – Repositories ─────────────────────────────────────────

    public function test_customer_eloquent_repository_exists(): void
    {
        $this->assertTrue(class_exists(EloquentCustomerRepository::class));
    }

    public function test_customer_eloquent_repository_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(EloquentCustomerRepository::class, CustomerRepositoryInterface::class),
            'EloquentCustomerRepository must implement CustomerRepositoryInterface.'
        );
    }

    // ── Infrastructure – HTTP ─────────────────────────────────────────────────

    public function test_customer_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(CustomerController::class));
    }

    public function test_customer_form_request_classes_exist(): void
    {
        $this->assertTrue(class_exists(StoreCustomerRequest::class));
        $this->assertTrue(class_exists(UpdateCustomerRequest::class));
    }

    public function test_customer_resource_classes_exist(): void
    {
        $this->assertTrue(class_exists(CustomerResource::class));
        $this->assertTrue(class_exists(CustomerCollection::class));
    }

    // ── Infrastructure – Provider ─────────────────────────────────────────────

    public function test_customer_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(CustomerServiceProvider::class));
    }

    // ── Domain behaviour: timestamps ─────────────────────────────────────────

    public function test_customer_entity_timestamps_are_set_on_construction(): void
    {
        $before   = new \DateTimeImmutable;
        $customer = new Customer(tenantId: 1, name: 'Test', code: 'T-001');
        $after    = new \DateTimeImmutable;

        $this->assertGreaterThanOrEqual($before->getTimestamp(), $customer->getCreatedAt()->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $customer->getCreatedAt()->getTimestamp());
    }

    public function test_customer_entity_updated_at_changes_on_update_details(): void
    {
        $customer         = new Customer(tenantId: 1, name: 'Old', code: 'OLD-001');
        $originalUpdatedAt = $customer->getUpdatedAt();

        usleep(1000);

        $customer->updateDetails(
            name: 'New', code: 'NEW-001', userId: null, email: null, phone: null,
            billingAddress: null, shippingAddress: null, dateOfBirth: null,
            loyaltyTier: null, creditLimit: null, paymentTerms: null,
            currency: 'USD', taxNumber: null, type: 'retail',
            attributes: null, metadata: null
        );

        $this->assertGreaterThanOrEqual(
            $originalUpdatedAt->getTimestamp(),
            $customer->getUpdatedAt()->getTimestamp()
        );
    }

    // ── Store request rules ────────────────────────────────────────────────────

    public function test_store_customer_request_has_required_rules(): void
    {
        $request = new StoreCustomerRequest;
        $rules   = $request->rules();

        $this->assertArrayHasKey('tenant_id', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('code', $rules);
        $this->assertArrayHasKey('status', $rules);
        $this->assertArrayHasKey('type', $rules);
        $this->assertArrayHasKey('loyalty_tier', $rules);
        $this->assertArrayHasKey('credit_limit', $rules);
    }

    public function test_update_customer_request_has_required_rules(): void
    {
        $request = new UpdateCustomerRequest;
        $rules   = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('code', $rules);
        $this->assertArrayHasKey('status', $rules);
        $this->assertArrayHasKey('type', $rules);
        $this->assertArrayHasKey('loyalty_tier', $rules);
        $this->assertArrayHasKey('credit_limit', $rules);
    }
}
