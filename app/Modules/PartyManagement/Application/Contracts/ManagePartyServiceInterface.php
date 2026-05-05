<?php

declare(strict_types=1);

namespace Modules\PartyManagement\Application\Contracts;

use Modules\PartyManagement\Domain\Entities\Party;

interface ManagePartyServiceInterface
{
    public function create(array $data): Party;

    public function update(int $tenantId, string $id, array $data): Party;

    public function delete(int $tenantId, string $id): void;

    public function find(int $tenantId, string $id): Party;

    public function list(int $tenantId, int $perPage = 15, int $page = 1): mixed;
}
