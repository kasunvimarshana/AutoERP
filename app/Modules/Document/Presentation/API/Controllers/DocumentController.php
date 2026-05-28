<?php

namespace Modules\Document\Presentation\API\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Document\Application\DTOs\CreateDocumentDTO;
use Modules\Document\Application\Services\DocumentOrchestrator;
use Modules\Document\Presentation\API\Requests\ChangeStatusRequest;
use Modules\Document\Presentation\API\Requests\CreateDocumentRequest;
use Modules\Document\Presentation\API\Requests\ListDocumentsRequest;
use Modules\Document\Presentation\API\Requests\UploadDocumentAttachmentRequest;
use Modules\Document\Presentation\API\Resources\DocumentResource;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentOrchestrator $orchestrator,
        private readonly CurrentTenantContextAccessorInterface $currentTenantContext
    ) {
    }

    public function index(ListDocumentsRequest $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $filters = array_filter(
            $request->validated() + [
                'tenant_id' => $tenantId,
            ],
            static fn ($value): bool => $value !== null && $value !== '',
        );

        $documents = $this->orchestrator->list($filters, (int) ($filters['per_page'] ?? 15));

        return DocumentResource::collection($documents)->response();
    }

    public function store(CreateDocumentRequest $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $validated = $request->validated();

        $dto = new CreateDocumentDTO(
            tenantId: $tenantId,
            documentTypeId: (int) $validated['document_type_id'],
            documentDate: $validated['document_date'],
            organizationUnitId: $validated['organization_unit_id'] ?? null,
            ownerId: $validated['owner_id'] ?? null,
            partyId: $validated['party_id'] ?? null,
            dueDate: $validated['due_date'] ?? null,
            notes: $validated['notes'] ?? null,
            data: $validated['data'] ?? [],
            items: $validated['items'],
        );

        $aggregate = $this->orchestrator->create($dto);

        return (new DocumentResource($aggregate))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $document): JsonResponse
    {
        $tenantId = $this->resolveTenantId(request());

        return (new DocumentResource($this->orchestrator->show($tenantId, $document)))->response();
    }

    public function changeStatus(ChangeStatusRequest $request, int $document): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $aggregate = $this->orchestrator->changeStatus(
            $tenantId,
            $document,
            $request->validated('status'),
            $request->validated('action_name'),
        );

        return (new DocumentResource($aggregate))->response();
    }

    public function uploadAttachment(UploadDocumentAttachmentRequest $request, int $document): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $attachment = $this->orchestrator->uploadAttachment($tenantId, $document, $request->file('file'));

        return response()->json(['data' => $attachment], Response::HTTP_CREATED);
    }

    public function listComments(int $document): JsonResponse
    {
        $tenantId = $this->resolveTenantId(request());

        return response()->json([
            'data' => $this->orchestrator->listComments($tenantId, $document),
        ]);
    }

    public function addComment(Request $request, int $document): JsonResponse
    {
        $payload = $request->validate([
            'comment' => ['required', 'string', 'max:10000'],
            'author_id' => ['nullable', 'integer'],
        ]);

        $tenantId = $this->resolveTenantId($request);

        return response()->json([
            'data' => $this->orchestrator->addComment($tenantId, $document, $payload),
        ], Response::HTTP_CREATED);
    }

    public function listActivities(int $document): JsonResponse
    {
        $tenantId = $this->resolveTenantId(request());

        return response()->json([
            'data' => $this->orchestrator->listActivities($tenantId, $document),
        ]);
    }

    public function addActivity(Request $request, int $document): JsonResponse
    {
        $payload = $request->validate([
            'activity_type' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:10000'],
            'performed_by' => ['nullable', 'integer'],
        ]);

        $tenantId = $this->resolveTenantId($request);

        return response()->json([
            'data' => $this->orchestrator->addActivity($tenantId, $document, $payload),
        ], Response::HTTP_CREATED);
    }

    public function listEvents(int $document): JsonResponse
    {
        $tenantId = $this->resolveTenantId(request());

        return response()->json([
            'data' => $this->orchestrator->listEvents($tenantId, $document),
        ]);
    }

    public function addEvent(Request $request, int $document): JsonResponse
    {
        $payload = $request->validate([
            'event_type' => ['required', 'string', 'max:180'],
            'attributes' => ['nullable', 'array'],
            'payload' => ['nullable', 'array'],
            'performed_by' => ['nullable', 'integer'],
        ]);

        $tenantId = $this->resolveTenantId($request);

        return response()->json([
            'data' => $this->orchestrator->addEvent($tenantId, $document, $payload),
        ], Response::HTTP_CREATED);
    }

    public function listPermissions(int $document): JsonResponse
    {
        $tenantId = $this->resolveTenantId(request());

        return response()->json([
            'data' => $this->orchestrator->listPermissions($tenantId, $document),
        ]);
    }

    public function addPermission(Request $request, int $document): JsonResponse
    {
        $payload = $request->validate([
            'principal_type' => ['required', 'string', 'max:120'],
            'principal_identifier' => ['required', 'string', 'max:180'],
            'ability' => ['required', 'string', 'max:120'],
        ]);

        $tenantId = $this->resolveTenantId($request);

        return response()->json([
            'data' => $this->orchestrator->addPermission($tenantId, $document, $payload),
        ], Response::HTTP_CREATED);
    }

    public function listRelations(int $document): JsonResponse
    {
        $tenantId = $this->resolveTenantId(request());

        return response()->json([
            'data' => $this->orchestrator->listRelations($tenantId, $document),
        ]);
    }

    public function addRelation(Request $request, int $document): JsonResponse
    {
        $payload = $request->validate([
            'target_document_id' => ['required', 'integer'],
            'relation_type' => ['nullable', 'string', 'max:120'],
        ]);

        $tenantId = $this->resolveTenantId($request);

        return response()->json([
            'data' => $this->orchestrator->addRelation($tenantId, $document, $payload),
        ], Response::HTTP_CREATED);
    }

    public function listDocumentTypes(): JsonResponse
    {
        return response()->json([
            'data' => $this->orchestrator->listDocumentTypes(),
        ]);
    }

    public function createDocumentType(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100'],
            'default_status' => ['nullable', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'requires_source' => ['nullable', 'boolean'],
        ]);

        $tenantId = $this->resolveTenantId($request);

        return response()->json([
            'data' => $this->orchestrator->createDocumentType($tenantId, $payload),
        ], Response::HTTP_CREATED);
    }

    public function listItemTypes(): JsonResponse
    {
        return response()->json([
            'data' => $this->orchestrator->listItemTypes(),
        ]);
    }

    public function createItemType(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100'],
            'display_name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $tenantId = $this->resolveTenantId($request);

        return response()->json([
            'data' => $this->orchestrator->createItemType($tenantId, $payload),
        ], Response::HTTP_CREATED);
    }

    public function listDocumentDefinitions(): JsonResponse
    {
        $tenantId = $this->resolveTenantId(request());

        return response()->json([
            'data' => $this->orchestrator->listDocumentDefinitions($tenantId),
        ]);
    }

    public function createDocumentDefinition(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'version' => ['nullable', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'allowed_item_types' => ['nullable', 'array'],
            'allowed_item_types.*' => ['nullable'],
            'settings' => ['nullable', 'array'],
            'validation_rules' => ['nullable', 'array'],
            'sections' => ['nullable', 'array'],
            'sections.*.section_key' => ['nullable', 'string', 'max:120'],
            'sections.*.label' => ['nullable', 'string', 'max:180'],
            'sections.*.display_order' => ['nullable', 'integer', 'min:1'],
            'sections.*.is_visible' => ['nullable', 'boolean'],
            'sections.*.field_keys' => ['nullable', 'array'],
            'sections.*.field_keys.*' => ['string', 'max:120'],
            'form_layout' => ['nullable', 'array'],
            'fields' => ['nullable', 'array'],
            'fields.*.field_key' => ['required_with:fields', 'string', 'max:120'],
            'fields.*.label' => ['nullable', 'string', 'max:180'],
            'fields.*.data_type' => ['required_with:fields', 'string', 'max:40'],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.display_order' => ['nullable', 'integer', 'min:1'],
            'fields.*.default_value' => ['nullable', 'string', 'max:255'],
            'fields.*.validation_rule' => ['nullable', 'string', 'max:1000'],
        ]);

        $tenantId = $this->resolveTenantId($request);

        return response()->json([
            'data' => $this->orchestrator->createDocumentDefinition($tenantId, $payload),
        ], Response::HTTP_CREATED);
    }

    public function listItemDefinitions(): JsonResponse
    {
        $tenantId = $this->resolveTenantId(request());

        return response()->json([
            'data' => $this->orchestrator->listItemDefinitions($tenantId),
        ]);
    }

    public function createItemDefinition(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'item_type_id' => ['required', 'integer', 'exists:document_item_types,id'],
            'version' => ['nullable', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'settings' => ['nullable', 'array'],
            'validation_rules' => ['nullable', 'array'],
            'fields' => ['nullable', 'array'],
            'fields.*.field_key' => ['required_with:fields', 'string', 'max:120'],
            'fields.*.label' => ['nullable', 'string', 'max:180'],
            'fields.*.data_type' => ['required_with:fields', 'string', 'max:40'],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.display_order' => ['nullable', 'integer', 'min:1'],
            'fields.*.default_value' => ['nullable', 'string', 'max:255'],
            'fields.*.validation_rule' => ['nullable', 'string', 'max:1000'],
        ]);

        $tenantId = $this->resolveTenantId($request);

        return response()->json([
            'data' => $this->orchestrator->createItemDefinition($tenantId, $payload),
        ], Response::HTTP_CREATED);
    }

    private function resolveTenantId(Request $request): int
    {
        $resolved = $this->currentTenantContext->currentTenantId();
        if ($resolved !== null) {
            return $resolved;
        }

        $payloadTenant = $request->integer('tenant_id');
        if ($payloadTenant > 0) {
            return $payloadTenant;
        }

        abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Tenant context is required.');
    }
}
