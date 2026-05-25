<?php

declare(strict_types=1);

namespace Modules\User\Application\Actions;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Modules\User\Domain\Exceptions\UserRecordNotFoundException;

class FindUserRecordAction
{
    public function execute(BaseRepositoryInterface $repository, string $resource, int|string $id): Model
    {
        $record = $repository->findById($id);

        if ($record === null) {
            throw UserRecordNotFoundException::for($resource, $id);
        }

        return $record;
    }
}

