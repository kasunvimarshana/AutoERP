<?php

namespace Modules\Attachment\Application\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Storage;
use Modules\Attachment\Application\Contracts\DeleteAttachmentServiceInterface;
use Modules\Attachment\Domain\Entities\Attachment;
use Modules\Attachment\Domain\Events\AttachmentDeleted;
use Modules\Attachment\Domain\RepositoryInterfaces\AttachmentRepositoryInterface;

class DeleteAttachmentService implements DeleteAttachmentServiceInterface
{
    public function __construct(
        private readonly AttachmentRepositoryInterface $repo,
        private readonly Dispatcher $dispatcher,
    ) {}

    public function execute(Attachment $attachment): bool
    {
        try {
            Storage::disk($attachment->disk)->delete($attachment->path);
        } catch (\Throwable $e) {
            // Log but continue — the DB record should still be removed
            logger()->warning('Attachment file could not be deleted from storage', [
                'attachment_id' => $attachment->id,
                'path'          => $attachment->path,
                'error'         => $e->getMessage(),
            ]);
        }

        $deleted = $this->repo->delete($attachment);

        $this->dispatcher->dispatch(new AttachmentDeleted($attachment->tenantId, $attachment->id));

        return $deleted;
    }
}
