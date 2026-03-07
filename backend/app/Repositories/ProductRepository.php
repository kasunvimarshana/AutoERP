<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository extends BaseRepository
{
    protected array $searchableColumns = ['name', 'sku', 'barcode', 'description'];

    protected function model(): string
    {
        return Product::class;
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->newQuery()->where('sku', $sku)->first();
    }

    public function findByBarcode(string $barcode): ?Product
    {
        return $this->newQuery()->where('barcode', $barcode)->first();
    }

    public function getLowStockProducts(int $threshold = null): \Illuminate\Database\Eloquent\Collection
    {
        return $this->newQuery()
                    ->with('inventory')
                    ->whereHas('inventory', function ($q) use ($threshold) {
                        $q->whereRaw('quantity <= COALESCE(?, products.low_stock_threshold)', [$threshold]);
                    })
                    ->where('is_trackable', true)
                    ->get();
    }

    public function getByCategory(int $categoryId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->newQuery()->where('category_id', $categoryId)->get();
    }
}
