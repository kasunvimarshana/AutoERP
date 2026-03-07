<?php

declare(strict_types=1);

namespace App\Application\Saga\Steps;

use App\Application\Saga\Contracts\SagaInterface;
use App\Domain\Inventory\Contracts\InventoryRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Saga step: Reserve stock for all items in the order.
 *
 * Execute  — locks the required quantity as reserved for each line item.
 * Compensate — releases the reservation for each item.
 */
final class ReserveStockStep implements SagaInterface
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventoryRepository
    ) {}

    public function name(): string
    {
        return 'ReserveStock';
    }

    /**
     * @param  array{items: array<array{product_id: int, quantity: int}>} $context
     */
    public function execute(array $context): array
    {
        $reserved = [];

        foreach ($context['items'] as $item) {
            $product = $this->inventoryRepository->findOrFail($item['product_id']);

            $this->inventoryRepository->adjustStock(
                $item['product_id'],
                -$item['quantity']  // Negative delta = reserve
            );

            $reserved[] = [
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
            ];

            Log::debug("[ReserveStockStep] Reserved {$item['quantity']} units of product #{$item['product_id']}");
        }

        $context['reserved_items'] = $reserved;

        return $context;
    }

    public function compensate(array $context): void
    {
        foreach ($context['reserved_items'] ?? [] as $item) {
            try {
                $this->inventoryRepository->adjustStock(
                    $item['product_id'],
                    $item['quantity']  // Positive delta = release
                );

                Log::debug("[ReserveStockStep:compensate] Released {$item['quantity']} units of product #{$item['product_id']}");
            } catch (\Throwable $e) {
                Log::error("[ReserveStockStep:compensate] Failed to release stock for product #{$item['product_id']}: {$e->getMessage()}");
            }
        }
    }
}
