<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum IncidentReviewAction: string
{
    case Confirm = 'confirm';
    case Reject = 'reject';

    public function status(): IncidentStatus
    {
        return match ($this) {
            self::Confirm => IncidentStatus::Confirmed,
            self::Reject => IncidentStatus::Rejected,
        };
    }
}
