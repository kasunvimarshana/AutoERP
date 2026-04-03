<?php

declare(strict_types=1);

namespace Modules\Taxation\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Taxation\Domain\Entities\TaxRule;

interface TaxRuleRepositoryInterface extends RepositoryInterface
{
    public function save(TaxRule $taxRule): TaxRule;

    public function findByTaxRate(int $taxRateId): Collection;

    public function findByEntity(int $tenantId, string $entityType, ?int $entityId): Collection;
}
