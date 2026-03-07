<?php

namespace App\Modules\Product\Services;

use App\Modules\Product\Services\Contracts\ProductServiceInterface;
use App\Modules\Product\Repositories\Contracts\ProductRepositoryInterface;
use App\Modules\Product\Events\ProductCreated;
use App\Modules\Product\Events\ProductDeleted;
use App\Modules\Product\DTOs\ProductDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class ProductService implements ProductServiceInterface
{
    private ProductRepositoryInterface $productRepository;
    private WebhookService $webhookService;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        WebhookService $webhookService
    ) {
        $this->productRepository = $productRepository;
        $this->webhookService = $webhookService;
    }

    public function getAllProducts(array $filters): LengthAwarePaginator
    {
        return $this->productRepository->getAllWithFilters($filters);
    }

    public function getProductById(int $id)
    {
        return $this->productRepository->findById($id);
    }

    /**
     * Create a product via transactional pipeline demonstrating DTOs and Webhook outputs.
     */
    public function createProduct(array $data)
    {
        // 1. Transform raw validated data into secure Data Transfer Object
        $productDTO = ProductDTO::fromArray($data);

        DB::beginTransaction();

        try {
            // 2. Persist DTO Array to Repository
            $product = $this->productRepository->create($productDTO->toArray());

            // 3. Dispatch Internal Domain Event using Laravel Events (Queue -> Listener -> RabbitMQ)
            event(new ProductCreated($product));

            // 4. Dispatch External Webhook to sub-systems subscribing outside Message Broker
            $this->webhookService->dispatch('product.created', $product->toArray());

            DB::commit();
            return $product;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateProduct(int $id, array $data)
    {
        DB::beginTransaction();
        try {
            $updatedProduct = $this->productRepository->update($id, $data);
            DB::commit();
            return $updatedProduct;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteProduct(int $id): bool
    {
        DB::beginTransaction();
        try {
            $product = $this->productRepository->findById($id);
            $deleted = $this->productRepository->delete($id);

            // Fire Domain Event for RabbitMQ to sync inventory deletions or alert saga
            event(new ProductDeleted($product));

            // Dispatch Webhook Context
            $this->webhookService->dispatch('product.deleted', ['id' => $id]);

            DB::commit();
            return $deleted;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
