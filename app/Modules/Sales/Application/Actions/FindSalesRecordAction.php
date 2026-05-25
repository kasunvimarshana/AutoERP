<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Actions;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Modules\Sales\Domain\Exceptions\SalesRecordNotFoundException;

class FindSalesRecordAction
{
    public function execute(
        BaseRepositoryInterface $repository,
        string $resource,
        int|string $tenantId,
        int|string|null $id,
    ): Model {
        if ($id === null) {
            throw SalesRecordNotFoundException::for($resource, null);
        }

        $record = method_exists($repository, 'findForTenantById')
            ? $repository->findForTenantById($tenantId, $id)
            : $repository->getWhere(['tenant_id' => $tenantId, 'id' => $id])->first();

        if ($record === null) {
            throw SalesRecordNotFoundException::for($resource, $id);
        }

        return $record;
    }
}

