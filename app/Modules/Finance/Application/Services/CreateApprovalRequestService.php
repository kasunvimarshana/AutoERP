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
            tenantId: $dto->tenant_id,
            workflowConfigId: $dto->workflow_config_id,
            entityType: $dto->entity_type,
            entityId: $dto->entity_id,
            requestedByUserId: $dto->requested_by_user_id,
            status: $dto->status,
            currentStepOrder: $dto->current_step_order,
        );

        return $this->approvalRequestRepository->save($request);
    }
}
