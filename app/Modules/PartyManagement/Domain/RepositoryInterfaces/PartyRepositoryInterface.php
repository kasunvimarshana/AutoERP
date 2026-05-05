<?php

declare(strict_types=1);

namespace Modules\PartyManagement\Domain\RepositoryInterfaces;

use Modules\PartyManagement\Domain\Entities\Party;

interface PartyRepositoryInterface
{
    public function create(Party $party): Party;

    public function update(Party $party): Party;

    public function delete(int $tenantId, string $id): void;

    public function findById(int $tenantId, string $id): Party;

    public function getByTenant(int $tenantId, int $perPage = 15, int $page = 1): mixed;

    public function findByTaxNumber(int $tenantId, string $taxNumber): ?Party;
}
