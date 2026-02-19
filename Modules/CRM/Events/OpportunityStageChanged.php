<?php

declare(strict_types=1);

namespace Modules\CRM\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\CRM\Models\Opportunity;

class OpportunityStageChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Opportunity $opportunity,
        public string $oldStage,
        public string $newStage
    ) {}
}
