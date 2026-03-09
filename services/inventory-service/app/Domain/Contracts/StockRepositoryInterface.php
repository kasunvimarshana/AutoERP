<?php

namespace App\Domain\Contracts;

interface StockRepositoryInterface
{
    public function getStockLevel(string $tenantId, string $productId, string $warehouseId): ?object;
    public function getOrCreateStockLevel(string $tenantId, string $productId, string $warehouseId): object;
    public function getTotalStock(string $tenantId, string $productId): array;
    public function updateStockLevel(string $tenantId, string $productId, string $warehouseId, array $data): object;
    public function reserveStock(string $tenantId, string $productId, string $warehouseId, int $quantity, array $reservationData): object;
    public function commitReservation(string $tenantId, string $reservationId, ?string $performedBy = null): array;
    public function releaseReservation(string $tenantId, string $reservationId, ?string $performedBy = null): array;
    public function getExpiredReservations(): mixed;
    public function findReservationById(string $tenantId, string $reservationId): ?object;
    public function getStockByWarehouse(string $tenantId, string $warehouseId): mixed;
    public function getStockByProduct(string $tenantId, string $productId): mixed;
    public function lockStockLevelForUpdate(string $tenantId, string $productId, string $warehouseId): ?object;
}
