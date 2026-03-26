<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\User\Application\Contracts\DeleteUserAttachmentServiceInterface;
use Modules\User\Application\Contracts\UploadUserAttachmentServiceInterface;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\RepositoryInterfaces\UserAttachmentRepositoryInterface;
use Modules\User\Infrastructure\Http\Requests\UploadUserAttachmentRequest;
use Modules\User\Infrastructure\Http\Resources\UserAttachmentResource;
use OpenApi\Attributes as OA;

class UserAttachmentController extends AuthorizedController
{
    public function __construct(
        protected UploadUserAttachmentServiceInterface $uploadService,
        protected DeleteUserAttachmentServiceInterface $deleteService,
        protected UserAttachmentRepositoryInterface $attachmentRepo,
        protected FileStorageServiceInterface $storage
    ) {}

    #[OA\Get(
        path: '/api/users/{userId}/attachments',
        summary: 'List user attachments',
        tags: ['User Attachments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path',  required: true,  schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'type',   in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'profile_picture')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of attachments',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/AttachmentObject'))),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(int $userId, Request $request)
    {
        $this->authorize('viewAttachments', User::class);
        $type = $request->query('type');
        $attachments = $this->attachmentRepo->getByUser($userId, $type);

        return UserAttachmentResource::collection($attachments);
    }

    #[OA\Post(
        path: '/api/users/{userId}/attachments',
        summary: 'Upload user attachment',
        tags: ['User Attachments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file',     type: 'string', format: 'binary'),
                        new OA\Property(property: 'type',     type: 'string', nullable: true, example: 'profile_picture'),
                        new OA\Property(property: 'metadata', type: 'string', nullable: true, example: '{}'),
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
    public function store(UploadUserAttachmentRequest $request, int $userId): UserAttachmentResource
    {
        $this->authorize('uploadAttachment', User::class);
        $file = $request->file('file');
        $fileInfo = [
            'tmp_path' => $file->getPathname(),
            'name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];
        $attachment = $this->uploadService->execute([
            'user_id' => $userId,
            'file' => $fileInfo,
            'type' => $request->input('type'),
            'metadata' => $request->input('metadata'),
        ]);

        return new UserAttachmentResource($attachment);
    }

    #[OA\Delete(
        path: '/api/users/{userId}/attachments/{attachmentId}',
        summary: 'Delete user attachment',
        tags: ['User Attachments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'userId',       in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
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
    public function destroy(int $userId, int $attachmentId): JsonResponse
    {
        $this->authorize('deleteAttachment', User::class);
        $this->deleteService->execute(['attachment_id' => $attachmentId]);

        return response()->json(['message' => 'Attachment deleted successfully']);
    }

    #[OA\Get(
        path: '/api/storage/user-attachments/{uuid}',
        summary: 'Serve user attachment',
        tags: ['User Attachments'],
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
