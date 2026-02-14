<?php

namespace App\Repositories;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;

class InventoryRepository
{
    public function all()
    {
        return InventoryItem::with(['product', 'branch', 'movements'])->get();
    }

    public function find($id)
    {
        return InventoryItem::with(['product', 'branch', 'movements'])->findOrFail($id);
    }

    public function findByProductAndBranch($productId, $branchId, $batchNumber = null)
    {
        $query = InventoryItem::where('product_id', $productId)
            ->where('branch_id', $branchId);

        if ($batchNumber) {
            $query->where('batch_number', $batchNumber);
        }

        return $query->first();
    }

    public function create(array $data)
    {
        return InventoryItem::create($data);
    }

    public function update($id, array $data)
    {
        $item = $this->find($id);
        $item->update($data);
        return $item;
    }

    public function recordMovement(array $data)
    {
        return InventoryMovement::create($data);
    }
}
