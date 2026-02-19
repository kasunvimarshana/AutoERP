<?php

declare(strict_types=1);

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Document\Http\Requests\StoreDocumentRequest;
use Modules\Document\Http\Requests\UpdateDocumentRequest;
use Modules\Document\Http\Resources\DocumentResource;
use Modules\Document\Models\Document;
use Modules\Document\Repositories\DocumentRepository;
use Modules\Document\Repositories\DocumentTagRepository;
use Modules\Document\Services\DocumentSearchService;
use Modules\Document\Services\DocumentStorageService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private DocumentStorageService $storageService,
        private DocumentSearchService $searchService,
        private DocumentTagRepository $tagRepository,
    ) {}

    /**
     * Display a listing of documents
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'folder_id', 'status', 'type', 'access_level',
            'mime_type', 'extension', 'from_date', 'to_date', 'per_page',
        ]);

        if ($request->has('search')) {
            $documents = $this->searchService->search($request->search, $filters);
        } elseif ($request->has('folder_id')) {
            $documents = $this->documentRepository->getByFolder($request->folder_id, $filters);
        } else {
            $documents = $this->documentRepository->paginate($filters['per_page'] ?? 15);
        }

        return ApiResponse::paginated(
            $documents->setCollection(
                $documents->getCollection()->map(fn ($doc) => new DocumentResource($doc))
            ),
            'Documents retrieved successfully'
        );
    }

    /**
     * Upload a new document
     */
    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $document = $this->storageService->upload(
            $request->file('file'),
            $request->folder_id,
            $request->description,
            $request->status ? \Modules\Document\Enums\DocumentStatus::from($request->status) : null,
            $request->access_level ? \Modules\Document\Enums\AccessLevel::from($request->access_level) : null
        );

        // Attach tags if provided
        if ($request->has('tags')) {
            $tagIds = [];
            foreach ($request->tags as $tagName) {
                $tag = $this->tagRepository->getOrCreate($tagName);
                $tagIds[] = $tag->id;
            }
            $document->tags()->sync($tagIds);
        }

        return ApiResponse::success(
            new DocumentResource($document->load(['folder', 'owner', 'tags'])),
            'Document uploaded successfully',
            201
        );
    }

    /**
     * Display the specified document
     */
    public function show(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        return ApiResponse::success(
            new DocumentResource($document->load(['folder', 'owner', 'tags', 'versions'])),
            'Document retrieved successfully'
        );
    }

    /**
     * Update the specified document
     */
    public function update(UpdateDocumentRequest $request, Document $document): JsonResponse
    {
        $this->authorize('update', $document);

        $document->update($request->validated());

        // Update tags if provided
        if ($request->has('tags')) {
            $tagIds = [];
            foreach ($request->tags as $tagName) {
                $tag = $this->tagRepository->getOrCreate($tagName);
                $tagIds[] = $tag->id;
            }
            $document->tags()->sync($tagIds);
        }

        return ApiResponse::success(
            new DocumentResource($document->load(['folder', 'owner', 'tags'])),
            'Document updated successfully'
        );
    }

    /**
     * Remove the specified document
     */
    public function destroy(Document $document): JsonResponse
    {
        $this->authorize('delete', $document);

        $this->storageService->delete($document->id, false);

        return ApiResponse::success(null, 'Document deleted successfully');
    }

    /**
     * Download document
     */
    public function download(Document $document): StreamedResponse
    {
        $this->authorize('download', $document);

        $fileInfo = $this->storageService->getForDownload($document->id);

        return \Storage::disk($fileInfo['disk'])->download(
            $fileInfo['path'],
            $fileInfo['filename'],
            [
                'Content-Type' => $fileInfo['mime_type'],
            ]
        );
    }

    /**
     * Stream document
     */
    public function stream(Document $document): StreamedResponse
    {
        $this->authorize('view', $document);

        $fileInfo = $this->storageService->getForStreaming($document->id);

        return \Storage::disk($fileInfo['disk'])->response(
            $fileInfo['path'],
            $fileInfo['filename'],
            [
                'Content-Type' => $fileInfo['mime_type'],
            ]
        );
    }

    /**
     * Move document to folder
     */
    public function move(Request $request, Document $document): JsonResponse
    {
        $this->authorize('update', $document);

        $request->validate([
            'folder_id' => ['nullable', 'string', 'exists:folders,id'],
        ]);

        $document = $this->storageService->move($document->id, $request->folder_id);

        return ApiResponse::success(
            new DocumentResource($document->load(['folder', 'owner'])),
            'Document moved successfully'
        );
    }

    /**
     * Copy document
     */
    public function copy(Request $request, Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $request->validate([
            'folder_id' => ['nullable', 'string', 'exists:folders,id'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $copy = $this->storageService->copy($document->id, $request->folder_id, $request->name);

        return ApiResponse::success(
            new DocumentResource($copy->load(['folder', 'owner'])),
            'Document copied successfully'
        );
    }

    /**
     * Restore soft-deleted document
     */
    public function restore(string $id): JsonResponse
    {
        $document = Document::withTrashed()->findOrFail($id);

        $this->authorize('restore', $document);

        $document->restore();

        return ApiResponse::success(
            new DocumentResource($document->load(['folder', 'owner'])),
            'Document restored successfully'
        );
    }

    /**
     * Get document URL
     */
    public function getUrl(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $url = $this->storageService->getUrl($document->id);

        return ApiResponse::success(['url' => $url], 'URL generated successfully');
    }
}
