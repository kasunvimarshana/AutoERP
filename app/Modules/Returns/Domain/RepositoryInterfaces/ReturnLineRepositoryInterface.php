<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\RepositoryInterfaces;

use Modules\Returns\Domain\Entities\ReturnLine;

interface ReturnLineRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?ReturnLine;

    public function findByReturn(string $tenantId, string $returnType, string $returnId): array;

    public function save(ReturnLine $line): void;

    public function delete(string $tenantId, string $id): void;
}
