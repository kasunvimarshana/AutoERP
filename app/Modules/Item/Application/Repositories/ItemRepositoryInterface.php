<?php

declare(strict_types=1);

namespace Modules\Item\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface ItemRepositoryInterface extends RepositoryPortInterface
{
    public function findByIdInTenant(int|string $id, int $tenantId): ?DataRecord;

    /**
     * @param list<array<string, mixed>> $values
     */
    public function syncMetadataValues(int $tenantId, int $itemId, array $values): void;
}
