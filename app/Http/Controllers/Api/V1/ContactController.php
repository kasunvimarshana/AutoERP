<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __construct(
        private readonly ContactService $contactService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['type', 'search']);

        return response()->json($this->contactService->paginateContacts($tenantId, $filters, $perPage));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('crm.contacts.create'), 403);

        $data = $request->validate([
            'type' => ['sometimes', 'string', 'in:person,company'],
            'first_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'mobile' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'array'],
            'source' => ['sometimes', 'nullable', 'string', 'max:50'],
            'tags' => ['sometimes', 'array'],
            'custom_fields' => ['sometimes', 'array'],
            'metadata' => ['sometimes', 'array'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;
        $data['organization_id'] ??= $request->user()->organization_id;

        return response()->json($this->contactService->createContact($data), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('crm.contacts.update'), 403);

        $data = $request->validate([
            'first_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'mobile' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'array'],
            'tags' => ['sometimes', 'array'],
            'custom_fields' => ['sometimes', 'array'],
        ]);

        return response()->json($this->contactService->updateContact($id, $data));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('crm.contacts.delete'), 403);
        $this->contactService->deleteContact($id);

        return response()->json(null, 204);
    }

    // Lead sub-resources
    public function leads(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['status', 'assigned_to']);

        return response()->json($this->contactService->paginateLeads($tenantId, $filters, $perPage));
    }

    public function storeLead(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('crm.leads.create'), 403);

        $data = $request->validate([
            'contact_id' => ['sometimes', 'nullable', 'uuid', 'exists:contacts,id'],
            'assigned_to' => ['sometimes', 'nullable', 'uuid', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'source' => ['sometimes', 'nullable', 'string', 'max:50'],
            'estimated_value' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'probability' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'custom_fields' => ['sometimes', 'array'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;
        $data['organization_id'] ??= $request->user()->organization_id;

        return response()->json($this->contactService->createLead($data), 201);
    }

    public function convertLead(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('crm.leads.convert'), 403);

        return response()->json($this->contactService->convertLead($id));
    }

    // Opportunities
    public function opportunities(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['stage']);

        return response()->json($this->contactService->paginateOpportunities($tenantId, $filters, $perPage));
    }

    public function storeOpportunity(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('crm.leads.create'), 403);

        $data = $request->validate([
            'contact_id' => ['sometimes', 'nullable', 'uuid', 'exists:contacts,id'],
            'lead_id' => ['sometimes', 'nullable', 'uuid', 'exists:leads,id'],
            'assigned_to' => ['sometimes', 'nullable', 'uuid', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'stage' => ['sometimes', 'string', 'in:prospecting,qualification,proposal,negotiation,closed_won,closed_lost'],
            'value' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'probability' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;
        $data['organization_id'] ??= $request->user()->organization_id;

        return response()->json($this->contactService->createOpportunity($data), 201);
    }
}
