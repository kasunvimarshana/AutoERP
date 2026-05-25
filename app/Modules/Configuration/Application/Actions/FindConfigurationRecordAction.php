<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Actions;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Modules\Configuration\Domain\Exceptions\ConfigurationRecordNotFoundException;

class FindConfigurationRecordAction
{
    public function execute(BaseRepositoryInterface $repository, string $resource, int|string $id): Model
    {
        $record = $repository->findById($id);

        if ($record === null) {
            throw ConfigurationRecordNotFoundException::for($resource, $id);
        }

        return $record;
    }
}

