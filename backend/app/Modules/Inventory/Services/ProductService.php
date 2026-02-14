<?php

namespace App\Modules\Inventory\Services;

use App\Core\BaseService;
use App\Modules\Inventory\Repositories\ProductRepository;
use App\Modules\Inventory\Events\ProductCreated;
use App\Modules\Inventory\Events\ProductUpdated;

class ProductService extends BaseService
{
    protected ProductRepository $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createProduct(array $data)
    {
        return $this->transaction(function () use ($data) {
            $product = $this->repository->create($data);
            
            $this->logActivity('Product created', ['product_id' => $product->id]);
            
            event(new ProductCreated($product));
            
            return $product;
        });
    }

    public function updateProduct(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                $product = $this->repository->find($id);
                
                $this->logActivity('Product updated', ['product_id' => $id]);
                
                event(new ProductUpdated($product));
            }
            
            return $result;
        });
    }

    public function getProduct(int $id)
    {
        return $this->repository->with(['skus', 'tenant'])->find($id);
    }

    public function getAllProducts()
    {
        return $this->repository->with(['skus'])->all();
    }

    public function deleteProduct(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->delete($id);
            
            if ($result) {
                $this->logActivity('Product deleted', ['product_id' => $id]);
            }
            
            return $result;
        });
    }
}
