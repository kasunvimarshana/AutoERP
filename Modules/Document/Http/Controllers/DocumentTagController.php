<?php

declare(strict_types=1);

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Document\Http\Requests\StoreDocumentTagRequest;
use Modules\Document\Http\Resources\DocumentTagResource;
use Modules\Document\Models\DocumentTag;
use Modules\Document\Repositories\DocumentTagRepository;

class DocumentTagController extends Controller
{
    public function __construct(
        private DocumentTagRepository $tagRepository,
    ) {}

    /**
     * Display a listing of tags
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DocumentTag::class);

        if ($request->has('search')) {
            $tags = $this->tagRepository->search($request->search);
        } elseif ($request->boolean('popular')) {
            $tags = $this->tagRepository->getPopular($request->get('limit', 10));
        } else {
            $tags = $this->tagRepository->all();
        }

        return ApiResponse::success(
            DocumentTagResource::collection($tags),
            'Tags retrieved successfully'
        );
    }

    /**
     * Create a new tag
     */
    public function store(StoreDocumentTagRequest $request): JsonResponse
    {
        $this->authorize('create', DocumentTag::class);

        $tag = $this->tagRepository->create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $request->name,
            'color' => $request->color,
            'description' => $request->description,
        ]);

        return ApiResponse::success(
            new DocumentTagResource($tag),
            'Tag created successfully',
            201
        );
    }

    /**
     * Display the specified tag
     */
    public function show(DocumentTag $tag): JsonResponse
    {
        $this->authorize('view', $tag);

        return ApiResponse::success(
            new DocumentTagResource($tag->loadCount('documents')),
            'Tag retrieved successfully'
        );
    }

    /**
     * Update the specified tag
     */
    public function update(Request $request, DocumentTag $tag): JsonResponse
    {
        $this->authorize('update', $tag);

        $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $tag->update($request->validated());

        return ApiResponse::success(
            new DocumentTagResource($tag),
            'Tag updated successfully'
        );
    }

    /**
     * Remove the specified tag
     */
    public function destroy(DocumentTag $tag): JsonResponse
    {
        $this->authorize('delete', $tag);

        $tag->delete();

        return ApiResponse::success(null, 'Tag deleted successfully');
    }

    /**
     * Get documents with tag
     */
    public function documents(DocumentTag $tag, Request $request): JsonResponse
    {
        $documents = $tag->documents()
            ->where('is_latest_version', true)
            ->with(['folder', 'owner', 'tags'])
            ->paginate($request->get('per_page', 15));

        return ApiResponse::paginated(
            $documents->setCollection(
                $documents->getCollection()->map(fn ($doc) => new \Modules\Document\Http\Resources\DocumentResource($doc))
            ),
            'Documents retrieved successfully'
        );
    }
}
