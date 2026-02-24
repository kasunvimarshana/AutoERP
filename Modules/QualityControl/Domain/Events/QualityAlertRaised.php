<?php

namespace Modules\QualityControl\Domain\Events;

class QualityAlertRaised
{
    public function __construct(
        public readonly string $alertId,
        public readonly string $tenantId,
    ) {}
}
