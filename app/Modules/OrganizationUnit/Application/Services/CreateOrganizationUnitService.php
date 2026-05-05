<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitData;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitCreated;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Domain\ValueObjects\OrganizationPath;

class CreateOrganizationUnitService extends BaseService implements CreateOrganizationUnitServiceInterface
{
    public function __construct(private readonly OrganizationUnitRepositoryInterface $organizationUnitRepository)
    {
        parent::__construct($organizationUnitRepository);
    }

    protected function handle(array $data): OrganizationUnit
    {
        $dto = OrganizationUnitData::fromArray($data);

        return DB::transaction(function () use ($dto): OrganizationUnit {
            // Calculate path and depth if parent exists
            $path = null;
            $depth = 0;
            if ($dto->parent_id !== null) {
                $parent = $this->organizationUnitRepository->find($dto->parent_id);
                if ($parent !== null) {
                    $parentPath = OrganizationPath::child(
                        new OrganizationPath($parent->getPath() ?? '/', $parent->getDepth()),
                        $dto->parent_id,
                    );
                    $path = $parentPath->getPath();
                    $depth = $parentPath->getDepth();
                }
            } else {
                // Root organization unit
                $path = null; // Will be set after ID is generated
                $depth = 0;
            }

            $organizationUnit = new OrganizationUnit(
                tenantId: $dto->tenant_id,
                typeId: $dto->type_id,
                parentId: $dto->parent_id,
                managerUserId: $dto->manager_user_id,
                name: $dto->name,
                code: $dto->code,
                path: $path,
                depth: $depth,
                metadata: $dto->metadata,
                isActive: $dto->is_active ?? true,
                description: $dto->description,
            );

            $saved = $this->organizationUnitRepository->save($organizationUnit);

            // If root (no parent), set path to /id
            if ($saved->getParentId() === null && $saved->getId() !== null) {
                $saved->setPath('/' . $saved->getId(), 0);
                $this->organizationUnitRepository->save($saved);
            }

            // Dispatch event
            Event::dispatch(new OrganizationUnitCreated(
                organizationUnitId: $saved->getId() ?? 0,
                tenantId: $saved->getTenantId(),
                name: $saved->getName(),
                parentId: $saved->getParentId(),
                typeId: $saved->getTypeId(),
            ));

            return $saved;
        });
    }
}
