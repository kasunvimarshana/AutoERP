<?php

namespace Modules\Recruitment\Domain\Events;

class ApplicantHired
{
    public function __construct(
        public readonly string $applicationId,
        public readonly string $tenantId,
        public readonly string $positionId,
        public readonly string $reviewerId,
        public readonly string $candidateName = '',
        public readonly string $email = '',
        public readonly string $phone = '',
    ) {}
}
