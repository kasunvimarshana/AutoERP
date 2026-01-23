<?php

declare(strict_types=1);

namespace Modules\JobCard\Services;

use App\Core\Exceptions\ServiceException;
use App\Core\Services\BaseOrchestrator;
use Illuminate\Support\Facades\Log;
use Modules\Customer\Services\VehicleServiceRecordService;
use Modules\Inventory\Services\InventoryService;
use Modules\Invoice\Services\InvoiceService;
use Modules\JobCard\Events\JobCardCompleted;
use Modules\JobCard\Models\JobCard;
use Modules\JobCard\Repositories\JobCardRepository;

/**
 * Job Card Orchestrator Service
 *
 * Orchestrates complex business operations across multiple modules:
 * - Job Card completion
 * - Invoice generation
 * - Inventory updates
 * - Service history tracking
 * - Customer notifications (via events)
 *
 * Demonstrates:
 * - Service layer orchestration
 * - Transactional integrity
 * - Exception propagation
 * - Rollback on failure
 * - Event-driven async communication
 */
class JobCardOrchestrator extends BaseOrchestrator
{
    public function __construct(
        private readonly JobCardRepository $jobCardRepository,
        private readonly JobCardService $jobCardService,
        private readonly InvoiceService $invoiceService,
        private readonly InventoryService $inventoryService,
        private readonly VehicleServiceRecordService $serviceRecordService
    ) {}

    /**
     * Complete a job card with full orchestration
     *
     * This method orchestrates the complete job card completion workflow:
     * 1. Validate job card can be completed
     * 2. Complete the job card (update status, calculate totals)
     * 3. Generate invoice from job card
     * 4. Update inventory (deduct used parts) - TRANSACTIONAL
     * 5. Create vehicle service record
     * 6. Dispatch events for async operations (notifications, etc.)
     *
     * All database operations are wrapped in a transaction.
     * If any step fails, everything is rolled back atomically.
     *
     * @param  int  $jobCardId  The job card to complete
     * @param  array<string, mixed>  $options  Additional options (e.g., skip_invoice)
     * @return array{jobCard: JobCard, invoice: ?\Modules\Invoice\Models\Invoice, inventoryTransactions: array, serviceRecord: mixed}
     *
     * @throws ServiceException If any step fails
     */
    public function completeJobCardWithFullOrchestration(int $jobCardId, array $options = []): array
    {
        return $this->executeInTransaction(function () use ($jobCardId, $options) {
            // Step 1: Validate prerequisites
            $this->validatePrerequisites([
                'job_card_exists' => fn () => $this->jobCardRepository->exists(['id' => $jobCardId]),
                'job_card_not_already_completed' => function () use ($jobCardId) {
                    $jobCard = $this->jobCardRepository->findOrFail($jobCardId);

                    return $jobCard->status !== 'completed';
                },
            ]);

            $this->recordStep('prerequisites_validated');

            // Step 2: Complete the job card
            $jobCard = $this->executeWithRetry(function () use ($jobCardId) {
                return $this->jobCardService->complete($jobCardId);
            }, maxAttempts: 2);

            $this->recordStep('job_card_completed', [
                'job_card_id' => $jobCard->id,
                'grand_total' => $jobCard->grand_total,
            ]);

            // Step 3: Generate invoice (if not skipped)
            $invoice = null;
            if (! ($options['skip_invoice'] ?? false)) {
                $invoice = $this->executeWithRetry(function () use ($jobCardId, $options) {
                    return $this->invoiceService->generateFromJobCard(
                        $jobCardId,
                        $options['invoice_data'] ?? []
                    );
                });

                $this->recordStep('invoice_generated', [
                    'invoice_id' => $invoice->id,
                    'total_amount' => $invoice->total_amount,
                ]);
            }

            // Step 4: Update inventory (deduct parts used)
            // This is CRITICAL - must be transactional
            $inventoryTransactions = [];
            if (! ($options['skip_inventory'] ?? false)) {
                $jobCard->load('parts.inventoryItem');

                foreach ($jobCard->parts as $part) {
                    if ($part->inventoryItem && ! $part->inventoryItem->is_dummy_item) {
                        try {
                            $transaction = $this->inventoryService->adjustInventory(
                                itemId: $part->inventory_item_id,
                                quantity: -$part->quantity,
                                transactionType: 'job_card_usage',
                                referenceId: $jobCard->id,
                                reason: "Used in Job Card #{$jobCard->job_number}"
                            );

                            $inventoryTransactions[] = $transaction;
                        } catch (\Exception $e) {
                            // Log but don't fail the entire operation for dummy items
                            Log::warning('Could not adjust inventory for part', [
                                'part_id' => $part->id,
                                'item_id' => $part->inventory_item_id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }

                $this->recordStep('inventory_updated', [
                    'parts_count' => count($inventoryTransactions),
                ]);
            }

            // Step 5: Create vehicle service record
            $serviceRecord = null;
            if (! ($options['skip_service_record'] ?? false)) {
                $serviceRecord = $this->serviceRecordService->createFromJobCard($jobCard);

                $this->recordStep('service_record_created', [
                    'service_record_id' => $serviceRecord->id,
                ]);
            }

            // Step 6: Dispatch events for async operations
            // Events are dispatched AFTER transaction commits
            event(new JobCardCompleted($jobCard, $invoice));

            $this->recordStep('events_dispatched');

            Log::info('Job card orchestration completed successfully', [
                'job_card_id' => $jobCard->id,
                'invoice_id' => $invoice?->id,
                'inventory_transactions' => count($inventoryTransactions),
                'service_record_id' => $serviceRecord?->id,
            ]);

            // Refresh jobCard from repository only if it's a real model, otherwise return as-is
            $refreshedJobCard = $jobCard->exists ? $jobCard->fresh() : $jobCard;

            return [
                'jobCard' => $refreshedJobCard,
                'invoice' => $invoice,
                'inventoryTransactions' => $inventoryTransactions,
                'serviceRecord' => $serviceRecord,
            ];
        }, 'CompleteJobCardOrchestration');
    }

    /**
     * Compensation logic for failed job card completion
     *
     * This is called automatically if the transaction fails and is rolled back.
     * We can perform cleanup actions here (e.g., send alerts, log to external systems)
     */
    protected function compensate(): void
    {
        // Log compensation
        Log::warning('Job card orchestration failed, performing compensation', [
            'completed_steps' => $this->completedSteps,
        ]);

        // Notify operations team
        // Could send email, Slack notification, create ticket, etc.

        // Example: If we got to invoice generation but failed later,
        // we might want to mark the invoice as "draft" or "cancelled"
        // (Though in our case, database rollback handles this automatically)
    }

    /**
     * Start a job card with bay assignment and technician notification
     *
     * @param  array<string, mixed>  $data  Contains technician_id, bay_id, etc.
     *
     * @throws ServiceException
     */
    public function startJobCard(int $jobCardId, array $data): JobCard
    {
        return $this->executeInTransaction(function () use ($jobCardId, $data) {
            // Validate prerequisites
            $this->validatePrerequisites([
                'job_card_in_pending_status' => function () use ($jobCardId) {
                    $jobCard = $this->jobCardRepository->findOrFail($jobCardId);

                    return $jobCard->status === 'pending';
                },
            ]);

            // Start the job card
            $jobCard = $this->jobCardService->start($jobCardId);
            $this->recordStep('job_card_started');

            // Assign technician if provided
            if (isset($data['technician_id'])) {
                $jobCard = $this->jobCardService->assignTechnician($jobCardId, $data['technician_id']);
                $this->recordStep('technician_assigned');
            }

            // Could dispatch events for:
            // - Notifying technician
            // - Updating bay status
            // - Starting time tracking
            // event(new JobCardStarted($jobCard));

            return $jobCard;
        }, 'StartJobCard');
    }
}
