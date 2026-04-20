<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\Entities\ApprovalWorkflowConfig;
use Modules\Finance\Domain\RepositoryInterfaces\ApprovalWorkflowConfigRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\ApprovalWorkflowConfigModel;

class EloquentApprovalWorkflowConfigRepository extends EloquentRepository implements ApprovalWorkflowConfigRepositoryInterface
{
    public function __construct(ApprovalWorkflowConfigModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ApprovalWorkflowConfigModel $m): ApprovalWorkflowConfig => $this->mapToDomain($m));
    }

    public function save(ApprovalWorkflowConfig $config): ApprovalWorkflowConfig
    {
        $data = [
            'tenant_id' => $config->getTenantId(),
            'module' => $config->getModule(),
            'entity_type' => $config->getEntityType(),
            'name' => $config->getName(),
            'steps' => $config->getSteps(),
            'min_amount' => $config->getMinAmount(),
            'max_amount' => $config->getMaxAmount(),
            'is_active' => $config->isActive(),
        ];

        $model = $config->getId() ? $this->update($config->getId(), $data) : $this->create($data);

        /** @var ApprovalWorkflowConfigModel $model */
        return $this->toDomainEntity($model);
    }

    private function mapToDomain(ApprovalWorkflowConfigModel $m): ApprovalWorkflowConfig
    {
        return new ApprovalWorkflowConfig(
            tenantId: (int) $m->tenant_id,
            module: (string) $m->module,
            entityType: (string) $m->entity_type,
            name: (string) $m->name,
            steps: (array) $m->steps,
            minAmount: $m->min_amount !== null ? (float) $m->min_amount : null,
            maxAmount: $m->max_amount !== null ? (float) $m->max_amount : null,
            isActive: (bool) $m->is_active,
            id: (int) $m->id,
            createdAt: $m->created_at,
            updatedAt: $m->updated_at,
        );
    }
}
