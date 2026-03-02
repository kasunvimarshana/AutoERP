<?php

declare(strict_types=1);

namespace Modules\Crm\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use DomainException;
use Illuminate\Http\JsonResponse;
use Modules\Crm\Application\Commands\CreateContactCommand;
use Modules\Crm\Application\Commands\DeleteContactCommand;
use Modules\Crm\Application\Commands\UpdateContactCommand;
use Modules\Crm\Application\Services\ContactService;
use Modules\Crm\Interfaces\Http\Requests\CreateContactRequest;
use Modules\Crm\Interfaces\Http\Requests\UpdateContactRequest;
use Modules\Crm\Interfaces\Http\Resources\ActivityResource;
use Modules\Crm\Interfaces\Http\Resources\ContactResource;
use Modules\Crm\Interfaces\Http\Resources\LeadResource;

class ContactController extends BaseController
{
    public function __construct(
        private readonly ContactService $contactService,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->contactService->listContacts($tenantId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($contact) => (new ContactResource($contact))->resolve(),
                $result['items']
            ),
            message: 'Contacts retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(CreateContactRequest $request): JsonResponse
    {
        try {
            $contact = $this->contactService->createContact(new CreateContactCommand(
                tenantId: (int) $request->validated('tenant_id'),
                firstName: $request->validated('first_name'),
                lastName: $request->validated('last_name'),
                email: $request->validated('email'),
                phone: $request->validated('phone'),
                company: $request->validated('company'),
                jobTitle: $request->validated('job_title'),
                notes: $request->validated('notes'),
            ));

            return $this->success(
                data: (new ContactResource($contact))->resolve(),
                message: 'Contact created successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $contact = $this->contactService->findContactById($id, $tenantId);

        if ($contact === null) {
            return $this->error('Contact not found', status: 404);
        }

        return $this->success(
            data: (new ContactResource($contact))->resolve(),
            message: 'Contact retrieved successfully',
        );
    }

    public function update(UpdateContactRequest $request, int $id): JsonResponse
    {
        try {
            $tenantId = (int) $request->query('tenant_id', '0');

            $contact = $this->contactService->updateContact(new UpdateContactCommand(
                id: $id,
                tenantId: $tenantId,
                firstName: $request->validated('first_name'),
                lastName: $request->validated('last_name'),
                email: $request->validated('email'),
                phone: $request->validated('phone'),
                company: $request->validated('company'),
                jobTitle: $request->validated('job_title'),
                status: $request->validated('status'),
                notes: $request->validated('notes'),
            ));

            return $this->success(
                data: (new ContactResource($contact))->resolve(),
                message: 'Contact updated successfully',
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->contactService->deleteContact(new DeleteContactCommand($id, $tenantId));

            return $this->success(message: 'Contact deleted successfully');
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }

    public function leads(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $contact = $this->contactService->findContactById($id, $tenantId);

        if ($contact === null) {
            return $this->error('Contact not found', status: 404);
        }

        $leads = $this->contactService->listLeadsByContact($id, $tenantId);

        return $this->success(
            data: array_map(
                fn ($lead) => (new LeadResource($lead))->resolve(),
                $leads
            ),
            message: 'Leads retrieved successfully',
        );
    }

    public function activities(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $contact = $this->contactService->findContactById($id, $tenantId);

        if ($contact === null) {
            return $this->error('Contact not found', status: 404);
        }

        $activities = $this->contactService->listActivitiesByContact($id, $tenantId);

        return $this->success(
            data: array_map(
                fn ($activity) => (new ActivityResource($activity))->resolve(),
                $activities
            ),
            message: 'Activities retrieved successfully',
        );
    }
}
