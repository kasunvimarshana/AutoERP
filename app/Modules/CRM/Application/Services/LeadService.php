<?php
declare(strict_types=1);
namespace Modules\CRM\Application\Services;

use Modules\CRM\Domain\Entities\Lead;
use Modules\CRM\Domain\Exceptions\LeadNotFoundException;
use Modules\CRM\Domain\RepositoryInterfaces\LeadRepositoryInterface;

class LeadService
{
    public function __construct(private readonly LeadRepositoryInterface $repository) {}

    public function findById(int $id): Lead
    {
        $lead = $this->repository->findById($id);
        if ($lead === null) throw new LeadNotFoundException($id);
        return $lead;
    }

    public function findAllByTenant(int $tenantId, array $filters = []): array
    {
        return $this->repository->findAllByTenant($tenantId, $filters);
    }

    public function create(array $data): Lead
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): Lead
    {
        $this->findById($id);
        return $this->repository->update($id, $data) ?? $this->findById($id);
    }

    public function qualify(int $id): Lead
    {
        $lead = $this->findById($id);
        $lead->qualify();
        return $this->repository->update($id, ['status' => Lead::STATUS_QUALIFIED]) ?? $lead;
    }

    public function disqualify(int $id): Lead
    {
        $lead = $this->findById($id);
        $lead->disqualify();
        return $this->repository->update($id, ['status' => Lead::STATUS_DISQUALIFIED]) ?? $lead;
    }

    public function convert(int $id): Lead
    {
        $lead = $this->findById($id);
        $lead->convert();
        return $this->repository->update($id, [
            'status'       => Lead::STATUS_CONVERTED,
            'converted_at' => new \DateTimeImmutable(),
        ]) ?? $lead;
    }

    public function delete(int $id): void
    {
        $this->findById($id);
        $this->repository->delete($id);
    }
}
