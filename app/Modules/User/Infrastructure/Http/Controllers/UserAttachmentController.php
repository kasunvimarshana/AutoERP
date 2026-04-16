<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\User\Application\Contracts\DeleteUserAttachmentServiceInterface;
use Modules\User\Application\Contracts\FindUserAttachmentsServiceInterface;
use Modules\User\Application\Contracts\UploadUserAttachmentServiceInterface;
use Modules\User\Domain\Entities\User;
use Modules\User\Infrastructure\Http\Requests\UploadUserAttachmentRequest;
use Modules\User\Infrastructure\Http\Resources\UserAttachmentResource;
use OpenApi\Attributes as OA;

class UserAttachmentController extends AuthorizedController
{
    public function __construct(
        protected UploadUserAttachmentServiceInterface $uploadService,
        protected DeleteUserAttachmentServiceInterface $deleteService,
        protected FindUserAttachmentsServiceInterface $findService,
        protected FileStorageServiceInterface $storage
    ) {}

    public function index(int $userId, Request $request)
    {
        $this->authorize('viewAttachments', User::class);
        $type = $request->query('type');
        $attachments = $this->findService->getByUser($userId, $type);

        return UserAttachmentResource::collection($attachments);
    }

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


    public function destroy(int $userId, int $attachmentId): JsonResponse
    {
        $this->authorize('deleteAttachment', User::class);
        $this->deleteService->execute(['attachment_id' => $attachmentId]);

        return response()->json(['message' => 'Attachment deleted successfully']);
    }


    public function serve(string $uuid)
    {
        $attachment = $this->findService->findByUuid($uuid);
        if (! $attachment) {
            abort(404);
        }
        $this->authorize('view', $attachment);

        return $this->storage->stream($attachment->getFilePath());
    }
}
