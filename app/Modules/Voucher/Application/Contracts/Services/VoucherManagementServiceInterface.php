<?php

declare(strict_types=1);

namespace Modules\Voucher\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface VoucherManagementServiceInterface
{
    public function list(array $criteria = []): Result;
    public function create(array $payload): Result;
    public function getById(int $id): Result;
    public function update(int $id, array $payload): Result;
    public function delete(int $id): Result;
    public function upsertLines(int $voucherId, array $lines): Result;
    public function listAllocations(int $voucherId): Result;
    public function addAllocation(int $voucherId, array $payload): Result;
    public function updateAllocation(int $allocationId, array $payload): Result;
}