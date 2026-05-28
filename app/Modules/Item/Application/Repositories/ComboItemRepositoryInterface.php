<?php

declare(strict_types=1);

namespace Modules\Item\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface ComboItemRepositoryInterface extends RepositoryPortInterface
{
    public function findByIdInTenant(int|string $id, int $tenantId): ?DataRecord;

    public function introducesCycle(
        int $tenantId,
        int $comboItemId,
        int $componentItemId,
        ?int $ignoreLinkId = null,
    ): bool;
}
