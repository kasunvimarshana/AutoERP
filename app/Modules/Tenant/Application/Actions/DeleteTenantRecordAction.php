<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Actions;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class DeleteTenantRecordAction
{
    public function execute(BaseRepositoryInterface $repository, Model|int|string $record): bool
    {
        return $repository->transaction(fn (): bool => $repository->delete($record));
    }
}
