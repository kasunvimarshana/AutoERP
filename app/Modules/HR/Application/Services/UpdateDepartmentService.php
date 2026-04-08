<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\HR\Application\Contracts\UpdateDepartmentServiceInterface;
use Modules\HR\Application\DTOs\UpdateDepartmentData;
use Modules\HR\Domain\Entities\Department;
use Modules\HR\Domain\Events\DepartmentUpdated;
use Modules\HR\Domain\Exceptions\DepartmentNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\DepartmentRepositoryInterface;

class UpdateDepartmentService extends BaseService implements UpdateDepartmentServiceInterface
{
    public function __construct(private readonly DepartmentRepositoryInterface $departmentRepository)
    {
        parent::__construct($departmentRepository);
    }

    protected function handle(array $data): Department
    {
        $dto        = UpdateDepartmentData::fromArray($data);
        $id         = (int) ($dto->id ?? 0);
        $department = $this->departmentRepository->find($id);
        if (! $department) {
            throw new DepartmentNotFoundException($id);
        }

        $name = $dto->isProvided('name')
            ? new Name((string) $dto->name)
            : $department->getName();

        $code = $dto->isProvided('code')
            ? ($dto->code !== null ? new Code($dto->code) : null)
            : $department->getCode();

        $description = $dto->isProvided('description')
            ? $dto->description
            : $department->getDescription();

        $managerId = $dto->isProvided('manager_id')
            ? $dto->manager_id
            : $department->getManagerId();

        $parentId = $dto->isProvided('parent_id')
            ? $dto->parent_id
            : $department->getParentId();

        $metadata = $dto->isProvided('metadata')
            ? ($dto->metadata !== null ? new Metadata($dto->metadata) : null)
            : $department->getMetadata();

        $isActive = $dto->isProvided('is_active')
            ? (bool) $dto->is_active
            : $department->isActive();

        $department->updateDetails($name, $code, $description, $managerId, $parentId, $metadata, $isActive);

        $saved = $this->departmentRepository->save($department);
        $this->addEvent(new DepartmentUpdated($saved));

        return $saved;
    }
}
