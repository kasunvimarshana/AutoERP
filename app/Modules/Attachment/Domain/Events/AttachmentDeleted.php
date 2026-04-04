<?php

namespace Modules\Attachment\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class AttachmentDeleted extends BaseEvent
{
    public function __construct(
        int $tenantId,
        public readonly int $attachmentId,
    ) {
        parent::__construct($tenantId);
    }
}
