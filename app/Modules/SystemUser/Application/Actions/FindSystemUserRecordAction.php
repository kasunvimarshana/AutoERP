<?php

declare(strict_types=1);

namespace Modules\SystemUser\Application\Actions;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Modules\SystemUser\Domain\Exceptions\SystemUserRecordNotFoundException;

class FindSystemUserRecordAction
{
    public function execute(BaseRepositoryInterface $repository, string $resource, int|string $id): Model
    {
        $record = $repository->findById($id);

        if ($record === null) {
            throw SystemUserRecordNotFoundException::for($resource, $id);
        }

        return $record;
    }
}
