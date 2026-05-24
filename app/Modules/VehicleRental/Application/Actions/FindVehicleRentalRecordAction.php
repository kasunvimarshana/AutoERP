<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Actions;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Modules\VehicleRental\Domain\Exceptions\VehicleRentalRecordNotFoundException;

class FindVehicleRentalRecordAction
{
    public function execute(BaseRepositoryInterface $repository, string $resource, int|string $tenantId, int|string|null $id): Model
    {
        if ($id === null) {
            throw VehicleRentalRecordNotFoundException::for($resource, null);
        }

        $record = method_exists($repository, 'findForTenantById')
            ? $repository->findForTenantById($tenantId, $id)
            : $repository->getWhere(['tenant_id' => $tenantId, 'id' => $id])->first();

        if ($record === null) {
            throw VehicleRentalRecordNotFoundException::for($resource, $id);
        }

        return $record;
    }
}
