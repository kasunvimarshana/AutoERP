<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Contracts;

use Modules\Tax\Domain\Entities\TaxGroup;

interface TaxGroupServiceInterface
{
    public function getTaxGroup(string $tenantId, string $id): TaxGroup;

    public function createTaxGroup(string $tenantId, array $data): TaxGroup;

    public function updateTaxGroup(string $tenantId, string $id, array $data): TaxGroup;

    public function deleteTaxGroup(string $tenantId, string $id): void;

    /** @return TaxGroup[] */
    public function getAllTaxGroups(string $tenantId): array;
}
