<?php
declare(strict_types=1);
namespace Modules\Configuration\Application\Services;

use Modules\Configuration\Application\Contracts\OrgUnitServiceInterface;
use Modules\Configuration\Domain\Entities\OrgUnit;
use Modules\Configuration\Domain\Exceptions\OrgUnitNotFoundException;
use Modules\Configuration\Domain\RepositoryInterfaces\OrgUnitRepositoryInterface;

class OrgUnitService implements OrgUnitServiceInterface
{
    public function __construct(private readonly OrgUnitRepositoryInterface $repo) {}

    public function findById(int $id): OrgUnit
    {
        $unit = $this->repo->findById($id);
        if (!$unit) {
            throw new OrgUnitNotFoundException($id);
        }
        return $unit;
    }

    public function findByTenant(int $tenantId): array
    {
        return $this->repo->findByTenant($tenantId);
    }

    public function getTree(int $tenantId): array
    {
        return $this->repo->getTree($tenantId);
    }

    public function create(array $data): OrgUnit
    {
        return $this->repo->create($data);
    }

    public function update(int $id, array $data): OrgUnit
    {
        $unit = $this->repo->update($id, $data);
        if (!$unit) {
            throw new OrgUnitNotFoundException($id);
        }
        return $unit;
    }

    public function delete(int $id): bool
    {
        $unit = $this->repo->findById($id);
        if (!$unit) {
            throw new OrgUnitNotFoundException($id);
        }
        return $this->repo->delete($id);
    }

    public function move(int $id, ?int $newParentId): OrgUnit
    {
        return $this->repo->move($id, $newParentId);
    }
}
