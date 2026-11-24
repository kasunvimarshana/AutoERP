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

use Modules\Hr\Enums\EmployeeAvailabilityStatus;

final readonly class EmployeeAvailabilityData
{
    public function __construct(public EmployeeAvailabilityStatus $availabilityStatus, public ?string $availabilityDate = null, public ?string $sourceType = null, public ?int $sourceId = null, public ?string $reason = null, public ?string $startsAt = null, public ?string $endsAt = null) {}
}
