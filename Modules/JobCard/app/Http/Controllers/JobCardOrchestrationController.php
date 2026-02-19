<?php

declare(strict_types=1);

namespace Modules\JobCard\Http\Controllers;

use App\Core\Exceptions\ServiceException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\JobCard\Resources\JobCardResource;
use Modules\JobCard\Services\JobCardOrchestrator;

/**
 * JobCard Orchestration Controller
 *
 * Handles complex, cross-module operations for job cards
 * Demonstrates production-ready orchestration patterns
 */
class JobCardOrchestrationController extends Controller
{
    public function __construct(
        private readonly JobCardOrchestrator $orchestrator
    ) {}

    /**
     * Complete a job card with full orchestration
     *
     * This endpoint orchestrates:
     * - Job card completion (status update, totals calculation)
     * - Invoice generation
     * - Inventory updates (part deductions)
     * - Service history tracking
     * - Customer notifications (async via events)
     *
     * All database operations are wrapped in a transaction . * If any step fails, everything is rolled back automatically . *
     *
     * @OA\Post(
     *     path="/api/v1/job-cards/{id}/complete",
     *     tags={"JobCards"},
     *     summary="Complete a job card with full orchestration",
     *     description="Completes job card, generates invoice, updates inventory, and triggers notifications",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Job card ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="skip_invoice", type="boolean", description="Skip invoice generation"),
     *             @OA\Property(property="skip_inventory", type="boolean", description="Skip inventory updates"),
     *             @OA\Property(property="invoice_data", type="object", description="Additional invoice data")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Job card completed successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="job_card", ref="#/components/schemas/JobCard"),
     *                 @OA\Property(property="invoice", ref="#/components/schemas/Invoice"),
     *                 @OA\Property(property="inventory_transactions", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="service_record", type="object")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=400, description="Validation error or business logic failure"),
     *     @OA\Response(response=404, description="Job card not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function complete(int $id, Request $request): JsonResponse
    {
        try {
            // Validate request
            $options = $request->validate([
                'skip_invoice' => 'sometimes|boolean',
                'skip_inventory' => 'sometimes|boolean',
                'skip_service_record' => 'sometimes|boolean',
                'invoice_data' => 'sometimes|array',
                'invoice_data . due_date' => 'sometimes|date',
                'invoice_data . notes' => 'sometimes|string|max:1000',
            ]);

            // Execute orchestrated operation
            $result = $this->orchestrator->completeJobCardWithFullOrchestration($id, $options);

            return $this->successResponse([
                'job_card' => new JobCardResource($result['jobCard']),
                'invoice' => $result['invoice'] ? new \Modules\Invoice\Resources\InvoiceResource($result['invoice']) : null,
                'inventory_transactions_count' => count($result['inventoryTransactions'] ?? []),
                'service_record' => $result['serviceRecord'] ?? null,
                'message' => 'Job card completed successfully . Invoice generated and customer notified . ',
            ]);
        } catch (ServiceException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            \Log::error('Failed to complete job card', [
                'job_card_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('An unexpected error occurred while completing the job card . ', 500);
        }
    }

    /**
     * Start a job card with technician assignment
     *
     * @OA\Post(
     *     path="/api/v1/job-cards/{id}/start",
     *     tags={"JobCards"},
     *     summary="Start a job card",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="technician_id", type="integer", description="Assigned technician"),
     *             @OA\Property(property="bay_id", type="integer", description="Assigned bay")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Job card started successfully")
     * )
     */
    public function start(int $id, Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'technician_id' => 'sometimes|integer|exists:users,id',
                'bay_id' => 'sometimes|integer|exists:bays,id',
            ]);

            $jobCard = $this->orchestrator->startJobCard($id, $data);

            return $this->successResponse(
                new JobCardResource($jobCard),
                'Job card started successfully . Technician has been notified . '
            );
        } catch (ServiceException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            \Log::error('Failed to start job card', [
                'job_card_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('An unexpected error occurred . ', 500);
        }
    }

    /**
     * Get orchestration status for a job card
     *
     * Useful for debugging and monitoring
     *
     * @OA\Get(
     *     path="/api/v1/job-cards/{id}/orchestration-status",
     *     tags={"JobCards"},
     *     summary="Get orchestration status",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Orchestration status",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="job_card_status", type="string"),
     *             @OA\Property(property="has_invoice", type="boolean"),
     *             @OA\Property(property="inventory_updated", type="boolean"),
     *             @OA\Property(property="notifications_sent", type="boolean")
     *         )
     *     )
     * )
     */
    public function getOrchestrationStatus(int $id): JsonResponse
    {
        try {
            // This is a diagnostic endpoint
            $jobCard = \Modules\JobCard\Models\JobCard::with([
                'invoice',
                'parts.inventoryItem',
                'serviceRecords',
            ])->findOrFail($id);

            $status = [
                'job_card' => [
                    'id' => $jobCard->id,
                    'status' => $jobCard->status,
                    'completed_at' => $jobCard->completed_at?->toIso8601String(),
                ],
                'invoice' => [
                    'generated' => $jobCard->invoice !== null,
                    'invoice_id' => $jobCard->invoice?->id,
                    'total_amount' => $jobCard->invoice?->total_amount,
                    'status' => $jobCard->invoice?->status,
                ],
                'inventory' => [
                    'parts_count' => $jobCard->parts->count(),
                    'updated' => $jobCard->status === 'completed', // Simplified check
                ],
                'service_records' => [
                    'count' => $jobCard->serviceRecords->count(),
                    'latest' => $jobCard->serviceRecords->first(),
                ],
                'events' => [
                    'note' => 'Events are processed asynchronously . Check queue workers and logs for details . ',
                ],
            ];

            return $this->successResponse($status);
        } catch (\Exception $e) {
            return $this->errorResponse('Job card not found or error occurred . ', 404);
        }
    }
}
