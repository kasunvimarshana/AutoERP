<?php

declare(strict_types=1);

namespace Modules\Pricing\Services;

use Modules\Core\Helpers\TransactionHelper;
use Modules\Pricing\Models\ProductPrice;
use Modules\Pricing\Repositories\ProductPriceRepository;

/**
 * PriceManagementService
 *
 * Handles business logic for product price management including
 * create, update, and delete operations with proper transaction handling.
 */
class PriceManagementService
{
    public function __construct(
        private ProductPriceRepository $priceRepository
    ) {}

    /**
     * Create a new product price.
     */
    public function createPrice(array $data): ProductPrice
    {
        return TransactionHelper::execute(function () use ($data) {
            $price = $this->priceRepository->create($data);

            // Log activity
            activity()
                ->performedOn($price)
                ->causedBy(auth()->user())
                ->withProperties([
                    'strategy' => $price->strategy->value,
                    'price' => $price->price,
                    'location_id' => $price->location_id,
                ])
                ->log('Created product price');

            return $price->load(['product', 'location']);
        });
    }

    /**
     * Update an existing product price.
     */
    public function updatePrice(string $id, array $data): ProductPrice
    {
        return TransactionHelper::execute(function () use ($id, $data) {
            $price = $this->priceRepository->findOrFail($id);

            $oldData = $price->only(['strategy', 'price', 'config', 'location_id', 'is_active']);

            $price = $this->priceRepository->update($id, $data);

            // Log activity
            activity()
                ->performedOn($price)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old' => $oldData,
                    'new' => $price->only(['strategy', 'price', 'config', 'location_id', 'is_active']),
                ])
                ->log('Updated product price');

            return $price->load(['product', 'location']);
        });
    }

    /**
     * Delete a product price.
     */
    public function deletePrice(string $id): bool
    {
        return TransactionHelper::execute(function () use ($id) {
            $price = $this->priceRepository->findOrFail($id);

            // Log activity before deletion
            activity()
                ->performedOn($price)
                ->causedBy(auth()->user())
                ->withProperties([
                    'strategy' => $price->strategy->value,
                    'price' => $price->price,
                    'location_id' => $price->location_id,
                ])
                ->log('Deleted product price');

            return $this->priceRepository->delete($id);
        });
    }
}
