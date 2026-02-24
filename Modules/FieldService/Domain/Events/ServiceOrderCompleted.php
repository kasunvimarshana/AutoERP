<?php

namespace Modules\FieldService\Domain\Events;

class ServiceOrderCompleted
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $tenantId,
    ) {}
}
