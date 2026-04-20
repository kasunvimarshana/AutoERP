<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\ReleaseStockReservationServiceInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\StockReservationRepositoryInterface;

class ReleaseStockReservationService implements ReleaseStockReservationServiceInterface
{
    public function __construct(private readonly StockReservationRepositoryInterface $stockReservationRepository) {}

    public function execute(int $tenantId, int $reservationId): bool
    {
        return $this->stockReservationRepository->delete($tenantId, $reservationId);
    }
}
