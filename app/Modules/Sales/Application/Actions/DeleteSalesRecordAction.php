<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Actions;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class DeleteSalesRecordAction
{
    public function execute(BaseRepositoryInterface $repository, Model|int|string $record): bool
    {
        return $repository->delete($record);
    }
}
