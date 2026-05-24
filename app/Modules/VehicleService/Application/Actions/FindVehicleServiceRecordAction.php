<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Actions;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Modules\VehicleService\Domain\Exceptions\VehicleServiceRecordNotFoundException;

class FindVehicleServiceRecordAction
{
    public function execute(
        BaseRepositoryInterface $repository,
        string $resource,
        int|string $tenantId,
        int|string|null $id,
    ): Model {
        if ($id === null) {
            throw VehicleServiceRecordNotFoundException::for($resource, null);
        }

        $record = method_exists($repository, 'findForTenantById')
            ? $repository->findForTenantById($tenantId, $id)
            : $repository->getWhere(['tenant_id' => $tenantId, 'id' => $id])->first();

        if ($record === null) {
            throw VehicleServiceRecordNotFoundException::for($resource, $id);
        }

        return $record;
    }
}
