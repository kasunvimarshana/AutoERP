<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface AccountRepositoryInterface extends RepositoryPortInterface
{
    public function findPostableById(int|string $id, int $tenantId): ?DataRecord;
}
