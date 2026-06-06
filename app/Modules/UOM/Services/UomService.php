<?php

declare(strict_types=1);

namespace Modules\UOM\Services;

use Modules\Core\Results\Result;
use Modules\UOM\Services\UnitOfMeasures\CreateUnitOfMeasureService;
use Modules\UOM\Services\UnitOfMeasures\UpdateUnitOfMeasureService;

final class UomService
{
    public function __construct(
        private readonly CreateUnitOfMeasureService $createService,
        private readonly UpdateUnitOfMeasureService $updateService,
    ) {}

    public function create(array $payload): Result
    {
        return $this->createService->execute($payload);
    }

    public function update(int|string $id, array $payload): Result
    {
        return $this->updateService->execute($id, $payload);
    }

    public function activate(int|string $id): Result
    {
        return $this->update($id, ['is_active' => true]);
    }

    public function deactivate(int|string $id): Result
    {
        return $this->update($id, ['is_active' => false]);
    }
}
