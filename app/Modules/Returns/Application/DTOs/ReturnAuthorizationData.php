<?php

namespace Modules\Returns\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class ReturnAuthorizationData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $stockReturnId,
        public readonly string $rmaNumber,
        public readonly ?string $expiresAt = null,
        public readonly ?string $notes = null,
    ) {}
}
