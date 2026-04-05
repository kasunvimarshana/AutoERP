<?php

declare(strict_types=1);

namespace Modules\Attachment\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Attachment\Domain\Entities\Attachment;

interface GetAttachmentsServiceInterface
{
    public function findById(int $id): Attachment;
    public function findByAttachable(string $type, int $id): Collection;
}
