<?php

declare(strict_types=1);

namespace Modules\CRM\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CRM\Application\Commands\ConvertLeadCommand;
use Modules\CRM\Application\Commands\CreateLeadCommand;
use Modules\CRM\Application\Commands\UpdateLeadCommand;
use Modules\CRM\Application\Handlers\ConvertLeadHandler;
use Modules\CRM\Application\Handlers\CreateLeadHandler;
use Modules\CRM\Application\Handlers\UpdateLeadHandler;
use Modules\CRM\Domain\Contracts\LeadRepositoryInterface;

class LeadController extends Controller
{
    public function __construct(
        private readonly CreateLeadHandler      $createHandler,
        private readonly UpdateLeadHandler      $updateHandler,
        private readonly ConvertLeadHandler     $convertHandler,
        private readonly LeadRepositoryInterface $leads,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $items    = $this->leads->findAll($tenantId, (int) $request->query('page', 1), (int) $request->query('per_page', 25));

        return response()->json(['success' => true, 'message' => 'Leads retrieved.', 'data' => array_map(fn ($l) => [
            'id'     => $l->getId(), 'title' => $l->getTitle(), 'status' => $l->getStatus()->value,
            'value'  => $l->getValue(), 'source' => $l->getSource(),
        ], $items), 'errors' => null]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $lead     = $this->leads->findById($id, $tenantId);

        if (! $lead) {
            return response()->json(['success' => false, 'message' => 'Lead not found.', 'data' => null, 'errors' => null], 404);
        }

        return response()->json(['success' => true, 'message' => 'Lead retrieved.', 'data' => [
            'id'                  => $lead->getId(), 'title' => $lead->getTitle(),
            'status'              => $lead->getStatus()->value, 'value' => $lead->getValue(),
            'source'              => $lead->getSource(), 'expected_close_date' => $lead->getExpectedCloseDate(),
            'notes'               => $lead->getNotes(),
        ], 'errors' => null]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'contact_id'          => 'nullable|integer|exists:contacts,id',
            'source'              => 'nullable|string|max:100',
            'value'               => 'nullable|numeric|min:0',
            'expected_close_date' => 'nullable|date',
            'assigned_to'         => 'nullable|integer|exists:users,id',
            'notes'               => 'nullable|string',
        ]);

        $lead = $this->createHandler->handle(new CreateLeadCommand(
            tenantId: (int) $request->attributes->get('tenant_id'),
            title: $validated['title'],
            contactId: isset($validated['contact_id']) ? (int) $validated['contact_id'] : null,
            source: $validated['source'] ?? null,
            value: (string) ($validated['value'] ?? '0'),
            expectedCloseDate: $validated['expected_close_date'] ?? null,
            assignedTo: isset($validated['assigned_to']) ? (int) $validated['assigned_to'] : null,
            notes: $validated['notes'] ?? null,
        ));

        return response()->json(['success' => true, 'message' => 'Lead created.', 'data' => ['id' => $lead->getId(), 'title' => $lead->getTitle()], 'errors' => null], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'status'              => 'required|string|in:new,contacted,qualified,proposal,negotiation,won,lost,unqualified',
            'source'              => 'nullable|string|max:100',
            'value'               => 'nullable|numeric|min:0',
            'expected_close_date' => 'nullable|date',
            'assigned_to'         => 'nullable|integer|exists:users,id',
            'notes'               => 'nullable|string',
        ]);

        try {
            $lead = $this->updateHandler->handle(new UpdateLeadCommand(
                id: $id,
                tenantId: (int) $request->attributes->get('tenant_id'),
                title: $validated['title'],
                status: $validated['status'],
                source: $validated['source'] ?? null,
                value: (string) ($validated['value'] ?? '0'),
                expectedCloseDate: $validated['expected_close_date'] ?? null,
                assignedTo: isset($validated['assigned_to']) ? (int) $validated['assigned_to'] : null,
                notes: $validated['notes'] ?? null,
            ));
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => null, 'errors' => null], 404);
        }

        return response()->json(['success' => true, 'message' => 'Lead updated.', 'data' => ['id' => $lead->getId(), 'title' => $lead->getTitle(), 'status' => $lead->getStatus()->value], 'errors' => null]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $this->leads->delete($id, $tenantId);

        return response()->json(['success' => true, 'message' => 'Lead deleted.', 'data' => null, 'errors' => null]);
    }

    public function convert(Request $request, int $id): JsonResponse
    {
        try {
            $opportunity = $this->convertHandler->handle(new ConvertLeadCommand(
                leadId: $id,
                tenantId: (int) $request->attributes->get('tenant_id'),
            ));
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => null, 'errors' => null], 404);
        }

        return response()->json(['success' => true, 'message' => 'Lead converted to opportunity.', 'data' => ['opportunity_id' => $opportunity->getId()], 'errors' => null], 201);
    }
}

