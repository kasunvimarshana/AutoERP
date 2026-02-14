<?php

namespace App\Modules\Inventory\Services;

use App\Core\Services\BaseService;
use App\Modules\Inventory\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Product Service
 * 
 * Handles business logic for product operations
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
        $this->repository = $repository;
    }

    /**
     * Create a new product
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        if (empty($data['sku'])) {
            $data['sku'] = $this->generateSku($data);
        }

        if (!isset($data['current_stock'])) {
            $data['current_stock'] = 0;
        }

        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        return parent::create($data);
    }

    /**
     * Generate SKU for product
     *
     * @param array $data
     * @return string
     */
    protected function generateSku(array $data): string
    {
        $prefix = 'PRD-';
        $lastProduct = $this->repository->model
            ->where('sku', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->first();
        
        $number = 1;
        if ($lastProduct && str_starts_with($lastProduct->sku, $prefix)) {
            $extracted = substr($lastProduct->sku, strlen($prefix));
            if (is_numeric($extracted)) {
                $number = (int)$extracted + 1;
            }
        }
        
        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Find product by SKU
     *
     * @param string $sku
     * @return mixed
     */
    public function findBySku(string $sku)
    {
        return $this->repository->findBySku($sku);
    }

    /**
     * Get low stock products
     *
     * @return Collection
     */
    public function getLowStockProducts(): Collection
    {
        return $this->repository->lowStockProducts();
    }

    /**
     * Search products
     *
     * @param string $search
     * @return Collection
     */
    public function searchProducts(string $search): Collection
    {
        return $this->repository->searchProducts($search);
    }

    /**
     * Get active products
     *
     * @return Collection
     */
    public function getActiveProducts(): Collection
    {
        return $this->repository->getActiveProducts();
    }

    /**
     * Get inventory levels for a product
     *
     * @param int $productId
     * @return mixed
     */
    public function getInventoryLevels(int $productId)
    {
        $product = $this->find($productId);
        if (!$product) {
            return null;
        }

        return [
            'product_id' => $productId,
            'current_stock' => $product->current_stock ?? 0,
            'reorder_level' => $product->reorder_level ?? 0,
            'reorder_quantity' => $product->reorder_quantity ?? 0,
            'needs_reorder' => ($product->current_stock ?? 0) <= ($product->reorder_level ?? 0),
        ];
    }

    /**
     * Adjust stock for a product
     *
     * @param int $productId
     * @param array $data
     * @return mixed
     */
    public function adjustStock(int $productId, array $data)
    {
        return DB::transaction(function () use ($productId, $data) {
            $product = $this->find($productId);
            if (!$product) {
                throw new \Exception('Product not found');
            }

            $currentStock = $product->current_stock ?? 0;
            $newStock = $currentStock;

            switch ($data['type']) {
                case 'addition':
                    $newStock = $currentStock + $data['quantity'];
                    break;
                case 'subtraction':
                    $newStock = $currentStock - $data['quantity'];
                    if ($newStock < 0) {
                        throw new \Exception("Insufficient stock. Available: {$currentStock}, Requested: {$data['quantity']}");
                    }
                    break;
                case 'adjustment':
                    $newStock = $data['quantity'];
                    break;
            }

            $product->current_stock = $newStock;
            $product->save();

            return [
                'product_id' => $productId,
                'previous_stock' => $currentStock,
                'adjustment' => $data['quantity'],
                'type' => $data['type'],
                'new_stock' => $product->current_stock,
            ];
        });
    }
}
