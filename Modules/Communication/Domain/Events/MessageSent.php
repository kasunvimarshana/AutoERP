<?php

namespace Modules\Communication\Domain\Events;

class MessageSent
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $tenantId,
        public readonly string $channelId,
        public readonly string $senderId,
    ) {}
}
