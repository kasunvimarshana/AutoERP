<?php

namespace Tests\Unit\Maintenance;

use DomainException;
use Mockery;
use Modules\Maintenance\Application\Listeners\HandleInspectionFailedListener;
use Modules\Maintenance\Application\UseCases\CreateMaintenanceRequestUseCase;
use Modules\QualityControl\Domain\Events\InspectionFailed;
use PHPUnit\Framework\TestCase;

class InspectionFailedMaintenanceRequestListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeEvent(
        string $inspectionId = 'inspection-1',
        string $tenantId     = 'tenant-1',
        string $title        = 'Failed inspection #REF-001',
        string $productId    = 'product-1',
        string $priority     = 'high',
        string $equipmentId  = 'equipment-1',
    ): InspectionFailed {
        return new InspectionFailed(
            inspectionId: $inspectionId,
            tenantId:     $tenantId,
            title:        $title,
            productId:    $productId,
            priority:     $priority,
            equipmentId:  $equipmentId,
        );
    }

    // -------------------------------------------------------------------------
    // Guard: skip when tenantId is empty
    // -------------------------------------------------------------------------

    public function test_skips_when_tenant_id_empty(): void
    {
        $useCase = Mockery::mock(CreateMaintenanceRequestUseCase::class);
        $useCase->shouldNotReceive('execute');

        (new HandleInspectionFailedListener($useCase))->handle($this->makeEvent(tenantId: ''));

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip when equipmentId is empty
    // -------------------------------------------------------------------------

    public function test_skips_when_equipment_id_empty(): void
    {
        $useCase = Mockery::mock(CreateMaintenanceRequestUseCase::class);
        $useCase->shouldNotReceive('execute');

        (new HandleInspectionFailedListener($useCase))->handle($this->makeEvent(equipmentId: ''));

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Maintenance request creation
    // -------------------------------------------------------------------------

    public function test_creates_maintenance_request_with_correct_tenant_id(): void
    {
        $useCase = Mockery::mock(CreateMaintenanceRequestUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(fn ($data) => $data['tenant_id'] === 'tenant-1')
            ->andReturn((object) ['id' => 'req-1']);

        (new HandleInspectionFailedListener($useCase))->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    public function test_creates_maintenance_request_with_correct_equipment_id(): void
    {
        $useCase = Mockery::mock(CreateMaintenanceRequestUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(fn ($data) => $data['equipment_id'] === 'equipment-1')
            ->andReturn((object) ['id' => 'req-1']);

        (new HandleInspectionFailedListener($useCase))->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    public function test_passes_inspection_priority_to_maintenance_request(): void
    {
        $useCase = Mockery::mock(CreateMaintenanceRequestUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(fn ($data) => $data['priority'] === 'high')
            ->andReturn((object) ['id' => 'req-1']);

        (new HandleInspectionFailedListener($useCase))->handle($this->makeEvent(priority: 'high'));

        $this->addToAssertionCount(1);
    }

    public function test_defaults_priority_to_medium_when_empty(): void
    {
        $useCase = Mockery::mock(CreateMaintenanceRequestUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(fn ($data) => $data['priority'] === 'medium')
            ->andReturn((object) ['id' => 'req-1']);

        (new HandleInspectionFailedListener($useCase))->handle($this->makeEvent(priority: ''));

        $this->addToAssertionCount(1);
    }

    public function test_description_includes_inspection_title(): void
    {
        $useCase = Mockery::mock(CreateMaintenanceRequestUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(fn ($data) =>
                str_contains($data['description'], 'Failed inspection #REF-001')
            )
            ->andReturn((object) ['id' => 'req-1']);

        (new HandleInspectionFailedListener($useCase))->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    public function test_description_includes_product_id_when_provided(): void
    {
        $useCase = Mockery::mock(CreateMaintenanceRequestUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(fn ($data) =>
                str_contains($data['description'], 'product-1')
            )
            ->andReturn((object) ['id' => 'req-1']);

        (new HandleInspectionFailedListener($useCase))->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    public function test_description_works_without_title_and_product_id(): void
    {
        $useCase = Mockery::mock(CreateMaintenanceRequestUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(fn ($data) =>
                str_contains($data['description'], 'Quality inspection failure')
            )
            ->andReturn((object) ['id' => 'req-1']);

        (new HandleInspectionFailedListener($useCase))->handle(
            $this->makeEvent(title: '', productId: '')
        );

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Graceful degradation
    // -------------------------------------------------------------------------

    public function test_graceful_degradation_on_domain_exception(): void
    {
        $useCase = Mockery::mock(CreateMaintenanceRequestUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->andThrow(new DomainException('Equipment not found.'));

        // Must not throw
        (new HandleInspectionFailedListener($useCase))->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    public function test_graceful_degradation_on_runtime_exception(): void
    {
        $useCase = Mockery::mock(CreateMaintenanceRequestUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->andThrow(new \RuntimeException('DB connection lost'));

        // Must not throw
        (new HandleInspectionFailedListener($useCase))->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // InspectionFailed event defaults (backwards compatibility)
    // -------------------------------------------------------------------------

    public function test_inspection_failed_event_defaults_optional_fields(): void
    {
        $event = new InspectionFailed(
            inspectionId: 'inspection-1',
            tenantId:     'tenant-1',
        );

        $this->assertSame('', $event->title);
        $this->assertSame('', $event->productId);
        $this->assertSame('medium', $event->priority);
        $this->assertSame('', $event->equipmentId);
    }

    public function test_inspection_failed_event_carries_all_enriched_fields(): void
    {
        $event = new InspectionFailed(
            inspectionId: 'inspection-1',
            tenantId:     'tenant-1',
            title:        'Failed inspection #REF-001',
            productId:    'product-1',
            priority:     'critical',
            equipmentId:  'equipment-1',
        );

        $this->assertSame('Failed inspection #REF-001', $event->title);
        $this->assertSame('product-1', $event->productId);
        $this->assertSame('critical', $event->priority);
        $this->assertSame('equipment-1', $event->equipmentId);
    }
}
