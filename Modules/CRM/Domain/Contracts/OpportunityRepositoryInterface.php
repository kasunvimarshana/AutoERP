<?php

declare(strict_types=1);

namespace Modules\CRM\Domain\Contracts;

use Modules\CRM\Domain\Entities\Opportunity;

interface OpportunityRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?Opportunity;

    /** @return Opportunity[] */
    public function findAll(int $tenantId, ?string $stage, int $page, int $perPage): array;

    /** @return Opportunity[] */
    public function findOpen(int $tenantId): array;

    public function save(Opportunity $opportunity): Opportunity;

    public function delete(int $id, int $tenantId): void;
}
