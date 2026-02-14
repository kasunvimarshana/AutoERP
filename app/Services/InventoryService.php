<?php

namespace App\Services;

use App\Repositories\InventoryRepository;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    protected $inventoryRepository;

    public function __construct(InventoryRepository $inventoryRepository)
    {
        $this->inventoryRepository = $inventoryRepository;
    }

    public function getAllInventory()
    {
        return $this->inventoryRepository->all();
    }

    public function getInventoryItem($id)
    {
        return $this->inventoryRepository->find($id);
    }

    public function recordMovement(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Set tenant_id and user
            if (auth()->check()) {
                $data['tenant_id'] = auth()->user()->tenant_id;
                $data['created_by'] = auth()->id();
            }

            // Find or create inventory item
            $inventoryItem = $this->inventoryRepository->findByProductAndBranch(
                $data['product_id'],
                $data['branch_id'],
                $data['batch_number'] ?? null
            );

            if (!$inventoryItem) {
                $inventoryItem = $this->inventoryRepository->create([
                    'tenant_id' => $data['tenant_id'],
                    'product_id' => $data['product_id'],
                    'branch_id' => $data['branch_id'],
                    'batch_number' => $data['batch_number'] ?? null,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]);
            }

            // Add inventory_item_id to movement data
            $data['inventory_item_id'] = $inventoryItem->id;

            // Record the movement
            $movement = $this->inventoryRepository->recordMovement($data);

            // Update inventory quantity based on movement type
            $this->updateInventoryQuantity($inventoryItem, $data['movement_type'], $data['quantity']);

            // Log inventory movement
            \Log::info('Inventory movement recorded', [
                'movement_id' => $movement->id,
                'type' => $data['movement_type'],
            ]);

            return $movement;
        });
    }

    protected function updateInventoryQuantity($inventoryItem, $type, $quantity)
    {
        switch ($type) {
            case InventoryMovement::TYPE_IN:
                $inventoryItem->quantity += $quantity;
                break;
            case InventoryMovement::TYPE_OUT:
                $inventoryItem->quantity -= $quantity;
                break;
            case InventoryMovement::TYPE_ADJUSTMENT:
                $inventoryItem->quantity = $quantity;
                break;
        }

        $inventoryItem->save();
    }
}
