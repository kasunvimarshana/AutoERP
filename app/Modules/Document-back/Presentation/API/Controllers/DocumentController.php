<?php

namespace Modules\Document\Presentation\API\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
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
    ) {}

    public function index(ListDocumentsRequest $request): JsonResponse
    {
        $filters = array_filter(
            $request->validated() + [
                'tenant_id' => $request->validated('tenant_id', config('document.default_tenant_id')),
            ],
            static fn ($value): bool => $value !== null && $value !== '',
        );

        $documents = $this->orchestrator->list($filters, (int) ($filters['per_page'] ?? 15));

        return DocumentResource::collection($documents)->response();
    }

    public function store(CreateDocumentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $dto = new CreateDocumentDTO(
            tenantId: (int) ($validated['tenant_id'] ?? config('document.default_tenant_id')),
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
        return (new DocumentResource($this->orchestrator->show($document)))->response();
    }

    public function changeStatus(ChangeStatusRequest $request, int $document): JsonResponse
    {
        $aggregate = $this->orchestrator->changeStatus(
            $document,
            $request->validated('status'),
            $request->validated('action_name'),
        );

        return (new DocumentResource($aggregate))->response();
    }

    public function uploadAttachment(UploadDocumentAttachmentRequest $request, int $document): JsonResponse
    {
        $attachment = $this->orchestrator->uploadAttachment($document, $request->file('file'));

        return response()->json(['data' => $attachment], Response::HTTP_CREATED);
    }
}
