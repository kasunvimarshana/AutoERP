<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\DeleteApprovalWorkflowConfigServiceInterface;
use Modules\Finance\Domain\Exceptions\ApprovalWorkflowConfigNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\ApprovalWorkflowConfigRepositoryInterface;

class DeleteApprovalWorkflowConfigService extends BaseService implements DeleteApprovalWorkflowConfigServiceInterface
{
    public function __construct(private readonly ApprovalWorkflowConfigRepositoryInterface $configRepository)
    {
        parent::__construct($configRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        if (! $this->configRepository->find($id)) {
            throw new ApprovalWorkflowConfigNotFoundException($id);
        }

        return $this->configRepository->delete($id);
    }
}
