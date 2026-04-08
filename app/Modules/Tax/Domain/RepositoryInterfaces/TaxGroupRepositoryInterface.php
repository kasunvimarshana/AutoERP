<?php

declare(strict_types=1);

namespace Modules\Tax\Domain\RepositoryInterfaces;

use Modules\Tax\Domain\Entities\TaxGroup;

interface TaxGroupRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?TaxGroup;

    public function findByCode(string $tenantId, string $code): ?TaxGroup;

    /** @return TaxGroup[] */
    public function findAll(string $tenantId): array;

    public function save(TaxGroup $group): void;

    public function delete(string $tenantId, string $id): void;
}
