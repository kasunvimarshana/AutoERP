<?php

namespace App\Saga\Steps;

use App\Repositories\InventoryRepository;
use App\Saga\Contracts\SagaInterface;

class ReserveInventoryStep implements SagaInterface
{
    public function getName(): string
    {
        return 'ReserveInventory';
    }

    public function execute(array &$context): array
    {
        /** @var InventoryRepository $inventoryRepo */
        $inventoryRepo = app(InventoryRepository::class);

        $reserved = [];

        foreach ($context['items'] as $item) {
            $inventoryRepo->reserveQuantity($item['product_id'], $item['quantity']);
            $reserved[] = ['product_id' => $item['product_id'], 'quantity' => $item['quantity']];
        }

        $context['reserved_inventory'] = $reserved;

        return $context;
    }

    public function compensate(array &$context): void
    {
        if (empty($context['reserved_inventory'])) {
            return;
        }

        /** @var InventoryRepository $inventoryRepo */
        $inventoryRepo = app(InventoryRepository::class);

        foreach ($context['reserved_inventory'] as $reservation) {
            try {
                $inventoryRepo->releaseReservation(
                    $reservation['product_id'],
                    $reservation['quantity']
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error(
                    "[ReserveInventoryStep] Compensation failed for product {$reservation['product_id']}: " . $e->getMessage()
                );
            }
        }

        unset($context['reserved_inventory']);
    }
}
