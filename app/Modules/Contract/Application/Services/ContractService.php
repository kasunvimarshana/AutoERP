<?php
declare(strict_types=1);
namespace Modules\Contract\Application\Services;

use Modules\Contract\Domain\Entities\Contract;
use Modules\Contract\Domain\Exceptions\ContractNotFoundException;
use Modules\Contract\Domain\RepositoryInterfaces\ContractRepositoryInterface;

class ContractService
{
    public function __construct(private readonly ContractRepositoryInterface $repository) {}

    public function findById(int $id): Contract
    {
        $contract = $this->repository->findById($id);
        if ($contract === null) throw new ContractNotFoundException($id);
        return $contract;
    }

    public function findAllByTenant(int $tenantId, array $filters = []): array
    {
        return $this->repository->findAllByTenant($tenantId, $filters);
    }

    public function findExpiring(int $tenantId, int $withinDays = 30): array
    {
        $before = new \DateTimeImmutable("+{$withinDays} days");
        return $this->repository->findExpiring($tenantId, $before);
    }

    public function create(array $data): Contract
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): Contract
    {
        $this->findById($id);
        return $this->repository->update($id, $data) ?? $this->findById($id);
    }

    public function activate(int $id): Contract
    {
        $contract = $this->findById($id);
        $contract->activate();
        return $this->repository->update($id, ['status' => Contract::STATUS_ACTIVE]) ?? $contract;
    }

    public function terminate(int $id, string $reason = ''): Contract
    {
        $contract = $this->findById($id);
        $contract->terminate($reason);
        return $this->repository->update($id, [
            'status'        => Contract::STATUS_TERMINATED,
            'terminated_at' => new \DateTimeImmutable(),
        ]) ?? $contract;
    }

    public function delete(int $id): void
    {
        $this->findById($id);
        $this->repository->delete($id);
    }
}
