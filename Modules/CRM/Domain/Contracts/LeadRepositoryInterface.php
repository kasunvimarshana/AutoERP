<?php

declare(strict_types=1);

namespace Modules\Crm\Domain\Contracts;

use Modules\Crm\Domain\Entities\Lead;

interface LeadRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?Lead;

    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array;

    public function findByContact(int $contactId, int $tenantId): array;

    public function save(Lead $lead): Lead;

    public function delete(int $id, int $tenantId): void;
}
