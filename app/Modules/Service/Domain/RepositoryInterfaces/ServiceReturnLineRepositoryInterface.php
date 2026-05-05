<?php

declare(strict_types=1);

namespace Modules\Service\Domain\RepositoryInterfaces;

use Modules\Service\Domain\Entities\ServiceReturnLine;

interface ServiceReturnLineRepositoryInterface
{
    public function findById(int $tenantId, int $id): ?ServiceReturnLine;

    /** @return ServiceReturnLine[] */
    public function findByReturn(int $tenantId, int $returnId): array;

    public function save(ServiceReturnLine $line): ServiceReturnLine;

    public function delete(int $tenantId, int $id): bool;
}
