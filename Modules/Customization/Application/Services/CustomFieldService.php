<?php

declare(strict_types=1);

namespace Modules\Customization\Application\Services;

use Modules\Customization\Application\Commands\CreateCustomFieldCommand;
use Modules\Customization\Application\Commands\DeleteCustomFieldCommand;
use Modules\Customization\Application\Commands\UpdateCustomFieldCommand;
use Modules\Customization\Application\Handlers\CreateCustomFieldHandler;
use Modules\Customization\Application\Handlers\DeleteCustomFieldHandler;
use Modules\Customization\Application\Handlers\UpdateCustomFieldHandler;
use Modules\Customization\Domain\Contracts\CustomFieldRepositoryInterface;
use Modules\Customization\Domain\Entities\CustomField;

class CustomFieldService
{
    public function __construct(
        private readonly CustomFieldRepositoryInterface $repository,
        private readonly CreateCustomFieldHandler $createHandler,
        private readonly UpdateCustomFieldHandler $updateHandler,
        private readonly DeleteCustomFieldHandler $deleteHandler,
    ) {}

    public function createField(CreateCustomFieldCommand $cmd): CustomField
    {
        return $this->createHandler->handle($cmd);
    }

    public function updateField(UpdateCustomFieldCommand $cmd): CustomField
    {
        return $this->updateHandler->handle($cmd);
    }

    public function deleteField(DeleteCustomFieldCommand $cmd): void
    {
        $this->deleteHandler->handle($cmd);
    }

    public function findFieldById(int $id, int $tenantId): ?CustomField
    {
        return $this->repository->findById($id, $tenantId);
    }

    public function findAllFields(int $tenantId, string $entityType, int $page, int $perPage): array
    {
        return $this->repository->findAll($tenantId, $entityType, $page, $perPage);
    }

    public function findFieldsByEntityType(int $tenantId, string $entityType): array
    {
        return $this->repository->findByEntityType($tenantId, $entityType);
    }
}
