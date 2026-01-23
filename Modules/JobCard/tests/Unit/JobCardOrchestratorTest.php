<?php

declare(strict_types=1);

namespace Modules\JobCard\Tests\Unit;

use App\Core\Exceptions\ServiceException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Customer\Services\VehicleServiceRecordService;
use Modules\Inventory\Services\InventoryService;
use Modules\Invoice\Services\InvoiceService;
use Modules\JobCard\Models\JobCard;
use Modules\JobCard\Repositories\JobCardRepository;
use Modules\JobCard\Services\JobCardOrchestrator;
use Modules\JobCard\Services\JobCardService;
use Tests\TestCase;

/**
 * Job Card Orchestrator Unit Tests
 *
 * Tests the orchestration logic in isolation using mocks
 */
class JobCardOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    private JobCardOrchestrator $orchestrator;

    private JobCardService $jobCardService;

    private InvoiceService $invoiceService;

    private InventoryService $inventoryService;

    private VehicleServiceRecordService $serviceRecordService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->jobCardService = $this->createMock(JobCardService::class);
        $this->invoiceService = $this->createMock(InvoiceService::class);
        $this->inventoryService = $this->createMock(InventoryService::class);
        $this->serviceRecordService = $this->createMock(VehicleServiceRecordService::class);

        $repository = $this->createMock(JobCardRepository::class);

        // Create orchestrator with mocked dependencies
        $this->orchestrator = new JobCardOrchestrator(
            $repository,
            $this->jobCardService,
            $this->invoiceService,
            $this->inventoryService,
            $this->serviceRecordService
        );
    }

    /**
     * Helper method to create orchestrator with properly configured repository mock
     *
     * @param  bool  $exists  Whether the job card exists
     * @param  JobCard|null  $jobCard  The job card to return from findOrFail
     */
    private function createOrchestratorWithRepository(bool $exists = true, ?JobCard $jobCard = null): void
    {
        $repository = $this->createMock(JobCardRepository::class);
        $repository->method('exists')->willReturn($exists);

        if ($jobCard !== null) {
            $repository->method('findOrFail')->willReturn($jobCard);
        }

        $this->orchestrator = new JobCardOrchestrator(
            $repository,
            $this->jobCardService,
            $this->invoiceService,
            $this->inventoryService,
            $this->serviceRecordService
        );
    }

    /**
     * Test successful job card completion with all steps
     */
    public function test_completes_job_card_with_full_orchestration(): void
    {
        // Arrange
        $jobCardId = 1;
        $jobCard = new JobCard([
            'status' => 'in_progress',  // NOT 'completed' - prerequisite check requires this
            'grand_total' => 500.00,
        ]);
        $jobCard->id = $jobCardId;

        // Create completed job card for return value
        $completedJobCard = new JobCard([
            'status' => 'completed',
            'grand_total' => 500.00,
        ]);
        $completedJobCard->id = $jobCardId;
        $completedJobCard->exists = false; // No database record, don't refresh

        $invoice = new \Modules\Invoice\Models\Invoice([
            'id' => 1,
            'total_amount' => 500.00,
        ]);

        $serviceRecord = new \Modules\Customer\Models\VehicleServiceRecord([
            'id' => 1,
            'service_number' => 'SR-2024-001',
        ]);

        // Setup orchestrator with mocked repository
        $this->createOrchestratorWithRepository(exists: true, jobCard: $jobCard);

        // Mock service calls
        $this->jobCardService->expects($this->once())
            ->method('complete')
            ->with($jobCardId)
            ->willReturn($completedJobCard);

        $this->invoiceService->expects($this->once())
            ->method('generateFromJobCard')
            ->with($jobCardId, [])
            ->willReturn($invoice);

        $this->serviceRecordService->expects($this->once())
            ->method('createFromJobCard')
            ->with($completedJobCard)
            ->willReturn($serviceRecord);

        // Act
        $result = $this->orchestrator->completeJobCardWithFullOrchestration($jobCardId);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('jobCard', $result);
        $this->assertArrayHasKey('invoice', $result);
        $this->assertNotNull($result['jobCard'], 'Job card should not be null. Result: '.json_encode($result));
        $this->assertEquals($jobCardId, $result['jobCard']->id);
        $this->assertEquals('completed', $result['jobCard']->status);
    }

    /**
     * Test orchestrator rolls back on service failure
     */
    public function test_rolls_back_on_invoice_generation_failure(): void
    {
        // Arrange
        $jobCardId = 1;
        $jobCard = new JobCard(['status' => 'in_progress']);
        $jobCard->id = $jobCardId;

        // Setup orchestrator with mocked repository
        $this->createOrchestratorWithRepository(exists: true, jobCard: $jobCard);

        // Job card completes successfully
        $this->jobCardService->method('complete')
            ->willReturn($jobCard);

        // But invoice generation fails
        $this->invoiceService->method('generateFromJobCard')
            ->willThrowException(new ServiceException('Invoice generation failed'));

        // Act & Assert
        $this->expectException(ServiceException::class);
        // The orchestrator wraps the exception with retry context
        $this->expectExceptionMessageMatches('/CompleteJobCardOrchestration failed:.*Invoice generation failed/');

        $this->orchestrator->completeJobCardWithFullOrchestration($jobCardId);

        // Verify transaction was rolled back (in real test with database)
        // Job card status should still be 'in_progress'
        // Invoice should not exist
    }

    /**
     * Test prerequisite validation failure
     */
    public function test_fails_when_job_card_does_not_exist(): void
    {
        // Arrange
        $jobCardId = 999;

        $repository = $this->createMock(JobCardRepository::class);
        $repository->method('exists')->willReturn(false);

        // Recreate orchestrator with mock
        $this->orchestrator = new JobCardOrchestrator(
            $repository,
            $this->jobCardService,
            $this->invoiceService,
            $this->inventoryService,
            $this->serviceRecordService
        );

        // Act & Assert
        $this->expectException(ServiceException::class);
        $this->orchestrator->completeJobCardWithFullOrchestration($jobCardId);
    }

    /**
     * Test orchestrator with skip options
     */
    public function test_skips_invoice_when_option_provided(): void
    {
        // Arrange
        $jobCardId = 1;
        $jobCard = new JobCard([
            'status' => 'in_progress',  // NOT 'completed' - prerequisite check requires this
        ]);
        $jobCard->id = $jobCardId;

        // Create completed job card for return value
        $completedJobCard = new JobCard([
            'status' => 'completed',
        ]);
        $completedJobCard->id = $jobCardId;
        $completedJobCard->exists = false; // No database record

        $serviceRecord = new \Modules\Customer\Models\VehicleServiceRecord([
            'id' => 1,
            'service_number' => 'SR-2024-001',
        ]);

        $repository = $this->createMock(JobCardRepository::class);
        $repository->method('exists')->willReturn(true);
        $repository->method('findOrFail')->willReturn($jobCard);

        $this->orchestrator = new JobCardOrchestrator(
            $repository,
            $this->jobCardService,
            $this->invoiceService,
            $this->inventoryService,
            $this->serviceRecordService
        );

        $this->jobCardService->method('complete')->willReturn($completedJobCard);

        // Mock service record creation
        $this->serviceRecordService->expects($this->once())
            ->method('createFromJobCard')
            ->with($completedJobCard)
            ->willReturn($serviceRecord);

        // Invoice service should NOT be called
        $this->invoiceService->expects($this->never())
            ->method('generateFromJobCard');

        // Act
        $result = $this->orchestrator->completeJobCardWithFullOrchestration($jobCardId, [
            'skip_invoice' => true,
        ]);

        // Assert
        $this->assertNull($result['invoice']);
    }
}
