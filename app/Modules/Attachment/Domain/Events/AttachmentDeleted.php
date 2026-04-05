<?php

declare(strict_types=1);

namespace Modules\Attachment\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class AttachmentDeleted extends BaseEvent
{
    public int $attachmentId;
    public string $attachableType;
    public int $attachableId;

    public function __construct(int $tenantId, int $attachmentId, string $attachableType, int $attachableId)
    {
        parent::__construct($tenantId);
        $this->attachmentId = $attachmentId;
        $this->attachableType = $attachableType;
        $this->attachableId = $attachableId;
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'attachmentId'   => $this->attachmentId,
            'attachableType' => $this->attachableType,
            'attachableId'   => $this->attachableId,
        ]);
    }
}
