<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository
{
    public function all()
    {
        return Product::with(['parentProduct', 'variants', 'inventoryItems'])->get();
    }

    public function find($id)
    {
        return Product::with(['parentProduct', 'variants', 'inventoryItems'])->findOrFail($id);
    }

    public function findBySku($sku)
    {
        return Product::where('sku', $sku)->firstOrFail();
    }

    public function create(array $data)
    {
        return Product::create($data);
    }

    public function update($id, array $data)
    {
        $product = $this->find($id);
        $product->update($data);
        return $product;
    }

    public function delete($id)
    {
        return Product::destroy($id);
    }
}
