<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Purchase\Domain\Entities\GrnLine;

interface GrnLineRepositoryInterface extends RepositoryInterface
{
    public function save(GrnLine $line): GrnLine;

    public function find(int|string $id, array $columns = ['*']): ?GrnLine;

    public function findByGrnHeaderId(int $grnHeaderId): Collection;
}
