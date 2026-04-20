<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreateApprovalWorkflowConfigServiceInterface;
use Modules\Finance\Application\DTOs\ApprovalWorkflowConfigData;
use Modules\Finance\Domain\Entities\ApprovalWorkflowConfig;
use Modules\Finance\Domain\RepositoryInterfaces\ApprovalWorkflowConfigRepositoryInterface;

class CreateApprovalWorkflowConfigService extends BaseService implements CreateApprovalWorkflowConfigServiceInterface
{
    public function __construct(private readonly ApprovalWorkflowConfigRepositoryInterface $configRepository)
    {
        parent::__construct($configRepository);
    }

    protected function handle(array $data): ApprovalWorkflowConfig
    {
        $dto = ApprovalWorkflowConfigData::fromArray($data);

        $config = new ApprovalWorkflowConfig(
            tenantId: $dto->tenant_id,
            module: $dto->module,
            entityType: $dto->entity_type,
            name: $dto->name,
            steps: $dto->steps,
            minAmount: $dto->min_amount,
            maxAmount: $dto->max_amount,
            isActive: $dto->is_active,
        );

        return $this->configRepository->save($config);
    }
}
