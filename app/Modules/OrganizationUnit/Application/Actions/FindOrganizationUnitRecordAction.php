<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Actions;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitRecordNotFoundException;

class FindOrganizationUnitRecordAction
{
    public function execute(BaseRepositoryInterface $repository, string $resource, int|string $id): Model
    {
        $record = $repository->findById($id);

        if ($record === null) {
            throw OrganizationUnitRecordNotFoundException::for($resource, $id);
        }

        return $record;
    }
}
