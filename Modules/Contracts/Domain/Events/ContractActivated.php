<?php

namespace Modules\Contracts\Domain\Events;

class ContractActivated
{
    public function __construct(
        public readonly string $contractId,
        public readonly string $tenantId,
    ) {}
}
