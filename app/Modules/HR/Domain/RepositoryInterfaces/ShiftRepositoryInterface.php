<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\Shift;

interface ShiftRepositoryInterface extends RepositoryInterface
{
    public function save(Shift $shift): Shift;

    public function find(int|string $id, array $columns = ['*']): ?Shift;

    public function findByTenantAndCode(int $tenantId, string $code): ?Shift;
}
