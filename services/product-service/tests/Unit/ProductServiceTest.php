<?php

namespace Tests\Unit;

use App\DTOs\ProductDTO;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Jobs\SyncInventoryJob;
use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Services\ProductService;
use App\Webhooks\WebhookDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductRepositoryInterface $repository;
    private WebhookDispatcher $webhookDispatcher;
    private ProductService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository        = $this->createMock(ProductRepositoryInterface::class);
        $this->webhookDispatcher = $this->createMock(WebhookDispatcher::class);
        $this->service           = new ProductService($this->repository, $this->webhookDispatcher);
    }

    private function makeDto(array $overrides = []): ProductDTO
    {
        return ProductDTO::fromRequest(array_merge([
            'sku'       => 'TEST-001',
            'name'      => 'Test Product',
            'price'     => 99.99,
            'is_active' => true,
            'status'    => 'active',
        ], $overrides), $this->tenantId);
    }

    // -------------------------------------------------------------------------
    // create()
    // -------------------------------------------------------------------------

    public function test_create_dispatches_product_created_event(): void
    {
        Event::fake([ProductCreated::class]);
        Queue::fake();

        $product = new Product();
        $product->id        = 1;
        $product->sku       = 'TEST-001';
        $product->name      = 'Test Product';
        $product->price     = 99.99;
        $product->tenant_id = $this->tenantId;
        $product->status    = 'active';
        $product->is_active = true;

        $this->repository->method('findBySku')->willReturn(null);
        $this->repository->method('create')->willReturn($product);
        $this->webhookDispatcher->method('dispatch')->willReturn(null);

        $this->service->create($this->makeDto(), 'user-123');

        Event::assertDispatched(ProductCreated::class, function (ProductCreated $e) {
            return $e->product->sku === 'TEST-001' && $e->tenantId === $this->tenantId;
        });
    }

    public function test_create_dispatches_sync_inventory_job(): void
    {
        Queue::fake();
        Event::fake();

        $product = new Product();
        $product->id        = 42;
        $product->sku       = 'TEST-001';
        $product->tenant_id = $this->tenantId;

        $this->repository->method('findBySku')->willReturn(null);
        $this->repository->method('create')->willReturn($product);
        $this->webhookDispatcher->method('dispatch')->willReturn(null);

        $this->service->create($this->makeDto(), 'user-123');

        Queue::assertPushed(SyncInventoryJob::class, function (SyncInventoryJob $job) {
            return $job->productId === 42 && $job->tenantId === $this->tenantId;
        });
    }

    public function test_create_aborts_on_duplicate_sku(): void
    {
        $existing        = new Product();
        $existing->id    = 5;
        $existing->sku   = 'TEST-001';

        $this->repository->method('findBySku')->willReturn($existing);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->service->create($this->makeDto(), 'user-123');
    }

    // -------------------------------------------------------------------------
    // update()
    // -------------------------------------------------------------------------

    public function test_update_dispatches_product_updated_event_when_changes_exist(): void
    {
        Event::fake([ProductUpdated::class]);

        $product = new Product(['sku' => 'OLD-001', 'name' => 'Old Name', 'price' => 10.00, 'status' => 'active', 'is_active' => true]);
        $product->id        = 10;
        $product->tenant_id = $this->tenantId;
        $product->syncOriginal();

        $updated = clone $product;
        $updated->name = 'New Name';

        $this->repository->method('findBySku')->willReturn(null);
        $this->repository->method('update')->willReturn($updated);
        $this->webhookDispatcher->method('dispatch')->willReturn(null);

        $dto = $this->makeDto(['sku' => 'OLD-001', 'name' => 'New Name', 'price' => 10.00]);
        $this->service->update($product, $dto, 'user-123');

        Event::assertDispatched(ProductUpdated::class);
    }

    // -------------------------------------------------------------------------
    // delete()
    // -------------------------------------------------------------------------

    public function test_delete_dispatches_product_deleted_event(): void
    {
        Event::fake([ProductDeleted::class]);

        $product = new Product(['sku' => 'DEL-001', 'name' => 'To Delete', 'price' => 5, 'status' => 'active', 'is_active' => true]);
        $product->id        = 20;
        $product->tenant_id = $this->tenantId;

        $this->repository->method('delete')->willReturn(true);
        $this->webhookDispatcher->method('dispatch')->willReturn(null);

        $this->service->delete($product, 'user-123');

        Event::assertDispatched(ProductDeleted::class, function (ProductDeleted $e) {
            return $e->productId === 20 && $e->sku === 'DEL-001';
        });
    }
}
