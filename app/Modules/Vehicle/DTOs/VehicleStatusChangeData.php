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

use Modules\Vehicle\Enums\VehicleStatus;

final readonly class VehicleStatusChangeData
{
    public function __construct(
        public VehicleStatus $newStatus,
        public ?string $reason = null,
        public ?int $changedBy = null,
    ) {}
}
