<?php

namespace Modules\Tenant\Infrastructure\Http\Controllers;

use Modules\Tenant\Application\Contracts\UploadTenantAttachmentServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantAttachmentServiceInterface;
use Modules\Tenant\Infrastructure\Http\Requests\UploadTenantAttachmentRequest;
use Modules\Tenant\Infrastructure\Http\Resources\TenantAttachmentResource;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantAttachmentRepositoryInterface;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TenantAttachmentController extends Controller
{
    public function __construct(
        protected UploadTenantAttachmentServiceInterface $uploadService,
        protected DeleteTenantAttachmentServiceInterface $deleteService,
        protected TenantAttachmentRepositoryInterface $attachmentRepo,
        protected FileStorageServiceInterface $storage
    ) {}

    public function index(int $tenantId, Request $request)
    {
        $this->authorize('viewAttachments', Tenant::class);
        $type = $request->query('type');
        $attachments = $this->attachmentRepo->getByTenant($tenantId, $type);
        return TenantAttachmentResource::collection($attachments);
    }

    public function store(UploadTenantAttachmentRequest $request, int $tenantId): TenantAttachmentResource
    {
        $this->authorize('uploadAttachment', Tenant::class);
        $file = $request->file('file');
        $fileInfo = [
            'tmp_path' => $file->getPathname(),
            'name'     => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size'     => $file->getSize(),
        ];
        $attachment = $this->uploadService->execute([
            'tenant_id' => $tenantId,
            'file'      => $fileInfo,
            'type'      => $request->input('type'),
            'metadata'  => $request->input('metadata'),
        ]);
        return new TenantAttachmentResource($attachment);
    }

    public function destroy(int $tenantId, int $attachmentId): JsonResponse
    {
        $this->authorize('deleteAttachment', Tenant::class);
        $this->deleteService->execute(['attachment_id' => $attachmentId]);
        return response()->json(['message' => 'Attachment deleted successfully']);
    }

    public function serve(string $uuid)
    {
        $attachment = $this->attachmentRepo->findByUuid($uuid);
        if (!$attachment) {
            abort(404);
        }
        $this->authorize('view', $attachment);
        return $this->storage->stream($attachment->getFilePath());
    }
}
