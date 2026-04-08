<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\OrganizationUnit\Application\Contracts\AttachmentStorageStrategyInterface;
use Modules\OrganizationUnit\Application\Contracts\BulkUploadOrganizationUnitAttachmentsServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitAttachmentsServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\ReplaceOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UploadOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\UpdateOrganizationUnitAttachmentRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\UploadOrganizationUnitAttachmentRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Resources\OrganizationUnitAttachmentResource;
use OpenApi\Attributes as OA;

class OrganizationUnitAttachmentController extends AuthorizedController
{
    public function __construct(
        protected UploadOrganizationUnitAttachmentServiceInterface $uploadService,
        protected BulkUploadOrganizationUnitAttachmentsServiceInterface $bulkUploadService,
        protected DeleteOrganizationUnitAttachmentServiceInterface $deleteService,
        protected ReplaceOrganizationUnitAttachmentServiceInterface $replaceService,
        protected UpdateOrganizationUnitAttachmentServiceInterface $updateAttachmentService,
        protected FindOrganizationUnitAttachmentsServiceInterface $findAttachmentsService,
        protected AttachmentStorageStrategyInterface $storageStrategy
    ) {}

    #[OA\Get(
        path: '/api/org-units/{orgUnitId}/attachments',
        summary: 'List organization unit attachments',
        tags: ['OrgUnit Attachments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'orgUnitId', in: 'path',  required: true,  schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'type',      in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of attachments',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/AttachmentObject'))),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(int $orgUnitId, Request $request)
    {
        $this->authorize('viewAttachments', OrganizationUnit::class);
        $type        = $request->query('type');
        $attachments = $this->findAttachmentsService->findByOrganizationUnit($orgUnitId, $type);

        return OrganizationUnitAttachmentResource::collection($attachments);
    }

    #[OA\Post(
        path: '/api/org-units/{orgUnitId}/attachments',
        summary: 'Upload a single organization unit attachment',
        tags: ['OrgUnit Attachments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'orgUnitId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
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
    public function store(UploadOrganizationUnitAttachmentRequest $request, int $orgUnitId): JsonResponse
    {
        $this->authorize('uploadAttachment', OrganizationUnit::class);

        $attachment = $this->uploadService->execute([
            'organization_unit_id' => $orgUnitId,
            'file'                 => $request->file('file'),
            'type'                 => $request->input('type'),
            'metadata'             => $this->decodeMetadata($request),
        ]);

        return (new OrganizationUnitAttachmentResource($attachment))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Post(
        path: '/api/org-units/{orgUnitId}/attachments/bulk',
        summary: 'Upload multiple organization unit attachments in one request',
        tags: ['OrgUnit Attachments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'orgUnitId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
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
    public function storeMany(UploadOrganizationUnitAttachmentRequest $request, int $orgUnitId): JsonResponse
    {
        $this->authorize('uploadAttachment', OrganizationUnit::class);

        $attachments = $this->bulkUploadService->execute([
            'organization_unit_id' => $orgUnitId,
            'files'                => $request->file('files') ?? [],
            'type'                 => $request->input('type'),
            'metadata'             => $this->decodeMetadata($request),
        ]);

        return OrganizationUnitAttachmentResource::collection($attachments)
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Delete(
        path: '/api/org-units/{orgUnitId}/attachments/{attachmentId}',
        summary: 'Delete organization unit attachment',
        tags: ['OrgUnit Attachments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'orgUnitId',    in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
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
    public function destroy(int $orgUnitId, int $attachmentId): JsonResponse
    {
        $this->authorize('deleteAttachment', OrganizationUnit::class);

        $attachment = $this->findAttachmentsService->find($attachmentId);
        if (! $attachment) {
            abort(404);
        }

        $this->deleteService->execute(['attachment_id' => $attachmentId]);

        return response()->json(['message' => 'Attachment deleted successfully']);
    }

    #[OA\Patch(
        path: '/api/org-units/{orgUnitId}/attachments/{attachmentId}',
        summary: 'Update organization unit attachment metadata',
        description: 'Updates the type and/or metadata of an attachment without replacing the stored file.',
        tags: ['OrgUnit Attachments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'orgUnitId',    in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'attachmentId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'type',     type: 'string', nullable: true),
                        new OA\Property(property: 'metadata', type: 'object', nullable: true),
                    ],
                ),
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Attachment updated',
                content: new OA\JsonContent(ref: '#/components/schemas/AttachmentObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function update(UpdateOrganizationUnitAttachmentRequest $request, int $orgUnitId, int $attachmentId): JsonResponse
    {
        $this->authorize('uploadAttachment', OrganizationUnit::class);

        $payload = ['attachment_id' => $attachmentId];
        if ($request->has('type')) {
            $payload['type'] = $request->input('type');
        }
        if ($request->has('metadata')) {
            $payload['metadata'] = $request->input('metadata');
        }

        $updated = $this->updateAttachmentService->execute($payload);

        return (new OrganizationUnitAttachmentResource($updated))
            ->response()
            ->setStatusCode(200);
    }

    #[OA\Post(
        path: '/api/org-units/{orgUnitId}/attachments/{attachmentId}/replace',
        summary: 'Replace an existing organization unit attachment',
        description: 'Deletes the old stored file and replaces it with the uploaded file. The attachment UUID and record ID are preserved.',
        tags: ['OrgUnit Attachments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'orgUnitId',    in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'attachmentId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
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
            new OA\Response(response: 200, description: 'Attachment replaced',
                content: new OA\JsonContent(ref: '#/components/schemas/AttachmentObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function replace(UploadOrganizationUnitAttachmentRequest $request, int $orgUnitId, int $attachmentId): JsonResponse
    {
        $this->authorize('uploadAttachment', OrganizationUnit::class);

        $attachment = $this->findAttachmentsService->find($attachmentId);
        if (! $attachment) {
            abort(404);
        }

        $payload = ['attachment_id' => $attachmentId, 'file' => $request->file('file')];
        if ($request->has('type')) {
            $payload['type'] = $request->input('type');
        }
        if ($request->has('metadata')) {
            $payload['metadata'] = $this->decodeMetadata($request);
        }

        $updated = $this->replaceService->execute($payload);

        return (new OrganizationUnitAttachmentResource($updated))
            ->response()
            ->setStatusCode(200);
    }

    #[OA\Get(
        path: '/api/storage/org-unit-attachments/{uuid}',
        summary: 'Serve organization unit attachment',
        tags: ['OrgUnit Attachments'],
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
     * The 'metadata' field is validated as valid JSON by UploadOrganizationUnitAttachmentRequest.
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
