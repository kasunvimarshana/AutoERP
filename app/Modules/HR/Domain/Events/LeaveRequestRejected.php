<?php
declare(strict_types=1);
namespace Modules\HR\Domain\Events;

class LeaveRequestRejected
{
    public function __construct(public readonly int $leaveRequestId, public readonly int $approverId) {}
}
