<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Actions;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Modules\Vehicle\Domain\Exceptions\VehicleRecordNotFoundException;

class FindVehicleRecordAction
{
    public function execute(BaseRepositoryInterface $repository, string $resource, int|string $id): Model
    {
        $record = $repository->findById($id);

        if ($record === null) {
            throw VehicleRecordNotFoundException::for($resource, $id);
        }

        return $record;
    }
}

