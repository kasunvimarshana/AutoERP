<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;
use Modules\Core\Application\Contracts\AttachmentStorageStrategyInterface;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Tenant\Application\Contracts\BulkUploadTenantAttachmentsServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantAttachmentServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantAttachmentsServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UploadTenantAttachmentServiceInterface;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Infrastructure\Http\Requests\ListTenantAttachmentRequest;
use Modules\Tenant\Infrastructure\Http\Requests\UploadTenantAttachmentRequest;
use Modules\Tenant\Infrastructure\Http\Resources\TenantAttachmentCollection;
use Modules\Tenant\Infrastructure\Http\Resources\TenantAttachmentResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TenantAttachmentController extends AuthorizedController
{
    public function __construct(
        protected FindTenantServiceInterface $findTenantService,
        protected UploadTenantAttachmentServiceInterface $uploadAttachmentService,
        protected BulkUploadTenantAttachmentsServiceInterface $bulkUploadAttachmentsService,
        protected DeleteTenantAttachmentServiceInterface $deleteAttachmentService,
        protected FindTenantAttachmentsServiceInterface $findTenantAttachmentsService,
        protected AttachmentStorageStrategyInterface $storageStrategy
    ) {}

    public function index(int $tenantId, ListTenantAttachmentRequest $request): TenantAttachmentCollection
    {
        $tenantEntity = $this->findTenantOrFail($tenantId);
        $this->authorize('viewAttachments', $tenantEntity);
        $validated = $request->validated();
        $type = $validated['type'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);
        $attachments = $this->findTenantAttachmentsService->paginateByTenant(
            $tenantId,
            is_string($type) ? $type : null,
            $perPage,
            $page
        );

        return new TenantAttachmentCollection($attachments);
    }

    public function store(UploadTenantAttachmentRequest $request, int $tenantId): TenantAttachmentResource
    {
        $tenantEntity = $this->findTenantOrFail($tenantId);
        $this->authorize('uploadAttachment', $tenantEntity);

        $file = $request->file('file');
        if ($file === null) {
            throw ValidationException::withMessages([
                'file' => ['A single file upload is required for this endpoint.'],
            ]);
        }

        $attachment = $this->uploadAttachmentService->execute([
            'tenant_id' => $tenantId,
            'file' => $file,
            'type' => $request->input('type'),
            'metadata' => $this->decodeMetadata($request),
        ]);

        return new TenantAttachmentResource($attachment);
    }

    public function storeMany(UploadTenantAttachmentRequest $request, int $tenantId): JsonResponse
    {
        $tenantEntity = $this->findTenantOrFail($tenantId);
        $this->authorize('uploadAttachment', $tenantEntity);

        $files = $request->file('files') ?? [];
        if ($files === [] || ! is_array($files)) {
            throw ValidationException::withMessages([
                'files' => ['A files array upload is required for this endpoint.'],
            ]);
        }

        $attachments = $this->bulkUploadAttachmentsService->execute([
            'tenant_id' => $tenantId,
            'files' => $files,
            'type' => $request->input('type'),
            'metadata' => $this->decodeMetadata($request),
        ]);

        return TenantAttachmentResource::collection($attachments)
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function destroy(int $tenantId, int $attachmentId): JsonResponse
    {
        $tenantEntity = $this->findTenantOrFail($tenantId);
        $this->authorize('deleteAttachment', $tenantEntity);

        $attachmentEntity = $this->findTenantAttachmentsService->find($attachmentId);
        if (! $attachmentEntity || $attachmentEntity->getTenantId() !== $tenantId) {
            throw new NotFoundHttpException('Attachment not found.');
        }

        $this->deleteAttachmentService->execute(['attachment_id' => $attachmentId]);

        return Response::json(['message' => 'Attachment deleted successfully']);
    }

    public function serve(string $uuid): StreamedResponse
    {
        $attachment = $this->findTenantAttachmentsService->findByUuid($uuid);
        if (! $attachment) {
            throw new NotFoundHttpException('Attachment not found.');
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

    private function findTenantOrFail(int $tenantId): Tenant
    {
        $tenant = $this->findTenantService->find($tenantId);
        if (! $tenant) {
            throw new NotFoundHttpException('Tenant not found.');
        }

        return $tenant;
    }
}
