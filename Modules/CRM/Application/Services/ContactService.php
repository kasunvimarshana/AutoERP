<?php

declare(strict_types=1);

namespace Modules\Crm\Application\Services;

use Modules\Crm\Application\Commands\CreateContactCommand;
use Modules\Crm\Application\Commands\DeleteContactCommand;
use Modules\Crm\Application\Commands\UpdateContactCommand;
use Modules\Crm\Application\Handlers\CreateContactHandler;
use Modules\Crm\Application\Handlers\DeleteContactHandler;
use Modules\Crm\Application\Handlers\UpdateContactHandler;
use Modules\Crm\Domain\Contracts\ActivityRepositoryInterface;
use Modules\Crm\Domain\Contracts\ContactRepositoryInterface;
use Modules\Crm\Domain\Contracts\LeadRepositoryInterface;
use Modules\Crm\Domain\Entities\Activity;
use Modules\Crm\Domain\Entities\Contact;
use Modules\Crm\Domain\Entities\Lead;

/**
 * Service orchestrating all CRM contact operations.
 *
 * Controllers must interact with the contact domain exclusively through this
 * service. Read operations are fulfilled directly via the repository contracts;
 * write operations are delegated to the appropriate command handlers.
 */
class ContactService
{
    public function __construct(
        private readonly ContactRepositoryInterface $contactRepository,
        private readonly LeadRepositoryInterface $leadRepository,
        private readonly ActivityRepositoryInterface $activityRepository,
        private readonly CreateContactHandler $createContactHandler,
        private readonly UpdateContactHandler $updateContactHandler,
        private readonly DeleteContactHandler $deleteContactHandler,
    ) {}

    /**
     * Retrieve a paginated list of contacts for the given tenant.
     *
     * @return array{items: Contact[], current_page: int, last_page: int, per_page: int, total: int}
     */
    public function listContacts(int $tenantId, int $page, int $perPage): array
    {
        return $this->contactRepository->findAll($tenantId, $page, $perPage);
    }

    /**
     * Find a single contact by its identifier within the given tenant.
     */
    public function findContactById(int $contactId, int $tenantId): ?Contact
    {
        return $this->contactRepository->findById($contactId, $tenantId);
    }

    /**
     * Retrieve all leads associated with a specific contact.
     *
     * @return Lead[]
     */
    public function listLeadsByContact(int $contactId, int $tenantId): array
    {
        return $this->leadRepository->findByContact($contactId, $tenantId);
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
     * Create a new contact and return the persisted entity.
     */
    public function createContact(CreateContactCommand $command): Contact
    {
        return $this->createContactHandler->handle($command);
    }

    /**
     * Update an existing contact and return the updated entity.
     */
    public function updateContact(UpdateContactCommand $command): Contact
    {
        return $this->updateContactHandler->handle($command);
    }

    /**
     * Delete a contact.
     */
    public function deleteContact(DeleteContactCommand $command): void
    {
        $this->deleteContactHandler->handle($command);
    }
}
