<?php

declare(strict_types=1);

namespace Modules\Customization\Application\Services;

use Modules\Customization\Application\Commands\SetCustomFieldValuesCommand;
use Modules\Customization\Application\Handlers\SetCustomFieldValuesHandler;
use Modules\Customization\Domain\Contracts\CustomFieldValueRepositoryInterface;

class CustomFieldValueService
{
    public function __construct(
        private readonly CustomFieldValueRepositoryInterface $repository,
        private readonly SetCustomFieldValuesHandler $setValuesHandler,
    ) {}

    public function setValues(SetCustomFieldValuesCommand $cmd): array
    {
        return $this->setValuesHandler->handle($cmd);
    }

    public function findValuesForEntity(int $tenantId, string $entityType, int $entityId): array
    {
        return $this->repository->findByEntity($tenantId, $entityType, $entityId);
    }
}
