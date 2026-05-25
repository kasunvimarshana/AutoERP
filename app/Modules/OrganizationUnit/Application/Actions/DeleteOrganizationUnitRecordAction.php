<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Actions;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class DeleteOrganizationUnitRecordAction
{
    public function execute(BaseRepositoryInterface $repository, Model|int|string $record): bool
    {
        return $repository->transaction(fn (): bool => $repository->delete($record));
    }
}

