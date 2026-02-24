<?php

namespace Modules\Contracts\Domain\Events;

class ContractCreated
{
    public function __construct(
        public readonly string $contractId,
        public readonly string $tenantId,
        public readonly string $title,
    ) {}
}
