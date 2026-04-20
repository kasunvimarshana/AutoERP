<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\ReleaseExpiredStockReservationsServiceInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\StockReservationRepositoryInterface;

class ReleaseExpiredStockReservationsService implements ReleaseExpiredStockReservationsServiceInterface
{
    public function __construct(private readonly StockReservationRepositoryInterface $stockReservationRepository) {}

    public function execute(int $tenantId, ?string $expiresBefore = null): int
    {
        return $this->stockReservationRepository->deleteExpired($tenantId, $expiresBefore);
    }
}
