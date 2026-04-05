<?php

declare(strict_types=1);

namespace Modules\Attachment\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class AttachmentNotFoundException extends NotFoundException
{
    public function __construct(int|string $id)
    {
        parent::__construct("Attachment [{$id}] not found.");
    }
}
