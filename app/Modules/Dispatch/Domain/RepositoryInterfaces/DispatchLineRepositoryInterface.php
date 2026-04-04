<?php

namespace Modules\Dispatch\Domain\RepositoryInterfaces;

use Modules\Dispatch\Domain\Entities\DispatchLine;

interface DispatchLineRepositoryInterface
{
    public function findById(int $id): ?DispatchLine;
    public function findByDispatch(int $dispatchId): array;
    public function create(array $data): DispatchLine;
    public function update(DispatchLine $line, array $data): DispatchLine;
}
