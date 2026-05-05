<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Domain\Events;

use Modules\ServiceCenter\Domain\Entities\ServiceJob;

readonly class ServiceJobCreated
{
    public function __construct(
        public ServiceJob $serviceJob,
    ) {}
}
