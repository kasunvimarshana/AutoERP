<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Actions;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class DeleteVehicleRentalRecordAction
{
    public function execute(BaseRepositoryInterface $repository, Model|int|string $record): bool
    {
        return $repository->delete($record);
    }
}
