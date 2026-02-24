<?php

namespace Modules\Leave\Domain\Events;

class LeaveRequestRejected
{
    public function __construct(
        public readonly string $leaveRequestId,
        public readonly string $tenantId,
        public readonly string $reviewerId,
    ) {}
}
