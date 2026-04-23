<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\Batch;

interface BatchRepositoryInterface extends RepositoryInterface
{
    public function save(Batch $batch): Batch;

    public function find(int|string $id, array $columns = ['*']): ?Batch;
}
