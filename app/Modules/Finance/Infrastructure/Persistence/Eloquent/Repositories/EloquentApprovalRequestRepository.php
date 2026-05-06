<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\Entities\ApprovalRequest;
use Modules\Finance\Domain\RepositoryInterfaces\ApprovalRequestRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\ApprovalRequestModel;

class EloquentApprovalRequestRepository extends EloquentRepository implements ApprovalRequestRepositoryInterface
{
    public function __construct(ApprovalRequestModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ApprovalRequestModel $m): ApprovalRequest => $this->mapToDomain($m));
    }

    public function save(ApprovalRequest $ar): ApprovalRequest
    {
        $data = [
            'tenant_id' => $ar->getTenantId(),
            'workflow_config_id' => $ar->getWorkflowConfigId(),
            'entity_type' => $ar->getEntityType(),
            'entity_id' => $ar->getEntityId(),
            'status' => $ar->getStatus(),
            'current_step_order' => $ar->getCurrentStepOrder(),
            'requested_by_user_id' => $ar->getRequestedByUserId(),
            'resolved_by_user_id' => $ar->getResolvedByUserId(),
            'requested_at' => $ar->getRequestedAt()->format('Y-m-d H:i:s'),
            'resolved_at' => $ar->getResolvedAt()?->format('Y-m-d H:i:s'),
            'comments' => $ar->getComments(),
            'row_version' => $ar->getRowVersion(),
        ];

        $model = $ar->getId() ? $this->update($ar->getId(), $data) : $this->create($data);

        /** @var ApprovalRequestModel $model */
        return $this->toDomainEntity($model);
    }

    private function mapToDomain(ApprovalRequestModel $m): ApprovalRequest
    {
        return new ApprovalRequest(
            tenantId: (int) $m->tenant_id,
            workflowConfigId: (int) $m->workflow_config_id,
            entityType: (string) $m->entity_type,
            entityId: (int) $m->entity_id,
            requestedByUserId: (int) $m->requested_by_user_id,
            status: (string) $m->status,
            currentStepOrder: (int) $m->current_step_order,
            resolvedByUserId: $m->resolved_by_user_id !== null ? (int) $m->resolved_by_user_id : null,
            requestedAt: $m->requested_at,
            resolvedAt: $m->resolved_at,
            comments: $m->comments,
            rowVersion: (int) ($m->row_version ?? 1),
            id: (int) $m->id,
            createdAt: $m->created_at,
            updatedAt: $m->updated_at,
        );
    }
}
