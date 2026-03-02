<?php

declare(strict_types=1);

namespace Modules\Organisation\Application\Services;

use Modules\Organisation\Application\Commands\CreateOrganisationCommand;
use Modules\Organisation\Application\Commands\DeleteOrganisationCommand;
use Modules\Organisation\Application\Commands\UpdateOrganisationCommand;
use Modules\Organisation\Application\Handlers\CreateOrganisationHandler;
use Modules\Organisation\Application\Handlers\DeleteOrganisationHandler;
use Modules\Organisation\Application\Handlers\UpdateOrganisationHandler;
use Modules\Organisation\Domain\Contracts\OrganisationRepositoryInterface;
use Modules\Organisation\Domain\Entities\Organisation;

/**
 * Service orchestrating all organisation hierarchy operations.
 *
 * Controllers must interact with the organisation domain exclusively through
 * this service. Read operations are fulfilled directly via the repository
 * contract; write operations are delegated to the appropriate command handlers.
 */
class OrganisationService
{
    public function __construct(
        private readonly OrganisationRepositoryInterface $organisationRepository,
        private readonly CreateOrganisationHandler $createOrganisationHandler,
        private readonly UpdateOrganisationHandler $updateOrganisationHandler,
        private readonly DeleteOrganisationHandler $deleteOrganisationHandler,
    ) {}

    /**
     * Retrieve a paginated list of organisation nodes for the given tenant.
     *
     * @return array{items: Organisation[], current_page: int, last_page: int, per_page: int, total: int}
     */
    public function listOrganisations(int $tenantId, int $page, int $perPage): array
    {
        return $this->organisationRepository->findAll($tenantId, $page, $perPage);
    }

    /**
     * Find a single organisation node by its identifier within the given tenant.
     */
    public function findOrganisationById(int $organisationId, int $tenantId): ?Organisation
    {
        return $this->organisationRepository->findById($organisationId, $tenantId);
    }

    /**
     * Retrieve all direct child nodes of the given organisation node.
     *
     * @return Organisation[]
     */
    public function listChildren(int $parentId, int $tenantId): array
    {
        return $this->organisationRepository->findChildren($parentId, $tenantId);
    }

    /**
     * Create a new organisation node and return the persisted entity.
     */
    public function createOrganisation(CreateOrganisationCommand $command): Organisation
    {
        return $this->createOrganisationHandler->handle($command);
    }

    /**
     * Update an existing organisation node and return the updated entity.
     */
    public function updateOrganisation(UpdateOrganisationCommand $command): Organisation
    {
        return $this->updateOrganisationHandler->handle($command);
    }

    /**
     * Soft-delete an organisation node.
     */
    public function deleteOrganisation(DeleteOrganisationCommand $command): void
    {
        $this->deleteOrganisationHandler->handle($command);
    }
}
