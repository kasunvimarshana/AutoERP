<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Finance\Application\Contracts\UpdateApprovalWorkflowConfigServiceInterface;
use Modules\Finance\Application\DTOs\ApprovalWorkflowConfigData;
use Modules\Finance\Domain\Entities\ApprovalWorkflowConfig;
use Modules\Finance\Domain\Exceptions\ApprovalWorkflowConfigNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\ApprovalWorkflowConfigRepositoryInterface;

class UpdateApprovalWorkflowConfigService extends BaseService implements UpdateApprovalWorkflowConfigServiceInterface
{
    public function __construct(private readonly ApprovalWorkflowConfigRepositoryInterface $configRepository)
    {
        parent::__construct($configRepository);
    }

    protected function handle(array $data): ApprovalWorkflowConfig
    {
        $dto = ApprovalWorkflowConfigData::fromArray($data);
        /** @var ApprovalWorkflowConfig|null $config */
        $config = $this->configRepository->find((int) $dto->id);
        if (! $config) {
            throw new ApprovalWorkflowConfigNotFoundException((int) $dto->id);
        }
        if ($dto->row_version !== $config->getRowVersion()) {
            throw new ConcurrentModificationException('ApprovalWorkflowConfig', (int) $dto->id);
        }
        $config->update(
            name: $dto->name,
            steps: $dto->steps,
            minAmount: $dto->min_amount,
            maxAmount: $dto->max_amount,
            isActive: $dto->is_active,
        );

        return $this->configRepository->save($config);
    }
}
