<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Application\Contracts\AttachmentStorageStrategyInterface;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Tenant\Application\Contracts\BulkUploadTenantAttachmentsServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantAttachmentServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantAttachmentsServiceInterface;
use Modules\Tenant\Application\Contracts\UploadTenantAttachmentServiceInterface;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Infrastructure\Http\Requests\ListTenantAttachmentRequest;
use Modules\Tenant\Infrastructure\Http\Requests\UploadTenantAttachmentRequest;
use Modules\Tenant\Infrastructure\Http\Resources\TenantAttachmentCollection;
use Modules\Tenant\Infrastructure\Http\Resources\TenantAttachmentResource;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TenantAttachmentController extends AuthorizedController
{
    public function __construct(
        protected FindTenantServiceInterface $findTenantService,
        protected UploadTenantAttachmentServiceInterface $uploadService,
        protected BulkUploadTenantAttachmentsServiceInterface $bulkUploadService,
        protected DeleteTenantAttachmentServiceInterface $deleteService,
        protected FindTenantAttachmentsServiceInterface $findAttachmentsService,
        protected AttachmentStorageStrategyInterface $storageStrategy
    ) {}


    public function index(int $tenant, ListTenantAttachmentRequest $request): TenantAttachmentCollection
    {
        $tenantEntity = $this->findTenantOrFail($tenant);
        $this->authorize('viewAttachments', $tenantEntity);
        $validated = $request->validated();
        $type = $validated['type'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);
        $attachments = $this->findAttachmentsService->paginateByTenant(
            $tenant,
            is_string($type) ? $type : null,
            $perPage,
            $page
        );

        return new TenantAttachmentCollection($attachments);
    }


    public function store(UploadTenantAttachmentRequest $request, int $tenant): TenantAttachmentResource
    {
        $tenantEntity = $this->findTenantOrFail($tenant);
        $this->authorize('uploadAttachment', $tenantEntity);

        $attachment = $this->uploadService->execute([
            'tenant_id' => $tenant,
            'file'      => $request->file('file'),
            'type'      => $request->input('type'),
            'metadata'  => $this->decodeMetadata($request),
        ]);

        return new TenantAttachmentResource($attachment);
    }


    public function storeMany(UploadTenantAttachmentRequest $request, int $tenant): JsonResponse
    {
        $tenantEntity = $this->findTenantOrFail($tenant);
        $this->authorize('uploadAttachment', $tenantEntity);

        $attachments = $this->bulkUploadService->execute([
            'tenant_id' => $tenant,
            'files'     => $request->file('files') ?? [],
            'type'      => $request->input('type'),
            'metadata'  => $this->decodeMetadata($request),
        ]);

        return TenantAttachmentResource::collection($attachments)
            ->response()
            ->setStatusCode(201);
    }


    public function destroy(int $tenant, int $attachment): JsonResponse
    {
        $tenantEntity = $this->findTenantOrFail($tenant);
        $this->authorize('deleteAttachment', $tenantEntity);

        $attachmentEntity = $this->findAttachmentsService->find($attachment);
        if (! $attachmentEntity || $attachmentEntity->getTenantId() !== $tenant) {
            throw new NotFoundHttpException('Attachment not found.');
        }

        $this->deleteService->execute(['attachment_id' => $attachment]);

        return Response::json(['message' => 'Attachment deleted successfully']);
    }


    public function serve(string $uuid): StreamedResponse
    {
        $attachment = $this->findAttachmentsService->findByUuid($uuid);
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
