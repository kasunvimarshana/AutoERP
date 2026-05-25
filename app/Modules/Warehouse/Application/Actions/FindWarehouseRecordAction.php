<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Actions;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;
use Modules\Warehouse\Domain\Exceptions\WarehouseRecordNotFoundException;

class FindWarehouseRecordAction
{
    public function execute(RepositoryPortInterface $repository, string $resource, int|string $id): DataRecord
    {
        $record = $repository->findById($id);

        if ($record === null) {
            throw WarehouseRecordNotFoundException::for($resource, $id);
        }

        return $record;
    }
}
