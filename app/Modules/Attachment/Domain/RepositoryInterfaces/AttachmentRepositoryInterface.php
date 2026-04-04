<?php

namespace Modules\Attachment\Domain\RepositoryInterfaces;

use Modules\Attachment\Domain\Entities\Attachment;

interface AttachmentRepositoryInterface
{
    public function findById(int $id): ?Attachment;

    public function findByAttachable(string $type, int $id): array;

    public function create(array $data): Attachment;

    public function delete(Attachment $attachment): bool;
}
