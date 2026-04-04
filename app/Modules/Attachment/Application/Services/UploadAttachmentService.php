<?php

namespace Modules\Attachment\Application\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Attachment\Application\Contracts\UploadAttachmentServiceInterface;
use Modules\Attachment\Application\DTOs\UploadAttachmentData;
use Modules\Attachment\Domain\Entities\Attachment;
use Modules\Attachment\Domain\Events\AttachmentUploaded;
use Modules\Attachment\Domain\RepositoryInterfaces\AttachmentRepositoryInterface;

class UploadAttachmentService implements UploadAttachmentServiceInterface
{
    public function __construct(
        private readonly AttachmentRepositoryInterface $repo,
        private readonly Dispatcher $dispatcher,
    ) {}

    public function execute(UploadAttachmentData $data): Attachment
    {
        $attachment = $this->repo->create([
            'tenant_id'       => $data->tenantId,
            'attachable_type' => $data->attachableType,
            'attachable_id'   => $data->attachableId,
            'disk'            => $data->disk,
            'path'            => $data->path,
            'original_name'   => $data->originalName,
            'mime_type'       => $data->mimeType,
            'size'            => $data->size,
            'label'           => $data->label,
            'uploaded_by'     => $data->uploadedBy,
        ]);

        $this->dispatcher->dispatch(new AttachmentUploaded($data->tenantId, $attachment->id));

        return $attachment;
    }
}
