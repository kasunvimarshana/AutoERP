<?php

namespace Modules\QualityControl\Domain\Events;

class InspectionPassed
{
    public function __construct(
        public readonly string $inspectionId,
        public readonly string $tenantId,
    ) {}
}
