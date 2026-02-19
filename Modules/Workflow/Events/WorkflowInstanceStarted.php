<?php

declare(strict_types=1);

namespace Modules\Workflow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Workflow\Models\WorkflowInstance;

class WorkflowInstanceStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WorkflowInstance $instance
    ) {}
}
