<?php

namespace Modules\Recruitment\Domain\Events;

class ApplicantRejected
{
    public function __construct(
        public readonly string $applicationId,
        public readonly string $tenantId,
        public readonly string $positionId,
        public readonly string $reviewerId,
    ) {}
}
