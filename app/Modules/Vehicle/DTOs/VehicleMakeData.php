<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Vehicle\DTOs;

final readonly class VehicleMakeData
{
    public function __construct(
        public int $tenantId,
        public string $code,
        public string $name,
        public ?int $organizationUnitId = null,
        public ?string $description = null,
        public bool $isActive = true,
    ) {}
}
