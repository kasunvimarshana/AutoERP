<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\Contracts\RepositoryPortInterface;

interface AuthSessionRepositoryInterface extends RepositoryPortInterface
{
    /**
     * @return list<DataRecord>
     */
    public function listActiveByUser(?int $tenantId, int $userId): array;
}
