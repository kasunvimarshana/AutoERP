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

    public function updateMetadata(Request $request, int $document): JsonResponse
    {
        $payload = $request->validate([
            'metadata' => ['required', 'array'],
            'metadata.*.field_key' => ['required', 'string', 'max:120'],
            'metadata.*.value_type' => ['nullable', 'string', 'max:40'],
            'metadata.*.value' => ['nullable'],
        ]);

        $tenantId = $this->resolveTenantId($request);

        return response()->json([
            'data' => $this->orchestrator->updateDocumentMetadata($tenantId, $document, $payload['metadata']),
        ]);
    }

    public function listLines(int $document): JsonResponse
    {
        $tenantId = $this->resolveTenantId(request());

        return response()->json([
            'data' => $this->orchestrator->listDocumentLines($tenantId, $document),
        ]);
    }

    public function previewDocument(Request $request, int $document): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $aggregate = $this->orchestrator->show($tenantId, $document);

        return response()->json([
            'input' => [
                'document_id' => $document,
            ],
            'rendered' => [
                'html' => '<article><h1>' . e($aggregate->document->documentNumber) . '</h1><p>Status: ' . e($aggregate->document->status) . '</p></article>',
                'document_number' => $aggregate->document->documentNumber,
                'status' => $aggregate->document->status,
            ],
            'metadata' => [
                'official' => false,
                'business_logic_free' => true,
            ],
            'warnings' => [],
            'errors' => [],
        ]);
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

    public function listAttachments(int $document): JsonResponse
    {
        $tenantId = $this->resolveTenantId(request());

        return response()->json([
            'data' => $this->orchestrator->listAttachments($tenantId, $document),
        ]);
    }

    public function removeAttachment(int $document, int $attachment): JsonResponse
    {
        $tenantId = $this->resolveTenantId(request());
        $deleted = $this->orchestrator->removeAttachment($tenantId, $document, $attachment);

        return response()->json(['data' => ['deleted' => $deleted]]);
    }

    public function listVersions(int $document): JsonResponse
    {
        $tenantId = $this->resolveTenantId(request());

        return response()->json([
            'data' => $this->orchestrator->listVersions($tenantId, $document),
        ]);
    }

    public function showVersion(int $document, int $version): JsonResponse
    {
        $tenantId = $this->resolveTenantId(request());
        $payload = $this->orchestrator->getVersion($tenantId, $document, $version);

        if ($payload === null) {
            return response()->json(['message' => 'Document version not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $payload]);
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

    public function updatePermissions(Request $request, int $document): JsonResponse
    {
        $payload = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*.principal_type' => ['required', 'string', 'max:120'],
            'permissions.*.principal_identifier' => ['required', 'string', 'max:180'],
            'permissions.*.ability' => ['required', 'string', 'max:120'],
        ]);

        $tenantId = $this->resolveTenantId($request);

        return response()->json([
            'data' => $this->orchestrator->updatePermissions($tenantId, $document, $payload['permissions']),
        ]);
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

    public function removeRelation(int $document, int $relation): JsonResponse
    {
        $tenantId = $this->resolveTenantId(request());
        $deleted = $this->orchestrator->removeRelation($tenantId, $document, $relation);

        return response()->json(['data' => ['deleted' => $deleted]]);
    }

    public function listDocumentTypes(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        return response()->json([
            'data' => $this->orchestrator->listDocumentTypes($tenantId),
        ]);
    }

    public function createDocumentType(Request $request): JsonResponse
    {
        $payload = $this->validateDocumentTypePayload($request);

        $tenantId = $this->resolveTenantId($request);

        return response()->json([
            'data' => $this->orchestrator->createDocumentType($tenantId, $payload),
        ], Response::HTTP_CREATED);
    }

    public function showDocumentType(Request $request, int $type): JsonResponse
    {
        $payload = $this->orchestrator->getDocumentType($this->resolveTenantId($request), $type);

        if ($payload === null) {
            return response()->json(['message' => 'Document type not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $payload]);
    }

    public function updateDocumentType(Request $request, int $type): JsonResponse
    {
        $payload = $this->validateDocumentTypePayload($request);
        $tenantId = $this->resolveTenantId($request);

        return response()->json([
            'data' => $this->orchestrator->updateDocumentType($tenantId, $type, $payload),
        ]);
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
            'definition_code' => ['nullable', 'string', 'max:120'],
            'version' => ['nullable', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'source_module' => ['nullable', 'string', 'max:120'],
            'template_id' => ['nullable', 'integer', 'min:1'],
            'sequence_id' => ['nullable', 'integer', 'min:1'],
            'workflow_id' => ['nullable', 'integer', 'min:1'],
            'default_status' => ['nullable', 'string', 'max:120'],
            'supports_versions' => ['nullable', 'boolean'],
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
            'fields.*.is_readonly' => ['nullable', 'boolean'],
            'fields.*.section_key' => ['nullable', 'string', 'max:120'],
            'fields.*.display_order' => ['nullable', 'integer', 'min:1'],
            'fields.*.default_value' => ['nullable', 'string', 'max:255'],
            'fields.*.validation_rule' => ['nullable', 'string', 'max:1000'],
        ]);

        $tenantId = $this->resolveTenantId($request);

        return response()->json([
            'data' => $this->orchestrator->createDocumentDefinition($tenantId, $payload),
        ], Response::HTTP_CREATED);
    }

    public function showDocumentDefinition(int $definition): JsonResponse
    {
        $tenantId = $this->resolveTenantId(request());
        $payload = $this->orchestrator->getDocumentDefinition($tenantId, $definition);

        if ($payload === null) {
            return response()->json(['message' => 'Document definition not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $payload]);
    }

    public function updateDocumentDefinition(Request $request, int $definition): JsonResponse
    {
        $payload = $request->validate([
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'definition_code' => ['nullable', 'string', 'max:120'],
            'version' => ['nullable', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'source_module' => ['nullable', 'string', 'max:120'],
            'template_id' => ['nullable', 'integer', 'min:1'],
            'sequence_id' => ['nullable', 'integer', 'min:1'],
            'workflow_id' => ['nullable', 'integer', 'min:1'],
            'default_status' => ['nullable', 'string', 'max:120'],
            'supports_versions' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'settings' => ['nullable', 'array'],
            'fields' => ['nullable', 'array'],
            'fields.*.field_key' => ['required_with:fields', 'string', 'max:120'],
            'fields.*.label' => ['nullable', 'string', 'max:180'],
            'fields.*.data_type' => ['required_with:fields', 'string', 'max:40'],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.is_readonly' => ['nullable', 'boolean'],
            'fields.*.display_order' => ['nullable', 'integer', 'min:1'],
            'fields.*.default_value' => ['nullable', 'string', 'max:255'],
            'fields.*.validation_rule' => ['nullable', 'string', 'max:1000'],
        ]);

        $tenantId = $this->resolveTenantId($request);

        return response()->json([
            'data' => $this->orchestrator->updateDocumentDefinition($tenantId, $definition, $payload),
        ]);
    }

    public function listTemplates(): JsonResponse
    {
        $tenantId = $this->resolveTenantId(request());

        return response()->json(['data' => $this->orchestrator->listTemplates($tenantId)]);
    }

    public function createTemplate(Request $request): JsonResponse
    {
        $payload = $this->validateTemplatePayload($request);
        $tenantId = $this->resolveTenantId($request);

        return response()->json([
            'data' => $this->orchestrator->createTemplate($tenantId, $payload),
        ], Response::HTTP_CREATED);
    }

    public function showTemplate(int $template): JsonResponse
    {
        $tenantId = $this->resolveTenantId(request());
        $payload = $this->orchestrator->getTemplate($tenantId, $template);

        if ($payload === null) {
            return response()->json(['message' => 'Document template not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $payload]);
    }

    public function updateTemplate(Request $request, int $template): JsonResponse
    {
        $payload = $this->validateTemplatePayload($request);
        $tenantId = $this->resolveTenantId($request);

        return response()->json([
            'data' => $this->orchestrator->updateTemplate($tenantId, $template, $payload),
        ]);
    }

    public function previewTemplate(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'template_id' => ['nullable', 'integer', 'min:1'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
            'document_number' => ['nullable', 'string', 'max:180'],
        ]);
        $tenantId = $this->resolveTenantId($request);

        return response()->json($this->orchestrator->previewTemplate($tenantId, $payload));
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

    /**
     * @return array<string, mixed>
     */
    private function validateDocumentTypePayload(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'module_scope' => ['nullable', 'string', 'max:120'],
            'default_status' => ['nullable', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'requires_source' => ['nullable', 'boolean'],
            'supports_items' => ['nullable', 'boolean'],
            'supports_attachments' => ['nullable', 'boolean'],
            'supports_comments' => ['nullable', 'boolean'],
            'supports_versions' => ['nullable', 'boolean'],
            'supports_workflow' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTemplatePayload(Request $request): array
    {
        return $request->validate([
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'document_type_id' => ['nullable', 'integer', 'min:1', 'exists:document_types,id'],
            'template_code' => ['required', 'string', 'max:120'],
            'template_name' => ['required', 'string', 'max:255'],
            'layout_type' => ['nullable', 'string', 'max:80'],
            'header_content' => ['nullable', 'string'],
            'body_content' => ['nullable', 'string'],
            'footer_content' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
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
