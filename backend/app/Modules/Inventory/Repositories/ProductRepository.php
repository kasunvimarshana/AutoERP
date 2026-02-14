<?php

namespace App\Modules\Inventory\Repositories;

use App\Core\BaseRepository;
use App\Modules\Inventory\Models\Product;

/**
 * Product Repository
 * 
 * Enhanced with dynamic query capabilities from the CRUD framework
 * 
 * @package App\Modules\Inventory\Repositories
 */
class ProductRepository extends BaseRepository
{
    /**
     * Allowed fields for filtering, sorting, and searching
     * 
     * @var array
     */
    protected array $allowedFields = [
        'id',
        'tenant_id',
        'name',
        'sku',
        'description',
        'category',
        'brand',
        'unit',
        'price',
        'cost',
        'status',
        'is_active',
        'created_at',
        'updated_at',
    ];

    /**
     * Allowed relations for eager loading
     * 
     * @var array
     */
    protected array $allowedRelations = [
        'tenant',
        'skus',
        'batches',
        'stockMovements',
    ];

    /**
     * Default searchable fields for global search
     * 
     * @var array
     */
    protected array $searchableFields = [
        'name',
        'sku',
        'description',
        'brand',
        'category',
    ];

    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    /**
     * Find product by name
     * 
     * @param string $name
     * @return Product|null
     */
    public function findByName(string $name): ?Product
    {
        return $this->findBy('name', $name);
    }

    /**
     * Get products by tenant
     * 
     * @param int $tenantId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByTenant(int $tenantId)
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }

    /**
     * Get products by category
     * 
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByCategory(string $category)
    {
        return $this->model->where('category', $category)->get();
    }

    /**
     * Get products by status
     * 
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get products by brand
     * 
     * @param string $brand
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByBrand(string $brand)
    {
        return $this->model->where('brand', $brand)->get();
    }
}

