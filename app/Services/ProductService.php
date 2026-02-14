<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class ProductService
{
    protected $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function getAllProducts()
    {
        return $this->productRepository->all();
    }

    public function getProduct($id)
    {
        return $this->productRepository->find($id);
    }

    public function createProduct(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Set tenant_id from auth
            if (auth()->check()) {
                $data['tenant_id'] = auth()->user()->tenant_id;
            }

            $product = $this->productRepository->create($data);

            // Log product creation
            \Log::info('Product created', ['product_id' => $product->id]);

            return $product;
        });
    }

    public function updateProduct($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $product = $this->productRepository->update($id, $data);

            // Log product update
            \Log::info('Product updated', ['product_id' => $product->id]);

            return $product;
        });
    }

    public function deleteProduct($id)
    {
        return DB::transaction(function () use ($id) {
            $result = $this->productRepository->delete($id);

            // Log product deletion
            \Log::info('Product deleted', ['product_id' => $id]);

            return $result;
        });
    }
}
