<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitAttachmentsServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UploadOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitAttachment;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\ListOrganizationUnitAttachmentRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\UploadOrganizationUnitAttachmentRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Resources\OrganizationUnitAttachmentCollection;
use Modules\OrganizationUnit\Infrastructure\Http\Resources\OrganizationUnitAttachmentResource;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrganizationUnitAttachmentController extends AuthorizedController
{
    public function __construct(
        protected FindOrganizationUnitServiceInterface $findOrganizationUnitService,
        protected UploadOrganizationUnitAttachmentServiceInterface $uploadAttachmentService,
        protected DeleteOrganizationUnitAttachmentServiceInterface $deleteAttachmentService,
        protected FindOrganizationUnitAttachmentsServiceInterface $findAttachmentsService,
        protected FileStorageServiceInterface $storage,
    ) {
    }

    public function index(int $organizationUnitId, ListOrganizationUnitAttachmentRequest $request): OrganizationUnitAttachmentCollection
    {
        $organizationUnit = $this->findOrganizationUnitOrFail($organizationUnitId);
        $this->authorize('view', $organizationUnit);

        $validated = $request->validated();
        $type = $validated['type'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);
        $attachments = $this->findAttachmentsService->paginateByOrganizationUnit(
            $organizationUnitId,
            is_string($type) ? $type : null,
            $perPage,
            $page
        );

        return new OrganizationUnitAttachmentCollection($attachments);
    }

    public function store(UploadOrganizationUnitAttachmentRequest $request, int $organizationUnitId): OrganizationUnitAttachmentResource
    {
        $organizationUnit = $this->findOrganizationUnitOrFail($organizationUnitId);
        $this->authorize('update', $organizationUnit);

        $file = $request->file('file');
        if ($file === null) {
            throw ValidationException::withMessages([
                'file' => ['A file upload is required for this endpoint.'],
            ]);
        }

        $attachment = $this->uploadAttachmentService->execute([
            'org_unit_id' => $organizationUnitId,
            'file' => [
                'tmp_path' => $file->getPathname(),
                'name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ],
            'type' => $request->input('type'),
            'metadata' => $request->input('metadata'),
        ]);

        return new OrganizationUnitAttachmentResource($attachment);
    }

    public function destroy(int $organizationUnitId, int $attachmentId): JsonResponse
    {
        $organizationUnit = $this->findOrganizationUnitOrFail($organizationUnitId);
        $this->authorize('update', $organizationUnit);

        $attachmentEntity = $this->findAttachmentOrFail($attachmentId);
        if ($attachmentEntity->getOrganizationUnitId() !== $organizationUnitId) {
            throw new NotFoundHttpException('Attachment not found.');
        }

        $this->deleteAttachmentService->execute(['attachment_id' => $attachmentId]);

        return Response::json(['message' => 'Attachment deleted successfully']);
    }

    public function serve(string $uuid): StreamedResponse
    {
        $attachment = $this->findAttachmentsService->findByUuid($uuid);
        if (! $attachment) {
            throw new NotFoundHttpException('Attachment not found.');
        }
        $this->authorize('view', $attachment);

        return $this->storage->stream($attachment->getFilePath());
    }

    private function findOrganizationUnitOrFail(int $organizationUnitId): OrganizationUnit
    {
        $organizationUnit = $this->findOrganizationUnitService->find($organizationUnitId);
        if (! $organizationUnit) {
            throw new NotFoundHttpException('Organization unit not found.');
        }

        return $organizationUnit;
    }

    private function findAttachmentOrFail(int $attachmentId): OrganizationUnitAttachment
    {
        $attachment = $this->findAttachmentsService->find($attachmentId);
        if (! $attachment) {
            throw new NotFoundHttpException('Attachment not found.');
        }

        return $attachment;
    }
}
