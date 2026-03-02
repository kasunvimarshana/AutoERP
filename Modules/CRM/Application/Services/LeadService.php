<?php

declare(strict_types=1);

namespace Modules\Crm\Application\Services;

use Modules\Crm\Application\Commands\CreateLeadCommand;
use Modules\Crm\Application\Commands\DeleteLeadCommand;
use Modules\Crm\Application\Commands\UpdateLeadCommand;
use Modules\Crm\Application\Handlers\CreateLeadHandler;
use Modules\Crm\Application\Handlers\DeleteLeadHandler;
use Modules\Crm\Application\Handlers\UpdateLeadHandler;
use Modules\Crm\Domain\Contracts\ActivityRepositoryInterface;
use Modules\Crm\Domain\Contracts\LeadRepositoryInterface;
use Modules\Crm\Domain\Entities\Activity;
use Modules\Crm\Domain\Entities\Lead;

/**
 * Service orchestrating all CRM lead operations.
 *
 * Controllers must interact with the lead domain exclusively through this
 * service. Read operations are fulfilled directly via the repository contracts;
 * write operations are delegated to the appropriate command handlers.
 */
class LeadService
{
    public function __construct(
        private readonly LeadRepositoryInterface $leadRepository,
        private readonly ActivityRepositoryInterface $activityRepository,
        private readonly CreateLeadHandler $createLeadHandler,
        private readonly UpdateLeadHandler $updateLeadHandler,
        private readonly DeleteLeadHandler $deleteLeadHandler,
    ) {}

    /**
     * Retrieve a paginated list of leads for the given tenant.
     *
     * @return array{items: Lead[], current_page: int, last_page: int, per_page: int, total: int}
     */
    public function listLeads(int $tenantId, int $page, int $perPage): array
    {
        return $this->leadRepository->findAll($tenantId, $page, $perPage);
    }

    /**
     * Find a single lead by its identifier within the given tenant.
     */
    public function findLeadById(int $leadId, int $tenantId): ?Lead
    {
        return $this->leadRepository->findById($leadId, $tenantId);
    }

    /**
     * Retrieve all activities linked to a specific lead.
     *
     * @return Activity[]
     */
    public function listActivitiesByLead(int $leadId, int $tenantId): array
    {
        return $this->activityRepository->findByLead($leadId, $tenantId);
    }

    /**
     * Create a new lead and return the persisted entity.
     */
    public function createLead(CreateLeadCommand $command): Lead
    {
        return $this->createLeadHandler->handle($command);
    }

    /**
     * Update an existing lead and return the updated entity.
     */
    public function updateLead(UpdateLeadCommand $command): Lead
    {
        return $this->updateLeadHandler->handle($command);
    }

    /**
     * Delete a lead.
     */
    public function deleteLead(DeleteLeadCommand $command): void
    {
        $this->deleteLeadHandler->handle($command);
    }
}
