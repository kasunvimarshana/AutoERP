<?php

declare(strict_types=1);

namespace Modules\Reporting\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Reporting\Models\Report;

class ReportPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Report $report
    ) {}
}
