<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Exceptions;

use RuntimeException;

class AnalyticsSnapshotNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct(sprintf('Analytics snapshot not found for id: %d', $id));
    }
}
