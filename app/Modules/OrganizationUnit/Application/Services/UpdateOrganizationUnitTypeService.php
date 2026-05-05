<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitType;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitTypeNotFoundException;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitTypeRepositoryInterface;

class UpdateOrganizationUnitTypeService extends BaseService implements UpdateOrganizationUnitTypeServiceInterface
{
    public function __construct(private readonly OrganizationUnitTypeRepositoryInterface $organizationUnitTypeRepository)
    {
        parent::__construct($organizationUnitTypeRepository);
    }

    protected function handle(array $data): OrganizationUnitType
    {
        $organizationUnitTypeId = (int) $data['id'];

        return DB::transaction(function () use ($organizationUnitTypeId, $data): OrganizationUnitType {
            $organizationUnitType = $this->organizationUnitTypeRepository->find($organizationUnitTypeId);
            if (! $organizationUnitType) {
                throw new OrganizationUnitTypeNotFoundException($organizationUnitTypeId);
            }

            $organizationUnitType->update(
                name: array_key_exists('name', $data) ? (string) $data['name'] : $organizationUnitType->getName(),
                level: array_key_exists('level', $data) ? (int) $data['level'] : $organizationUnitType->getLevel(),
                isActive: array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $organizationUnitType->isActive(),
            );

            return $this->organizationUnitTypeRepository->save($organizationUnitType);
        });
    }
}
