<?php

declare(strict_types=1);

namespace Modules\VehicleService\Domain\Events;

final readonly class JobCardCompleted
{
    public function __construct(public int $jobCardId)
    {
    }
}
