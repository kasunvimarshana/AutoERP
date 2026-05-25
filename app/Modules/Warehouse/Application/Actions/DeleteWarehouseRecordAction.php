<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Actions;

use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

class DeleteWarehouseRecordAction
{
    public function execute(RepositoryPortInterface $repository, int|string $id): bool
    {
        return $repository->transaction(fn (): bool => $repository->delete($id));
    }
}
