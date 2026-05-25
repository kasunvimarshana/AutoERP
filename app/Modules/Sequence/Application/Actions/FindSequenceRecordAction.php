<?php

declare(strict_types=1);

namespace Modules\Sequence\Application\Actions;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Modules\Sequence\Domain\Exceptions\SequenceRecordNotFoundException;

class FindSequenceRecordAction
{
    public function execute(BaseRepositoryInterface $repository, string $resource, int|string $id): Model
    {
        $record = $repository->findById($id);

        if ($record === null) {
            throw SequenceRecordNotFoundException::for($resource, $id);
        }

        return $record;
    }
}

