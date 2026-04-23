<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\CreateLeavePolicyServiceInterface;
use Modules\HR\Application\DTOs\LeavePolicyData;
use Modules\HR\Domain\Entities\LeavePolicy;
use Modules\HR\Domain\RepositoryInterfaces\LeavePolicyRepositoryInterface;

class CreateLeavePolicyService extends BaseService implements CreateLeavePolicyServiceInterface
{
    public function __construct(
        private readonly LeavePolicyRepositoryInterface $policyRepository,
    ) {
        parent::__construct($this->policyRepository);
    }

    protected function handle(array $data): LeavePolicy
    {
        $dto = LeavePolicyData::fromArray($data);

        $now = new \DateTimeImmutable;
        $policy = new LeavePolicy(
            tenantId: $dto->tenantId,
            leaveTypeId: $dto->leaveTypeId,
            name: $dto->name,
            accrualType: $dto->accrualType,
            accrualAmount: $dto->accrualAmount,
            orgUnitId: $dto->orgUnitId,
            isActive: $dto->isActive,
            metadata: $dto->metadata,
            createdAt: $now,
            updatedAt: $now,
        );

        return $this->policyRepository->save($policy);
    }
}
