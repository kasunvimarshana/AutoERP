<?php

declare(strict_types=1);

namespace Modules\Dispatch\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Dispatch\Domain\Entities\DispatchLine;

interface DispatchLineRepositoryInterface extends RepositoryInterface
{
    public function save(DispatchLine $line): DispatchLine;
    public function findById(int $id): ?DispatchLine;
    public function delete(mixed $id): bool;
    public function list(array $filters = [], ?int $perPage = null, int $page = 1): mixed;
    public function findByDispatch(int $dispatchId): Collection;
}
