<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantAttachmentServiceInterface;
use Modules\Tenant\Application\Contracts\UploadTenantAttachmentServiceInterface;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantAttachmentRepositoryInterface;
use Modules\Tenant\Infrastructure\Http\Requests\UploadTenantAttachmentRequest;
use Modules\Tenant\Infrastructure\Http\Resources\TenantAttachmentResource;
use OpenApi\Attributes as OA;

class TenantAttachmentController extends Controller
{
    public function __construct(
        protected UploadTenantAttachmentServiceInterface $uploadService,
        protected DeleteTenantAttachmentServiceInterface $deleteService,
        protected TenantAttachmentRepositoryInterface $attachmentRepo,
        protected FileStorageServiceInterface $storage
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
        $type = $request->query('type');
        $attachments = $this->attachmentRepo->getByTenant($tenantId, $type);

        return TenantAttachmentResource::collection($attachments);
    }

    #[OA\Post(
        path: '/api/tenants/{tenantId}/attachments',
        summary: 'Upload tenant attachment',
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
                        new OA\Property(property: 'metadata', type: 'string', nullable: true),
                    ],
                ),
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Attachment uploaded',
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
        $file = $request->file('file');
        $fileInfo = [
            'tmp_path' => $file->getPathname(),
            'name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];
        $attachment = $this->uploadService->execute([
            'tenant_id' => $tenantId,
            'file' => $fileInfo,
            'type' => $request->input('type'),
            'metadata' => $request->input('metadata'),
        ]);

        return new TenantAttachmentResource($attachment);
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
        ],
    )]
    public function destroy(int $tenantId, int $attachmentId): JsonResponse
    {
        $this->authorize('deleteAttachment', Tenant::class);
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
        $attachment = $this->attachmentRepo->findByUuid($uuid);
        if (! $attachment) {
            abort(404);
        }
        $this->authorize('view', $attachment);

        return $this->storage->stream($attachment->getFilePath());
    }
}
