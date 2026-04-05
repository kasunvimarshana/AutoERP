<?php
declare(strict_types=1);
namespace Modules\Configuration\Application\Contracts;

use Modules\Configuration\Domain\Entities\OrgUnit;

interface OrgUnitServiceInterface
{
    public function findById(int $id): OrgUnit;
    public function findByTenant(int $tenantId): array;
    public function getTree(int $tenantId): array;
    public function create(array $data): OrgUnit;
    public function update(int $id, array $data): OrgUnit;
    public function delete(int $id): bool;
    public function move(int $id, ?int $newParentId): OrgUnit;
}
