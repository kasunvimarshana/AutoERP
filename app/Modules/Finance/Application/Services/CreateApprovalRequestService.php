<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreateApprovalRequestServiceInterface;
use Modules\Finance\Application\DTOs\ApprovalRequestData;
use Modules\Finance\Domain\Entities\ApprovalRequest;
use Modules\Finance\Domain\RepositoryInterfaces\ApprovalRequestRepositoryInterface;

class CreateApprovalRequestService extends BaseService implements CreateApprovalRequestServiceInterface
{
    public function __construct(private readonly ApprovalRequestRepositoryInterface $approvalRequestRepository)
    {
        parent::__construct($approvalRequestRepository);
    }

    protected function handle(array $data): ApprovalRequest
    {
        $dto = ApprovalRequestData::fromArray($data);

        $request = new ApprovalRequest(
            tenantId: $dto->tenantId,
            workflowConfigId: $dto->workflowConfigId,
            entityType: $dto->entityType,
            entityId: $dto->entityId,
            requestedByUserId: $dto->requestedByUserId,
            status: $dto->status,
            currentStepOrder: $dto->currentStepOrder,
        );

        return $this->approvalRequestRepository->save($request);
    }
}
