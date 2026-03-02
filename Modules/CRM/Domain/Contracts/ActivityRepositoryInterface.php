<?php

declare(strict_types=1);

namespace Modules\Crm\Domain\Contracts;

use Modules\Crm\Domain\Entities\Activity;

interface ActivityRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?Activity;

    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array;

    public function findByContact(int $contactId, int $tenantId): array;

    public function findByLead(int $leadId, int $tenantId): array;

    public function save(Activity $activity): Activity;

    public function delete(int $id, int $tenantId): void;
}
