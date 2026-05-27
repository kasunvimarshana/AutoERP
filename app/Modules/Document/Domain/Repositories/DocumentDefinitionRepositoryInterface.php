<?php

namespace Modules\Document\Domain\Repositories;

use Modules\Document\Domain\Entities\DocumentDefinition;

interface DocumentDefinitionRepositoryInterface
{
    public function findActive(int $tenantId, int $documentTypeId): ?DocumentDefinition;
}
