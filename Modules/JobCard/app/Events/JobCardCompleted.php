<?php

declare(strict_types=1);

namespace Modules\JobCard\Events;

use App\Core\Events\BaseDomainEvent;
use Modules\Invoice\Models\Invoice;
use Modules\JobCard\Models\JobCard;

/**
 * Job Card Completed Event
 *
 * Dispatched when a job card is marked as completed . * Triggers:
 * - Invoice generation
 * - Inventory updates
 * - Customer notifications
 * - Service history updates
 * - Analytics/reporting
 */
class JobCardCompleted extends BaseDomainEvent
{
    /**
     * Create a new event instance
     */
    public function __construct(
        public readonly JobCard $jobCard,
        public readonly ?Invoice $invoice = null
    ) {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    public function getEventPayload(): array
    {
        return [
            'job_card_id' => $this->jobCard->id,
            'job_number' => $this->jobCard->job_number,
            'customer_id' => $this->jobCard->customer_id,
            'vehicle_id' => $this->jobCard->vehicle_id,
            'grand_total' => $this->jobCard->grand_total,
            'invoice_id' => $this->invoice?->id,
            'completed_at' => $this->jobCard->completed_at?->toIso8601String(),
        ];
    }

    /**
     * Determine if this event should be broadcast
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new \Illuminate\Broadcasting\PrivateChannel("customer . {$this->jobCard->customer_id}"),
            new \Illuminate\Broadcasting\PrivateChannel("vehicle . {$this->jobCard->vehicle_id}"),
        ];
    }
}
