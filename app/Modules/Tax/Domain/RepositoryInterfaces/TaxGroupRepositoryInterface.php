<?php

declare(strict_types=1);

namespace Modules\Tax\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Tax\Domain\Entities\TaxGroup;

interface TaxGroupRepositoryInterface extends RepositoryInterface
{
    public function save(TaxGroup $taxGroup): TaxGroup;

    public function findByTenantAndName(int $tenantId, string $name): ?TaxGroup;

    public function find(int|string $id, array $columns = ['*']): ?TaxGroup;
}
