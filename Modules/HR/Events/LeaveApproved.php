<?php

declare(strict_types=1);

namespace Modules\HR\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HR\Entities\Leave;

/**
 * Leave Approved Event
 *
 * Fired when a leave request is approved.
 */
class LeaveApproved
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Leave $leave
    ) {}
}
