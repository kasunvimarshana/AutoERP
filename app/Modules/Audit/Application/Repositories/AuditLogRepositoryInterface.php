<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Repositories;

use Modules\Audit\Application\DTOs\AuditLogQueryData;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface AuditLogRepositoryInterface extends RepositoryPortInterface
{
	/**
	 * @param array<string, mixed> $attributes
	 */
	public function append(array $attributes): DataRecord;

	public function pageByQuery(AuditLogQueryData $query): PagedResult;
}
