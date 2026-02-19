<?php

declare(strict_types=1);

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Document\Enums\PermissionType;
use Modules\Document\Http\Requests\ShareDocumentRequest;
use Modules\Document\Http\Resources\DocumentShareResource;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentShare;
use Modules\Document\Services\DocumentShareService;

class DocumentShareController extends Controller
{
    public function __construct(
        private DocumentShareService $shareService,
    ) {}

    /**
     * Get shares for a document
     */
    public function index(Document $document): JsonResponse
    {
        $this->authorize('share', $document);

        $shares = $this->shareService->getDocumentShares($document->id);

        return ApiResponse::success(
            DocumentShareResource::collection($shares),
            'Shares retrieved successfully'
        );
    }

    /**
     * Share document with user
     */
    public function store(ShareDocumentRequest $request, Document $document): JsonResponse
    {
        $this->authorize('share', $document);

        $share = $this->shareService->share(
            $document->id,
            $request->user_id,
            PermissionType::from($request->permission_type),
            $request->expires_at ? new \DateTime($request->expires_at) : null
        );

        return ApiResponse::success(
            new DocumentShareResource($share->load('user')),
            'Document shared successfully',
            201
        );
    }

    /**
     * Bulk share with multiple users
     */
    public function bulkShare(Request $request, Document $document): JsonResponse
    {
        $this->authorize('share', $document);

        $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['required', 'string', 'exists:users,id'],
            'permission_type' => ['required', 'string', 'in:'.implode(',', array_column(PermissionType::cases(), 'value'))],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $shares = $this->shareService->bulkShare(
            $document->id,
            $request->user_ids,
            PermissionType::from($request->permission_type),
            $request->expires_at ? new \DateTime($request->expires_at) : null
        );

        return ApiResponse::success(
            DocumentShareResource::collection(collect($shares)),
            'Document shared with '.count($shares).' user(s) successfully'
        );
    }

    /**
     * Update share permissions
     */
    public function update(Request $request, DocumentShare $share): JsonResponse
    {
        $this->authorize('share', $share->document);

        $request->validate([
            'permission_type' => ['sometimes', 'string', 'in:'.implode(',', array_column(PermissionType::cases(), 'value'))],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        if ($request->has('permission_type')) {
            $share = $this->shareService->updatePermission(
                $share->id,
                PermissionType::from($request->permission_type)
            );
        }

        if ($request->has('expires_at')) {
            $share = $this->shareService->extendExpiration(
                $share->id,
                new \DateTime($request->expires_at)
            );
        }

        return ApiResponse::success(
            new DocumentShareResource($share),
            'Share updated successfully'
        );
    }

    /**
     * Revoke share
     */
    public function destroy(DocumentShare $share): JsonResponse
    {
        $this->authorize('share', $share->document);

        $this->shareService->revoke($share->id);

        return ApiResponse::success(null, 'Share revoked successfully');
    }

    /**
     * Get documents shared with current user
     */
    public function sharedWithMe(Request $request): JsonResponse
    {
        $documents = $this->shareService->getSharedWithUser($request->user()->id);

        return ApiResponse::success(
            \Modules\Document\Http\Resources\DocumentResource::collection($documents),
            'Shared documents retrieved successfully'
        );
    }

    /**
     * Get user permissions for document
     */
    public function permissions(Document $document): JsonResponse
    {
        $permissions = $this->shareService->getUserPermissions($document->id, auth()->id());

        return ApiResponse::success(
            ['permissions' => array_map(fn ($p) => $p->value, $permissions)],
            'Permissions retrieved successfully'
        );
    }

    /**
     * Check specific permission
     */
    public function checkPermission(Request $request, Document $document): JsonResponse
    {
        $request->validate([
            'permission' => ['required', 'string', 'in:'.implode(',', array_column(PermissionType::cases(), 'value'))],
        ]);

        $hasPermission = $this->shareService->checkPermission(
            $document->id,
            auth()->id(),
            PermissionType::from($request->permission)
        );

        return ApiResponse::success(
            ['has_permission' => $hasPermission],
            'Permission checked successfully'
        );
    }
}
