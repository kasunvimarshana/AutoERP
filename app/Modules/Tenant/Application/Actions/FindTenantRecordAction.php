<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Actions;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Domain\Exceptions\TenantRecordNotFoundException;

class FindTenantRecordAction
{
    public function execute(BaseRepositoryInterface $repository, string $resource, int|string $id): Model
    {
        $record = $repository->findById($id);

        if ($record === null) {
            throw TenantRecordNotFoundException::for($resource, $id);
        }

        return $record;
    }
}
