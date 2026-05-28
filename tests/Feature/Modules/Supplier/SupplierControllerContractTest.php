<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Supplier;

use Mockery;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Result;
use Modules\Supplier\Application\Contracts\Services\SupplierManagementServiceInterface;
use Tests\TestCase;

final class SupplierControllerContractTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreateSupplierDoesNotRequireUserAccessByDefault(): void
    {
        $this->withoutMiddleware();

        $service = Mockery::mock(SupplierManagementServiceInterface::class);
        $service->shouldReceive('createSupplier')
            ->once()
            ->with(Mockery::on(function (array $payload): bool {
                return ($payload['supplier_code'] ?? null) === 'SUP-001'
                    && ($payload['supplier_name'] ?? null) === 'Supplier A'
                    && ! array_key_exists('create_user_access', $payload);
            }))
            ->andReturn(Result::success(new DataRecord([
                'id' => 101,
                'supplier_code' => 'SUP-001',
                'supplier_name' => 'Supplier A',
                'status' => 'draft',
            ])));

        $this->instance(SupplierManagementServiceInterface::class, $service);

        $response = $this->postJson('/api/supplier/suppliers', [
            'supplier_code' => 'SUP-001',
            'supplier_name' => 'Supplier A',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.id', 101)
            ->assertJsonPath('data.supplier_code', 'SUP-001');
    }

    public function testLinkExistingUserEndpointDelegatesToService(): void
    {
        $this->withoutMiddleware();

        $service = Mockery::mock(SupplierManagementServiceInterface::class);
        $service->shouldReceive('linkExistingUser')
            ->once()
            ->with(12, Mockery::on(function (array $payload): bool {
                return ($payload['user_id'] ?? null) === 99;
            }))
            ->andReturn(Result::success(new DataRecord([
                'id' => 10,
                'supplier_id' => 12,
                'user_id' => 99,
                'status' => 'active',
            ])));

        $this->instance(SupplierManagementServiceInterface::class, $service);

        $response = $this->postJson('/api/supplier/suppliers/12/link-user', [
            'user_id' => 99,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user_id', 99);
    }

    public function testStatusTransitionEndpointDelegatesToService(): void
    {
        $this->withoutMiddleware();

        $service = Mockery::mock(SupplierManagementServiceInterface::class);
        $service->shouldReceive('changeStatus')
            ->once()
            ->with(12, 'active', 'approved')
            ->andReturn(Result::success(new DataRecord([
                'id' => 12,
                'status' => 'active',
                'is_active' => true,
            ])));

        $this->instance(SupplierManagementServiceInterface::class, $service);

        $response = $this->patchJson('/api/supplier/suppliers/12/status', [
            'status' => 'active',
            'reason' => 'approved',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'active');
    }
}
