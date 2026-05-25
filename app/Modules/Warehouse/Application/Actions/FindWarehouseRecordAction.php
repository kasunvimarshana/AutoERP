<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Actions;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Modules\Warehouse\Domain\Exceptions\WarehouseRecordNotFoundException;

class FindWarehouseRecordAction
{
    public function execute(BaseRepositoryInterface $repository, string $resource, int|string $id): Model
    {
        $record = $repository->findById($id);

        if ($record === null) {
            throw WarehouseRecordNotFoundException::for($resource, $id);
        }

        return $record;
    }
}

