<?php

namespace Modules\Integration\Domain\Events;

class WebhookCreated
{
    public function __construct(
        public readonly string $webhookId,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly string $url,
    ) {}
}
