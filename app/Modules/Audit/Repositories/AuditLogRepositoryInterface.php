<?php

declare(strict_types=1);

namespace Modules\Audit\Repositories;

use Modules\Audit\DTOs\AuditLogQueryData;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Repositories\Contracts\RepositoryPortInterface;

interface AuditLogRepositoryInterface extends RepositoryPortInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function append(array $attributes): DataRecord;

    public function pageByQuery(AuditLogQueryData $query): PagedResult;
}
