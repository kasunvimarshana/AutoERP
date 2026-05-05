<?php

declare(strict_types=1);

namespace Modules\Service\Domain\Events;

use Modules\Service\Domain\Entities\ServiceJobCard;

class ServiceJobCardCompleted
{
    public function __construct(
        public readonly ServiceJobCard $jobCard,
    ) {}
}
