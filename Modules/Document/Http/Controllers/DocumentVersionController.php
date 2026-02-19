<?php

declare(strict_types=1);

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Document\Http\Resources\DocumentVersionResource;
use Modules\Document\Models\Document;
use Modules\Document\Services\DocumentVersionService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentVersionController extends Controller
{
    public function __construct(
        private DocumentVersionService $versionService,
    ) {}

    /**
     * List versions for a document
     */
    public function index(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $versions = $this->versionService->listVersions($document->id);

        return ApiResponse::success(
            DocumentVersionResource::collection($versions),
            'Versions retrieved successfully'
        );
    }

    /**
     * Get specific version
     */
    public function show(Document $document, int $versionNumber): JsonResponse
    {
        $this->authorize('view', $document);

        $version = $this->versionService->getVersion($document->id, $versionNumber);

        if (! $version) {
            return ApiResponse::error('Version not found', 404);
        }

        return ApiResponse::success(
            new DocumentVersionResource($version),
            'Version retrieved successfully'
        );
    }

    /**
     * Restore a version
     */
    public function restore(Request $request, Document $document, int $versionNumber): JsonResponse
    {
        $this->authorize('update', $document);

        $request->validate([
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $document = $this->versionService->restoreVersion(
            $document->id,
            $versionNumber,
            $request->comment
        );

        return ApiResponse::success(
            ['document_id' => $document->id, 'new_version' => $document->version],
            'Version restored successfully'
        );
    }

    /**
     * Compare two versions
     */
    public function compare(Document $document, int $version1, int $version2): JsonResponse
    {
        $this->authorize('view', $document);

        $comparison = $this->versionService->compareVersions($document->id, $version1, $version2);

        return ApiResponse::success($comparison, 'Versions compared successfully');
    }

    /**
     * Download specific version
     */
    public function download(Document $document, string $versionId): StreamedResponse
    {
        $this->authorize('download', $document);

        return $this->versionService->downloadVersion($versionId);
    }

    /**
     * Delete old versions
     */
    public function cleanup(Request $request, Document $document): JsonResponse
    {
        $this->authorize('update', $document);

        $request->validate([
            'keep_latest' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $count = $this->versionService->deleteOldVersions(
            $document->id,
            $request->keep_latest ?? 5
        );

        return ApiResponse::success(
            ['deleted_count' => $count],
            "Deleted {$count} old version(s)"
        );
    }
}
