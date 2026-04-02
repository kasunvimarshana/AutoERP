<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\HR\Domain\Entities\PerformanceReview;

class PerformanceReviewUpdated extends BaseEvent
{
    public function __construct(public readonly PerformanceReview $performanceReview)
    {
        parent::__construct($performanceReview->getTenantId(), $performanceReview->getId());
    }
}
