<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Product Service
 * 
 * Contains business logic for Product operations.
 * Demonstrates how to extend BaseService with custom business logic.
 */
class ProductService extends BaseService
{
    /**
     * Constructor
     *
     * @param ProductRepository $repository
     */
    public function __construct(ProductRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Get product by SKU
     *
     * @param string $sku
     * @return Product|null
     */
    public function getBySku(string $sku): ?Product
    {
        return $this->repository->findBySku($sku);
    }

    /**
     * Get active products
     *
     * @param array $config
     * @return Collection
     */
    public function getActiveProducts(array $config = []): Collection
    {
        return $this->repository->getActive($config);
    }

    /**
     * Get products by category
     *
     * @param int $categoryId
     * @param array $config
     * @return Collection
     */
    public function getByCategory(int $categoryId, array $config = []): Collection
    {
        return $this->repository->getByCategory($categoryId, $config);
    }

    /**
     * Get low stock products
     *
     * @param int $threshold
     * @return Collection
     */
    public function getLowStock(int $threshold = 10): Collection
    {
        return $this->repository->getLowStock($threshold);
    }

    /**
     * Before create hook - generate SKU if not provided
     *
     * @param array $data
     * @return array
     */
    protected function beforeCreate(array $data): array
    {
        // Auto-generate SKU if not provided
        if (empty($data['sku'])) {
            $data['sku'] = $this->generateSku($data['name'] ?? 'product');
        }

        // Ensure SKU is unique
        $counter = 1;
        $originalSku = $data['sku'];
        while ($this->repository->skuExists($data['sku'])) {
            $data['sku'] = $originalSku . '-' . $counter;
            $counter++;
        }

        // Set default status if not provided
        if (empty($data['status'])) {
            $data['status'] = 'active';
        }

        return $data;
    }

    /**
     * After create hook - log activity
     *
     * @param Model $model
     * @param array $data
     * @return void
     */
    protected function afterCreate(Model $model, array $data): void
    {
        // Log activity, send notifications, trigger events, etc.
        // event(new ProductCreated($model));
        
        \Log::info('Product created', [
            'product_id' => $model->id,
            'sku' => $model->sku,
            'name' => $model->name
        ]);
    }

    /**
     * Before update hook - validate business rules
     *
     * @param Model $existingModel
     * @param array $data
     * @return array
     */
    protected function beforeUpdate(Model $existingModel, array $data): array
    {
        // If SKU is being updated, ensure it's unique
        if (isset($data['sku']) && $data['sku'] !== $existingModel->sku) {
            if ($this->repository->skuExists($data['sku'], $existingModel->id)) {
                throw new \InvalidArgumentException('SKU already exists');
            }
        }

        return $data;
    }

    /**
     * After update hook - log activity
     *
     * @param Model $model
     * @param Model $existingModel
     * @param array $data
     * @return void
     */
    protected function afterUpdate(Model $model, Model $existingModel, array $data): void
    {
        // Log changes
        \Log::info('Product updated', [
            'product_id' => $model->id,
            'changes' => $model->getChanges()
        ]);
    }

    /**
     * Before delete hook - check if product can be deleted
     *
     * @param Model $model
     * @return void
     * @throws \Exception
     */
    protected function beforeDelete(Model $model): void
    {
        // Check if product has inventory
        if ($model->inventoryItems()->exists()) {
            throw new \Exception('Cannot delete product with existing inventory');
        }

        // Add other business rules validation here
    }

    /**
     * After delete hook - cleanup and logging
     *
     * @param Model $model
     * @return void
     */
    protected function afterDelete(Model $model): void
    {
        \Log::info('Product deleted', [
            'product_id' => $model->id,
            'sku' => $model->sku
        ]);
    }

    /**
     * Generate SKU from product name
     *
     * @param string $name
     * @return string
     */
    private function generateSku(string $name): string
    {
        $prefix = 'PRD';
        $slug = Str::upper(Str::slug(Str::substr($name, 0, 6), ''));
        $random = Str::upper(Str::random(4));
        
        return "{$prefix}-{$slug}-{$random}";
    }

    /**
     * Update product stock
     * Example of a custom business logic method
     *
     * @param int $productId
     * @param int $locationId
     * @param int $quantity
     * @param array $options
     * @return Product
     */
    public function updateStock(int $productId, int $locationId, int $quantity, array $options = []): Product
    {
        \DB::beginTransaction();

        try {
            $product = $this->repository->findOrFail($productId);

            // Find or create inventory item
            $inventoryItem = $product->inventoryItems()
                ->where('location_id', $locationId)
                ->first();

            if ($inventoryItem) {
                $inventoryItem->quantity += $quantity;
                $inventoryItem->save();
            } else {
                $product->inventoryItems()->create([
                    'location_id' => $locationId,
                    'quantity' => max(0, $quantity),
                    'batch_number' => $options['batch_number'] ?? null,
                    'expiry_date' => $options['expiry_date'] ?? null,
                ]);
            }

            \DB::commit();

            return $product->fresh(['inventoryItems']);
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }
}
