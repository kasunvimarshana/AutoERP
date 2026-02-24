<?php

namespace Modules\Reporting\Domain\Events;

class ReportSaved
{
    public function __construct(
        public readonly string $reportId,
        public readonly string $tenantId,
        public readonly string $name,
    ) {}
}
