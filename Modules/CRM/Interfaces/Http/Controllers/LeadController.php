<?php

declare(strict_types=1);

namespace Modules\Crm\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use DomainException;
use Illuminate\Http\JsonResponse;
use Modules\Crm\Application\Commands\CreateLeadCommand;
use Modules\Crm\Application\Commands\DeleteLeadCommand;
use Modules\Crm\Application\Commands\UpdateLeadCommand;
use Modules\Crm\Application\Services\LeadService;
use Modules\Crm\Interfaces\Http\Requests\CreateLeadRequest;
use Modules\Crm\Interfaces\Http\Requests\UpdateLeadRequest;
use Modules\Crm\Interfaces\Http\Resources\ActivityResource;
use Modules\Crm\Interfaces\Http\Resources\LeadResource;

class LeadController extends BaseController
{
    public function __construct(
        private readonly LeadService $leadService,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->leadService->listLeads($tenantId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($lead) => (new LeadResource($lead))->resolve(),
                $result['items']
            ),
            message: 'Leads retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(CreateLeadRequest $request): JsonResponse
    {
        try {
            $lead = $this->leadService->createLead(new CreateLeadCommand(
                tenantId: (int) $request->validated('tenant_id'),
                title: $request->validated('title'),
                description: $request->validated('description'),
                contactId: $request->validated('contact_id') ? (int) $request->validated('contact_id') : null,
                status: $request->validated('status'),
                estimatedValue: $request->validated('estimated_value') !== null ? (string) $request->validated('estimated_value') : null,
                currency: $request->validated('currency'),
                expectedCloseDate: $request->validated('expected_close_date'),
                notes: $request->validated('notes'),
            ));

            return $this->success(
                data: (new LeadResource($lead))->resolve(),
                message: 'Lead created successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $lead = $this->leadService->findLeadById($id, $tenantId);

        if ($lead === null) {
            return $this->error('Lead not found', status: 404);
        }

        return $this->success(
            data: (new LeadResource($lead))->resolve(),
            message: 'Lead retrieved successfully',
        );
    }

    public function update(UpdateLeadRequest $request, int $id): JsonResponse
    {
        try {
            $tenantId = (int) $request->query('tenant_id', '0');

            $lead = $this->leadService->updateLead(new UpdateLeadCommand(
                id: $id,
                tenantId: $tenantId,
                title: $request->validated('title'),
                description: $request->validated('description'),
                contactId: $request->validated('contact_id') ? (int) $request->validated('contact_id') : null,
                status: $request->validated('status'),
                estimatedValue: $request->validated('estimated_value') !== null ? (string) $request->validated('estimated_value') : null,
                currency: $request->validated('currency'),
                expectedCloseDate: $request->validated('expected_close_date'),
                notes: $request->validated('notes'),
            ));

            return $this->success(
                data: (new LeadResource($lead))->resolve(),
                message: 'Lead updated successfully',
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->leadService->deleteLead(new DeleteLeadCommand($id, $tenantId));

            return $this->success(message: 'Lead deleted successfully');
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }

    public function activities(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $lead = $this->leadService->findLeadById($id, $tenantId);

        if ($lead === null) {
            return $this->error('Lead not found', status: 404);
        }

        $activities = $this->leadService->listActivitiesByLead($id, $tenantId);

        return $this->success(
            data: array_map(
                fn ($activity) => (new ActivityResource($activity))->resolve(),
                $activities
            ),
            message: 'Activities retrieved successfully',
        );
    }
}
