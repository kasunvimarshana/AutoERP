<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Contracts;

use Modules\Tax\Domain\Entities\TaxGroupRate;

interface TaxGroupRateServiceInterface
{
    public function getTaxGroupRate(string $tenantId, string $id): TaxGroupRate;

    public function createTaxGroupRate(string $tenantId, array $data): TaxGroupRate;

    public function updateTaxGroupRate(string $tenantId, string $id, array $data): TaxGroupRate;

    public function deleteTaxGroupRate(string $tenantId, string $id): void;

    /** @return TaxGroupRate[] */
    public function getRatesForGroup(string $tenantId, string $taxGroupId): array;
}
