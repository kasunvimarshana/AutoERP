<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Laravel\Passport\Passport;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Employee\Application\Contracts\CreateEmployeeServiceInterface;
use Modules\Employee\Application\Contracts\DeleteEmployeeServiceInterface;
use Modules\Employee\Application\Contracts\FindEmployeeServiceInterface;
use Modules\Employee\Application\Contracts\UpdateEmployeeServiceInterface;
use Modules\Employee\Domain\Entities\Employee;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class EmployeeEndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    /** @var CreateEmployeeServiceInterface&MockObject */
    private CreateEmployeeServiceInterface $createEmployeeService;

    /** @var UpdateEmployeeServiceInterface&MockObject */
    private UpdateEmployeeServiceInterface $updateEmployeeService;

    /** @var FindEmployeeServiceInterface&MockObject */
    private FindEmployeeServiceInterface $findEmployeeService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();

        $this->findEmployeeService = $this->createMock(FindEmployeeServiceInterface::class);
        $this->createEmployeeService = $this->createMock(CreateEmployeeServiceInterface::class);
        $this->updateEmployeeService = $this->createMock(UpdateEmployeeServiceInterface::class);

        $this->app->instance(FindEmployeeServiceInterface::class, $this->findEmployeeService);
        $this->app->instance(CreateEmployeeServiceInterface::class, $this->createEmployeeService);
        $this->app->instance(UpdateEmployeeServiceInterface::class, $this->updateEmployeeService);
        $this->app->instance(DeleteEmployeeServiceInterface::class, $this->createMock(DeleteEmployeeServiceInterface::class));

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
                if ($collection === 'employees' && in_array($column, ['employee_code', 'user_id'], true)) {
                    return 0;
                }

                return 1;
            }
        );
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app->instance(PresenceVerifierInterface::class, $presenceVerifier);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        $user = new UserModel([
            'id' => 271,
            'tenant_id' => 9,
            'email' => 'employee.test@example.com',
            'password' => 'secret',
            'first_name' => 'Employee',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 271);
        $user->setAttribute('tenant_id', 9);

        Passport::actingAs($user, [], 'api');
    }

    private function clearRoutesCacheOnce(): void
    {
        if (self::$routesCleared) {
            return;
        }

        Artisan::call('route:clear');
        self::$routesCleared = true;
    }

    public function test_authenticated_index_returns_success_payload(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildEmployee(id: 41)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findEmployeeService
            ->expects($this->once())
            ->method('list')
            ->with(
                [
                    'tenant_id' => 9,
                    'job_title' => 'Engineer',
                ],
                15,
                1,
                '-employee_code',
                null
            )
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/employees?tenant_id=9&job_title=Engineer&sort=-employee_code');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 41)
            ->assertJsonPath('data.0.employee_code', 'EMP-041')
            ->assertJsonPath('data.0.user_id', 701);
    }

    public function test_authenticated_show_returns_success_payload(): void
    {
        $this->findEmployeeService
            ->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($this->buildEmployee(id: 42));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/employees/42');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 42)
            ->assertJsonPath('data.tenant_id', 9)
            ->assertJsonPath('data.employee_code', 'EMP-041');
    }

    public function test_authenticated_store_requires_valid_dates(): void
    {
        $this->createEmployeeService
            ->expects($this->never())
            ->method('execute');

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/employees', [
                'tenant_id' => 9,
                'user_id' => 701,
                'hire_date' => '2025-01-02',
                'termination_date' => '2025-01-01',
            ]);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['termination_date']);
    }

    private function buildEmployee(int $id): Employee
    {
        return new Employee(
            id: $id,
            tenantId: 9,
            userId: 701,
            employeeCode: 'EMP-041',
            orgUnitId: null,
            jobTitle: 'Engineer',
            hireDate: new \DateTimeImmutable('2024-01-01'),
            terminationDate: null,
            metadata: ['department' => 'Engineering'],
        );
    }
}
