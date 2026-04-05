<?php
declare(strict_types=1);
namespace Modules\Tax\Application\Services;

use Modules\Tax\Application\Contracts\TaxGroupServiceInterface;
use Modules\Tax\Domain\Entities\TaxGroup;
use Modules\Tax\Domain\Exceptions\TaxGroupNotFoundException;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRepositoryInterface;

class ManageTaxGroupService implements TaxGroupServiceInterface
{
    public function __construct(
        private readonly TaxGroupRepositoryInterface $repository,
    ) {}

    public function findById(int $id): TaxGroup
    {
        $group = $this->repository->findById($id);
        if ($group === null) {
            throw new TaxGroupNotFoundException($id);
        }
        return $group;
    }

    public function findAllByTenant(int $tenantId): array
    {
        return $this->repository->findAllByTenant($tenantId);
    }

    public function create(array $data): TaxGroup
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): TaxGroup
    {
        $this->findById($id);
        return $this->repository->update($id, $data) ?? $this->findById($id);
    }

    public function delete(int $id): void
    {
        $this->findById($id);
        $this->repository->delete($id);
    }

    public function activate(int $id): TaxGroup
    {
        $group = $this->findById($id);
        $group->activate();
        return $this->repository->update($id, ['is_active' => true]) ?? $group;
    }

    public function deactivate(int $id): TaxGroup
    {
        $group = $this->findById($id);
        $group->deactivate();
        return $this->repository->update($id, ['is_active' => false]) ?? $group;
    }
}
