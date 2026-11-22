<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Vehicle\Data;

final readonly class VersionedVehicleOwnershipCommand
{
    public function __construct(
        public int $expectedVersion,
        public ?string $notes = null,
        public ?string $endedAt = null,
    ) {}
}
