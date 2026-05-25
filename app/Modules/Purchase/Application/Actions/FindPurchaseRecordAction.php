<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Actions;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Modules\Purchase\Domain\Exceptions\PurchaseRecordNotFoundException;

class FindPurchaseRecordAction
{
    public function execute(
        BaseRepositoryInterface $repository,
        string $resource,
        int|string $tenantId,
        int|string|null $id,
    ): Model {
        if ($id === null) {
            throw PurchaseRecordNotFoundException::for($resource, null);
        }

        $record = method_exists($repository, 'findForTenantById')
            ? $repository->findForTenantById($tenantId, $id)
            : $repository->getWhere(['tenant_id' => $tenantId, 'id' => $id])->first();

        if ($record === null) {
            throw PurchaseRecordNotFoundException::for($resource, $id);
        }

        return $record;
    }
}

