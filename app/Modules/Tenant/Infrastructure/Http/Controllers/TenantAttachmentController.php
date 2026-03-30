<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Tenant\Application\Contracts\AttachmentStorageStrategyInterface;
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

    #[OA\Get(
        path: '/api/tenants/{tenantId}/attachments',
        summary: 'List tenant attachments',
        tags: ['Tenant Attachments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenantId', in: 'path',  required: true,  schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'type',     in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of tenant attachments',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/AttachmentObject'))),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(int $tenantId, Request $request)
    {
        $this->authorize('viewAttachments', Tenant::class);
        $type        = $request->query('type');
        $attachments = $this->findAttachmentsService->findByTenant($tenantId, $type);

        return TenantAttachmentResource::collection($attachments);
    }

    #[OA\Post(
        path: '/api/tenants/{tenantId}/attachments',
        summary: 'Upload a single tenant attachment',
        tags: ['Tenant Attachments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenantId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file',     type: 'string', format: 'binary'),
                        new OA\Property(property: 'type',     type: 'string', nullable: true),
                        new OA\Property(property: 'metadata', type: 'string', format: 'json', nullable: true),
                    ],
                ),
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Attachment uploaded',
                content: new OA\JsonContent(ref: '#/components/schemas/AttachmentObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
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

    #[OA\Post(
        path: '/api/tenants/{tenantId}/attachments/bulk',
        summary: 'Upload multiple tenant attachments in one request',
        tags: ['Tenant Attachments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenantId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['files[]'],
                    properties: [
                        new OA\Property(property: 'files[]', type: 'array',
                            items: new OA\Items(type: 'string', format: 'binary')),
                        new OA\Property(property: 'type',     type: 'string',  nullable: true),
                        new OA\Property(property: 'metadata', type: 'string',  format: 'json', nullable: true),
                    ],
                ),
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Attachments uploaded',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/AttachmentObject'))),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
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

    #[OA\Delete(
        path: '/api/tenants/{tenantId}/attachments/{attachmentId}',
        summary: 'Delete tenant attachment',
        tags: ['Tenant Attachments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenantId',     in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'attachmentId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
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

    #[OA\Get(
        path: '/api/storage/tenant-attachments/{uuid}',
        summary: 'Serve tenant attachment',
        tags: ['Tenant Attachments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'File stream'),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
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
