<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class AttachmentNotFoundException extends NotFoundException
{
    public function __construct(mixed $id = null)
    {
        parent::__construct('Attachment', $id);
    }
}
