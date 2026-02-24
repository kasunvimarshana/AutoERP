<?php

namespace Modules\Communication\Domain\Events;

class ChannelCreated
{
    public function __construct(
        public readonly string $channelId,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly string $type,
    ) {}
}
