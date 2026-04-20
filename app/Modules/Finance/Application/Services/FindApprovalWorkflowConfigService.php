<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\FindApprovalWorkflowConfigServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\ApprovalWorkflowConfigRepositoryInterface;

class FindApprovalWorkflowConfigService extends BaseService implements FindApprovalWorkflowConfigServiceInterface
{
    public function __construct(private readonly ApprovalWorkflowConfigRepositoryInterface $configRepository)
    {
        parent::__construct($configRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
