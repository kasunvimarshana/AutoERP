<?php

namespace Modules\DocumentManagement\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\DocumentManagement\Application\UseCases\ArchiveDocumentUseCase;
use Modules\DocumentManagement\Application\UseCases\CreateDocumentUseCase;
use Modules\DocumentManagement\Application\UseCases\PublishDocumentUseCase;
use Modules\DocumentManagement\Domain\Contracts\DocumentRepositoryInterface;
use Modules\DocumentManagement\Presentation\Requests\StoreDocumentRequest;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepo,
        private CreateDocumentUseCase       $createUseCase,
        private PublishDocumentUseCase      $publishUseCase,
        private ArchiveDocumentUseCase      $archiveUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->documentRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $document = $this->createUseCase->execute(
            array_merge($request->validated(), [
                'tenant_id' => auth()->user()?->tenant_id,
                'owner_id'  => auth()->user()?->id,
            ])
        );

        return response()->json($document, 201);
    }

    public function show(string $id): JsonResponse
    {
        $document = $this->documentRepo->findById($id);

        if (! $document) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($document);
    }

    public function update(StoreDocumentRequest $request, string $id): JsonResponse
    {
        $document = $this->documentRepo->update($id, $request->validated());

        return response()->json($document);
    }

    public function publish(string $id): JsonResponse
    {
        $document = $this->publishUseCase->execute($id);

        return response()->json($document);
    }

    public function archive(string $id): JsonResponse
    {
        $document = $this->archiveUseCase->execute($id);

        return response()->json($document);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->documentRepo->delete($id);

        return response()->json(null, 204);
    }
}
