<?php

declare(strict_types=1);

namespace Modules\Driver\Application\DTOs;

use Modules\Driver\Domain\ValueObjects\CompensationType;

class UpdateDriverDTO
{
    public function __construct(
        public readonly string $fullName,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $address,
        public readonly CompensationType $compensationType,
        public readonly string $perTripRate,
        public readonly string $commissionPct,
        public readonly ?array $metadata,
        public readonly bool $isActive,
    ) {}
}
