<?php

declare(strict_types=1);

namespace Modules\HR\Application\Actions;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Modules\HR\Domain\Exceptions\HRRecordNotFoundException;

class FindHRRecordAction
{
    public function execute(BaseRepositoryInterface $repository, string $resource, int|string $tenantId, int|string|null $id): Model
    {
        if ($id === null) {
            throw HRRecordNotFoundException::for($resource, null);
        }

        $record = method_exists($repository, 'findForTenantById')
            ? $repository->findForTenantById($tenantId, $id)
            : $repository->getWhere(['tenant_id' => $tenantId, 'id' => $id])->first();

        if ($record === null) {
            throw HRRecordNotFoundException::for($resource, $id);
        }

        return $record;
    }
}
