<?php

declare(strict_types=1);

namespace Modules\Accounting\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodReopened
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public FiscalPeriod $fiscalPeriod
    ) {}
}
