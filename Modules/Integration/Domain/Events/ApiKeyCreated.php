<?php

namespace Modules\Integration\Domain\Events;

class ApiKeyCreated
{
    public function __construct(
        public readonly string $apiKeyId,
        public readonly string $tenantId,
        public readonly string $name,
    ) {}
}
