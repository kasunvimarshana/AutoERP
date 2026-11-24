<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\DTOs;

use Modules\Hr\Enums\PunchSource;
use Modules\Hr\Enums\PunchType;

final readonly class PunchData
{
    public function __construct(
        public int $employeeId,
        public PunchType $punchType,
        public ?string $punchTime = null,
        public PunchSource $punchSource = PunchSource::Web,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
        public ?string $latitude = null,
        public ?string $longitude = null,
        public ?string $notes = null,
        public ?int $organizationUnitId = null,
    ) {}
}
