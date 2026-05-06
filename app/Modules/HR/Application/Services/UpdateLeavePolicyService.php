<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\HR\Application\Contracts\UpdateLeavePolicyServiceInterface;
use Modules\HR\Application\DTOs\LeavePolicyData;
use Modules\HR\Domain\Entities\LeavePolicy;
use Modules\HR\Domain\RepositoryInterfaces\LeavePolicyRepositoryInterface;

class UpdateLeavePolicyService extends BaseService implements UpdateLeavePolicyServiceInterface
{
    public function __construct(
        private readonly LeavePolicyRepositoryInterface $policyRepository,
    ) {
        parent::__construct($this->policyRepository);
    }

    protected function handle(array $data): LeavePolicy
    {
        $id = (int) ($data['id'] ?? 0);
        $policy = $this->policyRepository->find($id);

        if ($policy === null) {
            throw new NotFoundException('LeavePolicy', $id);
        }

        $dto = LeavePolicyData::fromArray($data);

        if ($policy->getTenantId() !== $dto->tenantId) {
            throw new NotFoundException('LeavePolicy', $id);
        }

        if ($dto->rowVersion !== $policy->getRowVersion()) {
            throw new ConcurrentModificationException('LeavePolicy', $id);
        }

        $updated = new LeavePolicy(
            tenantId: $policy->getTenantId(),
            leaveTypeId: $policy->getLeaveTypeId(),
            name: $dto->name,
            accrualType: $dto->accrualType,
            accrualAmount: $dto->accrualAmount,
            orgUnitId: $dto->orgUnitId,
            isActive: $dto->isActive,
            metadata: $dto->metadata,
            createdAt: $policy->getCreatedAt(),
            updatedAt: new \DateTimeImmutable,
            rowVersion: $policy->getRowVersion() + 1,
            id: $policy->getId(),
        );

        return $this->policyRepository->save($updated);
    }
}
