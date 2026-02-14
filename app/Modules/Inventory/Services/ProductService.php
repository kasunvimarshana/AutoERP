<?php

namespace App\Modules\Inventory\Services;

use App\Core\Services\BaseService;
use App\Modules\Inventory\Repositories\ProductRepository;
use Illuminate\Support\Facades\Log;

class ProductService extends BaseService
{
    /**
     * ProductService constructor
     */
    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get active products
     */
    public function getActive()
    {
        try {
            return $this->repository->getActive();
        } catch (\Exception $e) {
            Log::error('Error fetching active products: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Search products
     */
    public function search(string $term)
    {
        try {
            return $this->repository->search($term);
        } catch (\Exception $e) {
            Log::error("Error searching products with term '{$term}': ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get low stock products
     */
    public function getLowStock()
    {
        try {
            return $this->repository->getLowStock();
        } catch (\Exception $e) {
            Log::error('Error fetching low stock products: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get products by category
     */
    public function getByCategory(int $categoryId)
    {
        try {
            return $this->repository->getByCategory($categoryId);
        } catch (\Exception $e) {
            Log::error("Error fetching products by category {$categoryId}: ".$e->getMessage());
            throw $e;
        }
    }
}
