<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\HR\Application\DTOs\UpdateDepartmentData;
use Modules\HR\Domain\Entities\Department;
use Modules\HR\Domain\Events\DepartmentUpdated;
use Modules\HR\Domain\Exceptions\DepartmentNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\DepartmentRepositoryInterface;

class UpdateDepartment
{
    public function __construct(private readonly DepartmentRepositoryInterface $repo) {}

    public function execute(UpdateDepartmentData $data): Department
    {
        $id         = (int) ($data->id ?? 0);
        $department = $this->repo->find($id);
        if (! $department) {
            throw new DepartmentNotFoundException($id);
        }

        $name        = $data->isProvided('name') ? new Name((string) $data->name) : $department->getName();
        $code        = $data->isProvided('code') ? ($data->code !== null ? new Code($data->code) : null) : $department->getCode();
        $description = $data->isProvided('description') ? $data->description : $department->getDescription();
        $managerId   = $data->isProvided('manager_id') ? $data->manager_id : $department->getManagerId();
        $parentId    = $data->isProvided('parent_id') ? $data->parent_id : $department->getParentId();
        $metadata    = $data->isProvided('metadata') ? ($data->metadata !== null ? new Metadata($data->metadata) : null) : $department->getMetadata();
        $isActive    = $data->isProvided('is_active') ? (bool) $data->is_active : $department->isActive();

        $department->updateDetails($name, $code, $description, $managerId, $parentId, $metadata, $isActive);

        $saved = $this->repo->save($department);
        DepartmentUpdated::dispatch($saved);

        return $saved;
    }
}
