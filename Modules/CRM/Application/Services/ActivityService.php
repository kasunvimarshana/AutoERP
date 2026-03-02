<?php

declare(strict_types=1);

namespace Modules\Crm\Application\Services;

use Modules\Crm\Application\Commands\DeleteActivityCommand;
use Modules\Crm\Application\Commands\LogActivityCommand;
use Modules\Crm\Application\Handlers\DeleteActivityHandler;
use Modules\Crm\Application\Handlers\LogActivityHandler;
use Modules\Crm\Domain\Contracts\ActivityRepositoryInterface;
use Modules\Crm\Domain\Entities\Activity;

/**
 * Service orchestrating all CRM activity operations.
 *
 * Controllers must interact with the activity domain exclusively through this
 * service. Read operations are fulfilled directly via the repository contract;
 * write operations are delegated to the appropriate command handlers.
 */
class ActivityService
{
    public function __construct(
        private readonly ActivityRepositoryInterface $activityRepository,
        private readonly LogActivityHandler $logActivityHandler,
        private readonly DeleteActivityHandler $deleteActivityHandler,
    ) {}

    /**
     * Retrieve a paginated list of activities for the given tenant.
     *
     * @return array{items: Activity[], current_page: int, last_page: int, per_page: int, total: int}
     */
    public function listActivities(int $tenantId, int $page, int $perPage): array
    {
        return $this->activityRepository->findAll($tenantId, $page, $perPage);
    }

    /**
     * Find a single activity by its identifier within the given tenant.
     */
    public function findActivityById(int $activityId, int $tenantId): ?Activity
    {
        return $this->activityRepository->findById($activityId, $tenantId);
    }

    /**
     * Retrieve all activities linked to a specific contact.
     *
     * @return Activity[]
     */
    public function listActivitiesByContact(int $contactId, int $tenantId): array
    {
        return $this->activityRepository->findByContact($contactId, $tenantId);
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
     * Log a new activity and return the persisted entity.
     */
    public function logActivity(LogActivityCommand $command): Activity
    {
        return $this->logActivityHandler->handle($command);
    }

    /**
     * Delete an activity.
     */
    public function deleteActivity(DeleteActivityCommand $command): void
    {
        $this->deleteActivityHandler->handle($command);
    }
}
