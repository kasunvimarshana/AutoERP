<?php

namespace App\Domain\Contracts;

interface StockServiceInterface
{
    public function getStockLevel(string $tenantId, string $productId, string $warehouseId): object;

    public function adjustStock(
        string $tenantId,
        string $productId,
        string $warehouseId,
        float $quantity,
        string $type,
        ?string $referenceId   = null,
        ?string $referenceType = null,
        ?string $notes         = null,
        ?string $performedBy   = null,
    ): array;

    public function reserveStock(
        string $tenantId,
        string $productId,
        string $warehouseId,
        int $quantity,
        string $referenceId,
        string $referenceType,
        ?\DateTime $expiresAt  = null,
        ?string $notes         = null,
    ): object;

    public function commitReservation(string $tenantId, string $reservationId, ?string $performedBy = null): array;

    public function releaseReservation(string $tenantId, string $reservationId, ?string $performedBy = null): array;

    public function transferStock(
        string $tenantId,
        string $productId,
        string $fromWarehouseId,
        string $toWarehouseId,
        float $quantity,
        ?string $notes       = null,
        ?string $performedBy = null,
    ): array;

    public function expireReservations(): int;
}
