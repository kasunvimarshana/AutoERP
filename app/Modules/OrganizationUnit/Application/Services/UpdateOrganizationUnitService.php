<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\DTOs\UpdateOrganizationUnitData;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitUpdated;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitNotFoundException;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

class UpdateOrganizationUnitService extends BaseService implements UpdateOrganizationUnitServiceInterface
{
    private OrganizationUnitRepositoryInterface $orgUnitRepository;

    public function __construct(OrganizationUnitRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->orgUnitRepository = $repository;
    }

    protected function handle(array $data): OrganizationUnit
    {
        $dto  = UpdateOrganizationUnitData::fromArray($data);
        $id   = (int) ($dto->id ?? 0);
        $unit = $this->orgUnitRepository->find($id);
        if (! $unit) {
            throw new OrganizationUnitNotFoundException($id);
        }

        // isProvided() distinguishes "field was absent" from "field was sent as null",
        // enabling safe partial updates that never unintentionally clear existing data.
        $name = $dto->isProvided('name')
            ? new Name((string) $dto->name)
            : $unit->getName();

        $code = $dto->isProvided('code')
            ? ($dto->code !== null ? new Code($dto->code) : null)
            : $unit->getCode();

        $description = $dto->isProvided('description')
            ? $dto->description
            : $unit->getDescription();

        $metadata = $dto->isProvided('metadata')
            ? ($dto->metadata !== null ? new Metadata($dto->metadata) : null)
            : $unit->getMetadata();

        $unit->updateDetails($name, $code, $description, $metadata);

        // Only move the node when parent_id was explicitly supplied and differs.
        if ($dto->isProvided('parent_id') && $dto->parent_id !== $unit->getParentId()) {
            $this->orgUnitRepository->moveNode($id, $dto->parent_id);
        }

        $saved = $this->orgUnitRepository->save($unit);
        $this->addEvent(new OrganizationUnitUpdated($saved));

        return $saved;
    }
}
