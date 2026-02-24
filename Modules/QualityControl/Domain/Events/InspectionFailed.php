<?php

namespace Modules\QualityControl\Domain\Events;

class InspectionFailed
{
    public function __construct(
        public readonly string $inspectionId,
        public readonly string $tenantId,
        public readonly string $title = '',
        public readonly string $productId = '',
        public readonly string $priority = 'medium',
        public readonly string $equipmentId = '',
    ) {}
}
