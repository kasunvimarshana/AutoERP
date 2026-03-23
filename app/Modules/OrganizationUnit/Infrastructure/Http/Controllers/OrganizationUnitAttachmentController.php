<?php

namespace Modules\OrganizationUnit\Infrastructure\Http\Controllers;

use Modules\OrganizationUnit\Application\Services\UploadOrganizationUnitAttachmentService;
use Modules\OrganizationUnit\Application\Services\DeleteOrganizationUnitAttachmentService;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface;
use Modules\Core\Application\Services\FileStorageServiceInterface;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\UploadOrganizationUnitAttachmentRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Resources\OrganizationUnitAttachmentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationUnitAttachmentController
{
    public function __construct(
        protected UploadOrganizationUnitAttachmentService $uploadService,
        protected DeleteOrganizationUnitAttachmentService $deleteService,
        protected OrganizationUnitAttachmentRepositoryInterface $attachmentRepo,
        protected FileStorageServiceInterface $storage
    ) {}

    public function index(int $orgUnitId, Request $request)
    {
        $this->authorize('viewAttachments', OrganizationUnit::class);
        $type = $request->query('type');
        $attachments = $this->attachmentRepo->getByOrganizationUnit($orgUnitId, $type);
        return OrganizationUnitAttachmentResource::collection($attachments);
    }

    public function store(UploadOrganizationUnitAttachmentRequest $request, int $orgUnitId): OrganizationUnitAttachmentResource
    {
        $this->authorize('uploadAttachment', OrganizationUnit::class);
        $file = $request->file('file');
        $fileInfo = [
            'tmp_path' => $file->getPathname(),
            'name'     => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size'     => $file->getSize(),
        ];
        $attachment = $this->uploadService->execute([
            'organization_unit_id' => $orgUnitId,
            'file'                 => $fileInfo,
            'type'                 => $request->input('type'),
            'metadata'             => $request->input('metadata'),
        ]);
        return new OrganizationUnitAttachmentResource($attachment);
    }

    public function destroy(int $orgUnitId, int $attachmentId): JsonResponse
    {
        $this->authorize('deleteAttachment', OrganizationUnit::class);
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
        return response()->file(storage_path("app/public/{$attachment->getFilePath()}"));
    }
}
