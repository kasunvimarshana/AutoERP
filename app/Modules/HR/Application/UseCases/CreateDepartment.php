<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\HR\Application\DTOs\DepartmentData;
use Modules\HR\Domain\Entities\Department;
use Modules\HR\Domain\Events\DepartmentCreated;
use Modules\HR\Domain\RepositoryInterfaces\DepartmentRepositoryInterface;

class CreateDepartment
{
    public function __construct(private readonly DepartmentRepositoryInterface $repo) {}

    public function execute(DepartmentData $data): Department
    {
        $department = new Department(
            tenantId:    $data->tenant_id,
            name:        new Name($data->name),
            code:        $data->code !== null ? new Code($data->code) : null,
            description: $data->description,
            managerId:   $data->manager_id,
            parentId:    $data->parent_id,
            metadata:    $data->metadata !== null ? new Metadata($data->metadata) : null,
            isActive:    $data->is_active,
        );

        $saved = $this->repo->save($department);
        DepartmentCreated::dispatch($saved);

        return $saved;
    }
}
