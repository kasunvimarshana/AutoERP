<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Finance\Domain\Entities\ApprovalWorkflowConfig;

interface ApprovalWorkflowConfigRepositoryInterface extends RepositoryInterface
{
    public function save(ApprovalWorkflowConfig $config): ApprovalWorkflowConfig;
}
