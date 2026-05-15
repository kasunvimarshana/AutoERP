<?php

namespace Modules\Document\Domain\Repositories;

use Modules\Document\Domain\Entities\DocumentWorkflow;

interface DocumentWorkflowRepositoryInterface
{
    public function findActive(int $tenantId, int $documentTypeId): ?DocumentWorkflow;
}
