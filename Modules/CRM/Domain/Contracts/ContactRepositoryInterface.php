<?php

declare(strict_types=1);

namespace Modules\Crm\Domain\Contracts;

use Modules\Crm\Domain\Entities\Contact;

interface ContactRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?Contact;

    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array;

    public function save(Contact $contact): Contact;

    public function delete(int $id, int $tenantId): void;
}
