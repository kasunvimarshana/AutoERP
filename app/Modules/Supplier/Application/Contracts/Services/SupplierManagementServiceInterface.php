<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface SupplierManagementServiceInterface
{
    public function listSuppliers(array $filters, int $perPage, int $page): Result;

    public function getSupplier(int|string $id): Result;

    public function createSupplier(array $payload): Result;

    public function updateSupplier(int|string $id, array $payload): Result;

    public function changeStatus(int|string $id, string $toStatus, ?string $reason = null): Result;

    public function safeDeleteSupplier(int|string $id): Result;

    public function lookupSuppliers(string $search, int $limit = 20): Result;

    public function validateSupplierForContext(int|string $id, string $context): Result;

    public function getFinanceDefaults(int|string $id): Result;

    public function updateFinanceDefaults(int|string $id, array $payload): Result;

    public function listSupplierUserAccounts(int|string $supplierId): Result;

    public function createSupplierUserAccess(int|string $supplierId, array $payload): Result;

    public function linkExistingUser(int|string $supplierId, array $payload): Result;

    public function deactivateSupplierUserAccess(int|string $supplierId, int|string $accessId, array $payload): Result;

    public function unlinkSupplierUserAccess(int|string $supplierId, int|string $accessId): Result;
}
