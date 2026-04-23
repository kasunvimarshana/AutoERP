<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\PayrollItem;

interface PayrollItemRepositoryInterface extends RepositoryInterface
{
    public function save(PayrollItem $item): PayrollItem;

    public function find(int|string $id, array $columns = ['*']): ?PayrollItem;

    public function findByTenantAndCode(int $tenantId, string $code): ?PayrollItem;
}
