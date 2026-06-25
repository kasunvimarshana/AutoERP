<?php

declare(strict_types=1);

namespace Modules\Audit\Data;

final readonly class AuditReadScope
{
    public function __construct(
        public ?int $tenantId,
        public ?int $organizationUnitId,
        public bool $tenantWide,
        public bool $platformWide = false,
    ) {}

    public static function forPlatform(?int $tenantId = null): self
    {
        return new self($tenantId, null, true, true);
    }
}
