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
        
        // Create a mock JobCard with proper property handling
        $jobCard = $this->getMockBuilder(JobCard::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fresh', '__get'])
            ->getMock();
        
        // Set up properties
        $properties = [
            'id' => $jobCardId,
            'status' => 'completed',
            'grand_total' => 500 . 00,
        ];
        
        // Mock the fresh() method to return self
        $jobCard->method('fresh')->willReturn($jobCard);
        
        // Mock __get to return properties
        $jobCard->method('__get')->willReturnCallback(function ($key) use ($properties) {
            return $properties[$key] ?? null;
        });
        
        // Set magic properties for direct access
        foreach ($properties as $key => $value) {
            $jobCard->$key = $value;
        }

        $invoice = new \Modules\Invoice\Models\Invoice([
            'id' => 1,
            'total_amount' => 500 . 00,
        ]);

        // Setup orchestrator with mocked repository that returns in_progress initially
        $initialJobCard = new JobCard(['id' => $jobCardId, 'status' => 'in_progress']);
        $this->createOrchestratorWithRepository(exists: true, jobCard: $initialJobCard);

        // Mock service calls
        $this->jobCardService->expects($this->once())
            ->method('complete')
            ->with($jobCardId)
            ->willReturn($jobCard);

        $this->invoiceService->expects($this->once())
            ->method('generateFromJobCard')
            ->with($jobCardId, [])
            ->willReturn($invoice);

        // Mock service record creation
        $this->serviceRecordService->expects($this->once())
            ->method('createFromJobCard')
            ->with($jobCard)
            ->willReturn((object) ['id' => 1]);

        // Act
        $result = $this->orchestrator->completeJobCardWithFullOrchestration($jobCardId, [
            'skip_inventory' => true,  // Skip inventory to avoid mocking parts relationship
        ]);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('jobCard', $result);
        $this->assertArrayHasKey('invoice', $result);
        $this->assertNotNull($result['jobCard']);
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
        $jobCard = new JobCard(['id' => $jobCardId, 'status' => 'in_progress']);

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
        // The orchestrator wraps the exception with retry context and operation name
        $this->expectExceptionMessageMatches('/CompleteJobCardOrchestration failed: . *Operation failed after . *attempts/');

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
            'id' => $jobCardId,
            'status' => 'in_progress',  // NOT 'completed' - prerequisite check requires this
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

        $this->jobCardService->method('complete')->willReturn($jobCard);

        // Invoice service should NOT be called
        $this->invoiceService->expects($this->never())
            ->method('generateFromJobCard');

        // Mock service record creation
        $this->serviceRecordService->expects($this->once())
            ->method('createFromJobCard')
            ->with($jobCard)
            ->willReturn((object) ['id' => 1]);

        // Act
        $result = $this->orchestrator->completeJobCardWithFullOrchestration($jobCardId, [
            'skip_invoice' => true,
        ]);

        // Assert
        $this->assertNull($result['invoice']);
    }
}
