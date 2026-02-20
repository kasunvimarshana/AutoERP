<?php

namespace App\Services;

use App\Models\ProductRack;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductRackService
{
    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ProductRack::where('tenant_id', $tenantId)
            ->with(['product', 'businessLocation']);

        if (isset($filters['business_location_id'])) {
            $query->where('business_location_id', $filters['business_location_id']);
        }
        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        return $query->paginate($perPage);
    }

    public function upsert(array $data): ProductRack
    {
        return DB::transaction(function () use ($data) {
            return ProductRack::updateOrCreate(
                [
                    'business_location_id' => $data['business_location_id'],
                    'product_id' => $data['product_id'],
                ],
                $data
            );
        });
    }

    public function delete(string $id): void
    {
        ProductRack::findOrFail($id)->delete();
    }
}
