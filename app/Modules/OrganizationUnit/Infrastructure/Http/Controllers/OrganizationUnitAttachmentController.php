<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UploadOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\UploadOrganizationUnitAttachmentRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Resources\OrganizationUnitAttachmentResource;
use OpenApi\Attributes as OA;

class OrganizationUnitAttachmentController extends Controller
{
    public function __construct(
        protected UploadOrganizationUnitAttachmentServiceInterface $uploadService,
        protected DeleteOrganizationUnitAttachmentServiceInterface $deleteService,
        protected OrganizationUnitAttachmentRepositoryInterface $attachmentRepo,
        protected FileStorageServiceInterface $storage
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
        $type = $request->query('type');
        $attachments = $this->attachmentRepo->getByOrganizationUnit($orgUnitId, $type);

        return OrganizationUnitAttachmentResource::collection($attachments);
    }

    #[OA\Post(
        path: '/api/org-units/{orgUnitId}/attachments',
        summary: 'Upload organization unit attachment',
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
    public function store(UploadOrganizationUnitAttachmentRequest $request, int $orgUnitId): OrganizationUnitAttachmentResource
    {
        $this->authorize('uploadAttachment', OrganizationUnit::class);
        $file = $request->file('file');
        $fileInfo = [
            'tmp_path' => $file->getPathname(),
            'name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];
        $attachment = $this->uploadService->execute([
            'organization_unit_id' => $orgUnitId,
            'file' => $fileInfo,
            'type' => $request->input('type'),
            'metadata' => $request->input('metadata'),
        ]);

        return new OrganizationUnitAttachmentResource($attachment);
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
        ],
    )]
    public function destroy(int $orgUnitId, int $attachmentId): JsonResponse
    {
        $this->authorize('deleteAttachment', OrganizationUnit::class);
        $this->deleteService->execute(['attachment_id' => $attachmentId]);

        return response()->json(['message' => 'Attachment deleted successfully']);
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
        $attachment = $this->attachmentRepo->findByUuid($uuid);
        if (! $attachment) {
            abort(404);
        }
        $this->authorize('view', $attachment);

        return $this->storage->stream($attachment->getFilePath());
    }
}
