<?php

declare(strict_types=1);

namespace Modules\HR\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HR\Entities\PerformanceReview;

/**
 * Performance Review Completed Event
 *
 * Fired when a performance review is completed.
 */
class PerformanceReviewCompleted
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public PerformanceReview $review
    ) {}
}
