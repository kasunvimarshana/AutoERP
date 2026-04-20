<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\CreateStockReservationServiceInterface;
use Modules\Inventory\Domain\Entities\StockReservation;
use Modules\Inventory\Domain\RepositoryInterfaces\StockReservationRepositoryInterface;

class CreateStockReservationService implements CreateStockReservationServiceInterface
{
    public function __construct(private readonly StockReservationRepositoryInterface $stockReservationRepository) {}

    public function execute(array $data): StockReservation
    {
        $reservation = new StockReservation(
            tenantId: (int) $data['tenant_id'],
            productId: (int) $data['product_id'],
            variantId: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            batchId: isset($data['batch_id']) ? (int) $data['batch_id'] : null,
            serialId: isset($data['serial_id']) ? (int) $data['serial_id'] : null,
            locationId: (int) $data['location_id'],
            quantity: (string) $data['quantity'],
            reservedForType: $data['reserved_for_type'] ?? null,
            reservedForId: isset($data['reserved_for_id']) ? (int) $data['reserved_for_id'] : null,
            expiresAt: $data['expires_at'] ?? null,
        );

        return $this->stockReservationRepository->create($reservation);
    }
}
