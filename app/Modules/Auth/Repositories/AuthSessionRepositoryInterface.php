<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface AuthSessionRepositoryInterface extends RepositoryPortInterface
{
    /**
     * @return list<DataRecord>
     */
    public function listActiveByUser(?int $tenantId, int $userId): array;
}
