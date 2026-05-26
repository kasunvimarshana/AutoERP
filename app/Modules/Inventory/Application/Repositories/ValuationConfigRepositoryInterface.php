<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface ValuationConfigRepositoryInterface extends RepositoryPortInterface
{
	/**
	 * @param array<string, mixed> $criteria
	 */
	public function findActiveForDimensions(array $criteria): ?DataRecord;
}
