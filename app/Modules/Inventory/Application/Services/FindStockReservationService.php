<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\FindStockReservationServiceInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\StockReservationRepositoryInterface;

class FindStockReservationService implements FindStockReservationServiceInterface
{
    public function __construct(private readonly StockReservationRepositoryInterface $stockReservationRepository) {}

    public function find(int $tenantId, int $reservationId): mixed
    {
        return $this->stockReservationRepository->findById($tenantId, $reservationId);
    }

    public function list(int $tenantId, int $perPage = 15, int $page = 1): mixed
    {
        return $this->stockReservationRepository->paginate($tenantId, $perPage, $page);
    }
}
