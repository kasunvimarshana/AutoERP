<?php

namespace Modules\Recruitment\Domain\Events;

class JobApplicationReceived
{
    public function __construct(
        public readonly string $applicationId,
        public readonly string $tenantId,
        public readonly string $positionId,
        public readonly string $candidateName,
    ) {}
}
