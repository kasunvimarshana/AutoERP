<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Application\Contracts\AttachmentStorageStrategyInterface;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Tenant\Application\Contracts\BulkUploadTenantAttachmentsServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantAttachmentServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantAttachmentsServiceInterface;
use Modules\Tenant\Application\Contracts\UploadTenantAttachmentServiceInterface;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Infrastructure\Http\Requests\UploadTenantAttachmentRequest;
use Modules\Tenant\Infrastructure\Http\Resources\TenantAttachmentResource;
use OpenApi\Attributes as OA;

class TenantAttachmentController extends AuthorizedController
{
    public function __construct(
        protected UploadTenantAttachmentServiceInterface $uploadService,
        protected BulkUploadTenantAttachmentsServiceInterface $bulkUploadService,
        protected DeleteTenantAttachmentServiceInterface $deleteService,
        protected FindTenantAttachmentsServiceInterface $findAttachmentsService,
        protected AttachmentStorageStrategyInterface $storageStrategy
    ) {}


    public function index(int $tenantId, Request $request)
    {
        $this->authorize('viewAttachments', Tenant::class);
        $type        = $request->query('type');
        $attachments = $this->findAttachmentsService->findByTenant($tenantId, $type);

        return TenantAttachmentResource::collection($attachments);
    }


    public function store(UploadTenantAttachmentRequest $request, int $tenantId): TenantAttachmentResource
    {
        $this->authorize('uploadAttachment', Tenant::class);

        $attachment = $this->uploadService->execute([
            'tenant_id' => $tenantId,
            'file'      => $request->file('file'),
            'type'      => $request->input('type'),
            'metadata'  => $this->decodeMetadata($request),
        ]);

        return new TenantAttachmentResource($attachment);
    }


    public function storeMany(UploadTenantAttachmentRequest $request, int $tenantId): JsonResponse
    {
        $this->authorize('uploadAttachment', Tenant::class);

        $attachments = $this->bulkUploadService->execute([
            'tenant_id' => $tenantId,
            'files'     => $request->file('files') ?? [],
            'type'      => $request->input('type'),
            'metadata'  => $this->decodeMetadata($request),
        ]);

        return TenantAttachmentResource::collection($attachments)
            ->response()
            ->setStatusCode(201);
    }


    public function destroy(int $tenantId, int $attachmentId): JsonResponse
    {
        $this->authorize('deleteAttachment', Tenant::class);

        $attachment = $this->findAttachmentsService->find($attachmentId);
        if (! $attachment) {
            abort(404);
        }

        $this->deleteService->execute(['attachment_id' => $attachmentId]);

        return response()->json(['message' => 'Attachment deleted successfully']);
    }


    public function serve(string $uuid)
    {
        $attachment = $this->findAttachmentsService->findByUuid($uuid);
        if (! $attachment) {
            abort(404);
        }
        $this->authorize('view', $attachment);

        return $this->storageStrategy->stream($attachment->getFilePath());
    }

    /**
     * Decode the optional JSON metadata string from the request.
     * The 'metadata' field is validated as valid JSON by UploadTenantAttachmentRequest.
     */
    private function decodeMetadata(Request $request): ?array
    {
        $raw = $request->input('metadata');
        if ($raw === null) {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
