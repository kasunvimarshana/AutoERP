<?php

declare(strict_types=1);

namespace Modules\Inventory\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Services\InventoryService;
use Modules\JobCard\Events\JobCardCompleted;

/**
 * Update Inventory from Job Card
 *
 * Listens to JobCardCompleted event and deducts parts from inventory
 * Runs asynchronously in the queue
 */
class UpdateInventoryFromJobCard implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the listener may be attempted
     */
    public int $tries = 3;

    /**
     * Create the event listener
     */
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {
    }

    /**
     * Handle the event
     */
    public function handle(JobCardCompleted $event): void
    {
        try {
            // Load job card with parts
            $jobCard = $event->jobCard->load('parts');

            if ($jobCard->parts->isEmpty()) {
                Log::info('No parts to deduct for completed job card', [
                    'job_card_id' => $jobCard->id,
                ]);

                return;
            }

            // Deduct each part from inventory
            foreach ($jobCard->parts as $part) {
                $this->inventoryService->adjustInventory(
                    itemId: $part->inventory_item_id,
                    quantity: -$part->quantity,
                    transactionType: 'job_card_usage',
                    referenceId: $jobCard->id,
                    reason: "Used in Job Card #{$jobCard->job_number}"
                );

                Log::info('Inventory adjusted for job card part', [
                    'job_card_id' => $jobCard->id,
                    'item_id' => $part->inventory_item_id,
                    'quantity' => $part->quantity,
                ]);
            }

            Log::info('Inventory updated for completed job card', [
                'job_card_id' => $jobCard->id,
                'parts_count' => $jobCard->parts->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update inventory from job card', [
                'job_card_id' => $event->jobCard->id,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Rethrow to trigger retry
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(JobCardCompleted $event, \Throwable $exception): void
    {
        Log::error('Failed to update inventory after all retries', [
            'job_card_id' => $event->jobCard->id,
            'error' => $exception->getMessage(),
        ]);

        // Create manual reconciliation task
        // Notify inventory manager
    }
}
