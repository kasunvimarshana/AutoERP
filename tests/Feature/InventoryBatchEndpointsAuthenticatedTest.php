<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Inventory\Application\Contracts\CreateBatchServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteBatchServiceInterface;
use Modules\Inventory\Application\Contracts\FindBatchServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateBatchServiceInterface;
use Modules\Inventory\Domain\Entities\Batch;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class InventoryBatchEndpointsAuthenticatedTest extends TestCase
{
    /** @var CreateBatchServiceInterface&MockObject */
    private CreateBatchServiceInterface $createBatchService;

    /** @var UpdateBatchServiceInterface&MockObject */
    private UpdateBatchServiceInterface $updateBatchService;

    /** @var DeleteBatchServiceInterface&MockObject */
    private DeleteBatchServiceInterface $deleteBatchService;

    /** @var FindBatchServiceInterface&MockObject */
    private FindBatchServiceInterface $findBatchService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createBatchService = $this->createMock(CreateBatchServiceInterface::class);
        $this->updateBatchService = $this->createMock(UpdateBatchServiceInterface::class);
        $this->deleteBatchService = $this->createMock(DeleteBatchServiceInterface::class);
        $this->findBatchService = $this->createMock(FindBatchServiceInterface::class);

        $this->app->instance(CreateBatchServiceInterface::class, $this->createBatchService);
        $this->app->instance(UpdateBatchServiceInterface::class, $this->updateBatchService);
        $this->app->instance(DeleteBatchServiceInterface::class, $this->deleteBatchService);
        $this->app->instance(FindBatchServiceInterface::class, $this->findBatchService);

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
            'id' => 401,
            'tenant_id' => 9,
            'email' => 'inventory.batch@test.com',
            'password' => 'secret',
            'first_name' => 'Inventory',
            'last_name' => 'BatchTester',
        ]);
        $user->setAttribute('id', 401);
        $user->setAttribute('tenant_id', 9);

        $this->actingAs($user, 'api');
    }

    public function test_authenticated_index_returns_success_payload(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildBatch(id: 41)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findBatchService
            ->expects($this->once())
            ->method('list')
            ->with(
                [
                    'tenant_id' => 9,
                    'status' => 'active',
                ],
                15,
                1,
                'id'
            )
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/inventory/batches?tenant_id=9&status=active');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('0.id', 41)
            ->assertJsonPath('0.tenant_id', 9)
            ->assertJsonPath('0.batch_number', 'BATCH-001');
    }

    public function test_authenticated_show_returns_not_found_when_batch_is_missing(): void
    {
        $this->findBatchService
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/inventory/batches/999');

        $response->assertStatus(HttpResponse::HTTP_NOT_FOUND)
            ->assertJsonPath('message', 'Batch not found.');
    }

    public function test_authenticated_store_returns_created_payload(): void
    {
        $this->createBatchService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $payload): bool {
                return (int) ($payload['tenant_id'] ?? 0) === 9
                    && (int) ($payload['product_id'] ?? 0) === 1001
                    && ($payload['batch_number'] ?? null) === 'BATCH-001';
            }))
            ->willReturn($this->buildBatch(id: 52));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/inventory/batches', [
                'tenant_id' => 9,
                'product_id' => 1001,
                'batch_number' => 'BATCH-001',
                'status' => 'active',
            ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('id', 52)
            ->assertJsonPath('product_id', 1001);
    }

    public function test_authenticated_update_returns_success_payload(): void
    {
        $this->updateBatchService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $payload): bool {
                return (int) ($payload['id'] ?? 0) === 7
                    && (int) ($payload['tenant_id'] ?? 0) === 9
                    && ($payload['batch_number'] ?? null) === 'BATCH-007';
            }))
            ->willReturn($this->buildBatch(id: 7, batchNumber: 'BATCH-007'));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->putJson('/api/inventory/batches/7', [
                'tenant_id' => 9,
                'batch_number' => 'BATCH-007',
                'status' => 'quarantine',
            ]);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('id', 7)
            ->assertJsonPath('batch_number', 'BATCH-007');
    }

    public function test_authenticated_destroy_returns_no_content(): void
    {
        $this->deleteBatchService
            ->expects($this->once())
            ->method('execute')
            ->with(['id' => 8])
            ->willReturn(true);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->deleteJson('/api/inventory/batches/8');

        $response->assertStatus(HttpResponse::HTTP_NO_CONTENT);
    }

    private function buildBatch(int $id, string $batchNumber = 'BATCH-001'): Batch
    {
        return new Batch(
            tenantId:        9,
            productId:       1001,
            variantId:       null,
            batchNumber:     $batchNumber,
            lotNumber:       'LOT-001',
            manufactureDate: null,
            expiryDate:      null,
            receivedDate:    null,
            supplierId:      null,
            status:          'active',
            notes:           null,
            metadata:        null,
            salesPrice:      null,
            id:              $id,
        );
    }
}
