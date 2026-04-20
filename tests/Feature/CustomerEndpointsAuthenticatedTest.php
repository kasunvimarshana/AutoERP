<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Laravel\Passport\Passport;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Customer\Application\Contracts\CreateCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\CreateCustomerContactServiceInterface;
use Modules\Customer\Application\Contracts\CreateCustomerServiceInterface;
use Modules\Customer\Application\Contracts\DeleteCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\DeleteCustomerContactServiceInterface;
use Modules\Customer\Application\Contracts\DeleteCustomerServiceInterface;
use Modules\Customer\Application\Contracts\FindCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\FindCustomerContactServiceInterface;
use Modules\Customer\Application\Contracts\FindCustomerServiceInterface;
use Modules\Customer\Application\Contracts\UpdateCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\UpdateCustomerContactServiceInterface;
use Modules\Customer\Application\Contracts\UpdateCustomerServiceInterface;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\Entities\CustomerAddress;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class CustomerEndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    /** @var FindCustomerServiceInterface&MockObject */
    private FindCustomerServiceInterface $findCustomerService;

    /** @var CreateCustomerServiceInterface&MockObject */
    private CreateCustomerServiceInterface $createCustomerService;

    /** @var FindCustomerAddressServiceInterface&MockObject */
    private FindCustomerAddressServiceInterface $findCustomerAddressService;

    /** @var CreateCustomerAddressServiceInterface&MockObject */
    private CreateCustomerAddressServiceInterface $createCustomerAddressService;

    /** @var FindCustomerContactServiceInterface&MockObject */
    private FindCustomerContactServiceInterface $findCustomerContactService;

    /** @var CreateCustomerContactServiceInterface&MockObject */
    private CreateCustomerContactServiceInterface $createCustomerContactService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();

        $this->findCustomerService = $this->createMock(FindCustomerServiceInterface::class);
        $this->createCustomerService = $this->createMock(CreateCustomerServiceInterface::class);
        $this->findCustomerAddressService = $this->createMock(FindCustomerAddressServiceInterface::class);
        $this->createCustomerAddressService = $this->createMock(CreateCustomerAddressServiceInterface::class);
        $this->findCustomerContactService = $this->createMock(FindCustomerContactServiceInterface::class);
        $this->createCustomerContactService = $this->createMock(CreateCustomerContactServiceInterface::class);

        $this->app->instance(FindCustomerServiceInterface::class, $this->findCustomerService);
        $this->app->instance(CreateCustomerServiceInterface::class, $this->createCustomerService);
        $this->app->instance(UpdateCustomerServiceInterface::class, $this->createMock(UpdateCustomerServiceInterface::class));
        $this->app->instance(DeleteCustomerServiceInterface::class, $this->createMock(DeleteCustomerServiceInterface::class));

        $this->app->instance(FindCustomerAddressServiceInterface::class, $this->findCustomerAddressService);
        $this->app->instance(CreateCustomerAddressServiceInterface::class, $this->createCustomerAddressService);
        $this->app->instance(UpdateCustomerAddressServiceInterface::class, $this->createMock(UpdateCustomerAddressServiceInterface::class));
        $this->app->instance(DeleteCustomerAddressServiceInterface::class, $this->createMock(DeleteCustomerAddressServiceInterface::class));

        $this->app->instance(FindCustomerContactServiceInterface::class, $this->findCustomerContactService);
        $this->app->instance(CreateCustomerContactServiceInterface::class, $this->createCustomerContactService);
        $this->app->instance(UpdateCustomerContactServiceInterface::class, $this->createMock(UpdateCustomerContactServiceInterface::class));
        $this->app->instance(DeleteCustomerContactServiceInterface::class, $this->createMock(DeleteCustomerContactServiceInterface::class));

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
                if ($collection === 'customers' && in_array($column, ['customer_code', 'user_id'], true)) {
                    return 0;
                }

                return 1;
            }
        );
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app->instance(PresenceVerifierInterface::class, $presenceVerifier);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        $user = new UserModel([
            'id' => 901,
            'tenant_id' => 9,
            'email' => 'customer.test@example.com',
            'password' => 'secret',
            'first_name' => 'Customer',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 901);
        $user->setAttribute('tenant_id', 9);

        Passport::actingAs($user, [], 'api');
    }

    public function test_authenticated_customer_index_returns_success_payload(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildCustomer(id: 41)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findCustomerService
            ->expects($this->once())
            ->method('list')
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/customers?tenant_id=9&name=Acme&sort=-customer_code');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 41)
            ->assertJsonPath('data.0.customer_code', 'CUS-041')
            ->assertJsonPath('data.0.user_id', 701);
    }

    public function test_authenticated_address_index_returns_success_payload(): void
    {
        $this->findCustomerService
            ->expects($this->once())
            ->method('find')
            ->with(41)
            ->willReturn($this->buildCustomer(41));

        $paginator = new LengthAwarePaginator(
            items: [$this->buildAddress(id: 1)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findCustomerAddressService
            ->expects($this->once())
            ->method('paginateByCustomer')
            ->with(9, 41, 15, 1)
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/customers/41/addresses');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 1)
            ->assertJsonPath('data.0.customer_id', 41)
            ->assertJsonPath('data.0.type', 'billing');
    }

    public function test_authenticated_contact_store_validates_email(): void
    {
        $this->createCustomerContactService
            ->expects($this->never())
            ->method('execute');

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/customers/41/contacts', [
                'name' => 'John Doe',
                'email' => 'invalid-email',
            ]);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_customer_store_returns_422_for_duplicate_customer_code(): void
    {
        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturnCallback(
            static function (string $collection, string $column): int {
                if ($collection === 'customers' && $column === 'customer_code') {
                    return 1;
                }

                if ($collection === 'customers' && $column === 'user_id') {
                    return 0;
                }

                if ($collection === 'users' && $column === 'email') {
                    return 0;
                }

                return 1;
            }
        );
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app->instance(PresenceVerifierInterface::class, $presenceVerifier);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        $this->createCustomerService
            ->expects($this->never())
            ->method('execute');

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/customers', [
                'tenant_id' => 9,
                'customer_code' => 'CUS-EXISTS',
                'name' => 'Existing Code Customer',
                'type' => 'company',
                'user' => [
                    'email' => 'new-customer@example.com',
                    'first_name' => 'New',
                    'last_name' => 'Customer',
                ],
            ]);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['customer_code']);
    }

    public function test_authenticated_contact_store_returns_409_on_unique_write_conflict(): void
    {
        $this->findCustomerService
            ->expects($this->once())
            ->method('find')
            ->with(41)
            ->willReturn($this->buildCustomer(41));

        $this->createCustomerContactService
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(new QueryException(
                'sqlite',
                'insert into "customer_contacts" (...) values (...)',
                [],
                new \RuntimeException('SQLSTATE[23000]: Integrity constraint violation: UNIQUE constraint failed: customer_contacts_single_primary_per_customer_uk', 23000)
            ));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/customers/41/contacts', [
                'name' => 'John Primary',
                'email' => 'john.primary@example.com',
                'is_primary' => true,
            ]);

        $response->assertStatus(HttpResponse::HTTP_CONFLICT)
            ->assertJsonPath('message', 'Resource conflict: unique constraint violated.');
    }

    private function clearRoutesCacheOnce(): void
    {
        if (self::$routesCleared) {
            return;
        }

        Artisan::call('route:clear');
        self::$routesCleared = true;
    }

    private function buildCustomer(int $id): Customer
    {
        return new Customer(
            id: $id,
            tenantId: 9,
            userId: 701,
            customerCode: 'CUS-041',
            name: 'Acme Ltd',
            type: 'company',
            creditLimit: '0.000000',
            paymentTermsDays: 30,
            status: 'active',
        );
    }

    private function buildAddress(int $id): CustomerAddress
    {
        return new CustomerAddress(
            id: $id,
            tenantId: 9,
            customerId: 41,
            type: 'billing',
            addressLine1: '123 Main St',
            city: 'Colombo',
            postalCode: '00100',
            countryId: 1,
        );
    }
}
