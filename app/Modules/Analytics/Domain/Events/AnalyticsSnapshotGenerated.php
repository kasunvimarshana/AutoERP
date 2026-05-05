<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Events;

use Modules\Analytics\Domain\Entities\AnalyticsSnapshot;

readonly class AnalyticsSnapshotGenerated
{
    public function __construct(
        public AnalyticsSnapshot $snapshot,
    ) {}
}
