<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\PerformanceCycle;

interface PerformanceCycleRepositoryInterface extends RepositoryInterface
{
    public function save(PerformanceCycle $cycle): PerformanceCycle;

    public function find(int|string $id, array $columns = ['*']): ?PerformanceCycle;
}
