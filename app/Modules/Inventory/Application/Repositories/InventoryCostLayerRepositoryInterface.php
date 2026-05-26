<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface InventoryCostLayerRepositoryInterface extends RepositoryPortInterface
{
	/**
	 * @param array<string, mixed> $criteria
	 * @return list<DataRecord>
	 */
	public function listOpenLayers(array $criteria, string $valuationMethod, int $limit = 1000): array;
}
