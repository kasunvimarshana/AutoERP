<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitNotFoundException;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

class UpdateOrganizationUnitService extends BaseService implements UpdateOrganizationUnitServiceInterface
{
    public function __construct(private readonly OrganizationUnitRepositoryInterface $organizationUnitRepository)
    {
        parent::__construct($organizationUnitRepository);
    }

    protected function handle(array $data): OrganizationUnit
    {
        $organizationUnitId = (int) $data['id'];
        $organizationUnit = $this->organizationUnitRepository->find($organizationUnitId);
        if (! $organizationUnit) {
            throw new OrganizationUnitNotFoundException($organizationUnitId);
        }

        $organizationUnit->update(
            name: isset($data['name']) ? (string) $data['name'] : $organizationUnit->getName(),
            typeId: array_key_exists('type_id', $data) ? (isset($data['type_id']) ? (int) $data['type_id'] : null) : $organizationUnit->getTypeId(),
            parentId: array_key_exists('parent_id', $data) ? (isset($data['parent_id']) ? (int) $data['parent_id'] : null) : $organizationUnit->getParentId(),
            managerUserId: array_key_exists('manager_user_id', $data) ? (isset($data['manager_user_id']) ? (int) $data['manager_user_id'] : null) : $organizationUnit->getManagerUserId(),
            code: array_key_exists('code', $data) ? (is_string($data['code']) ? $data['code'] : null) : $organizationUnit->getCode(),
            metadata: array_key_exists('metadata', $data) ? (is_array($data['metadata']) ? $data['metadata'] : null) : $organizationUnit->getMetadata(),
            isActive: array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $organizationUnit->isActive(),
            description: array_key_exists('description', $data) ? (is_string($data['description']) ? $data['description'] : null) : $organizationUnit->getDescription(),
        );

        return $this->organizationUnitRepository->save($organizationUnit);
    }
}
