<?php

namespace Modules\Inventory\Domain\Contracts;

interface CycleCountRepositoryInterface
{
    public function findById(string $id): ?object;

    public function findLineById(string $lineId): ?object;

    public function create(array $data): object;

    public function update(string $id, array $data): object;

    public function createLine(array $data): object;

    public function updateLine(string $lineId, array $data): object;

    public function linesForCount(string $cycleCountId): array;

    public function paginate(string $tenantId, array $filters = [], int $perPage = 20): object;
}
