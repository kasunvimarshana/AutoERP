<?php

declare(strict_types=1);

namespace Modules\SystemUser\Application\Actions;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class DeleteSystemUserRecordAction
{
    public function execute(BaseRepositoryInterface $repository, Model|int|string $record): bool
    {
        return $repository->transaction(fn (): bool => $repository->delete($record));
    }
}

