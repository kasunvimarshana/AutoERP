<?php

declare(strict_types=1);

namespace Modules\Tax\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Tax\Domain\Entities\TaxRule;

interface TaxRuleRepositoryInterface extends RepositoryInterface
{
    public function save(TaxRule $taxRule): TaxRule;

    public function findBestMatch(
        int $tenantId,
        ?int $productCategoryId,
        ?string $partyType,
        ?string $region,
    ): ?TaxRule;

    public function find(int|string $id, array $columns = ['*']): ?TaxRule;
}
