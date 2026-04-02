<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\HR\Application\Contracts\CreateDepartmentServiceInterface;
use Modules\HR\Application\DTOs\DepartmentData;
use Modules\HR\Domain\Entities\Department;
use Modules\HR\Domain\Events\DepartmentCreated;
use Modules\HR\Domain\RepositoryInterfaces\DepartmentRepositoryInterface;

class CreateDepartmentService extends BaseService implements CreateDepartmentServiceInterface
{
    public function __construct(private readonly DepartmentRepositoryInterface $departmentRepository)
    {
        parent::__construct($departmentRepository);
    }

    protected function handle(array $data): Department
    {
        $dto = DepartmentData::fromArray($data);

        $department = new Department(
            tenantId:    $dto->tenant_id,
            name:        new Name($dto->name),
            code:        $dto->code !== null ? new Code($dto->code) : null,
            description: $dto->description,
            managerId:   $dto->manager_id,
            parentId:    $dto->parent_id,
            metadata:    $dto->metadata !== null ? new Metadata($dto->metadata) : null,
            isActive:    $dto->is_active,
        );

        $saved = $this->departmentRepository->save($department);
        $this->addEvent(new DepartmentCreated($saved));

        return $saved;
    }
}
