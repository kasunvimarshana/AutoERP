<?php
declare(strict_types=1);
namespace Tests\Unit;

use Modules\Maintenance\Application\Services\MaintenanceScheduleService;
use Modules\Maintenance\Application\Services\ServiceOrderService;
use Modules\Maintenance\Domain\Entities\MaintenanceSchedule;
use Modules\Maintenance\Domain\Entities\ServiceOrder;
use Modules\Maintenance\Domain\Entities\ServiceOrderLine;
use Modules\Maintenance\Domain\Exceptions\MaintenanceScheduleNotFoundException;
use Modules\Maintenance\Domain\Exceptions\ServiceOrderNotFoundException;
use Modules\Maintenance\Domain\RepositoryInterfaces\MaintenanceScheduleRepositoryInterface;
use Modules\Maintenance\Domain\RepositoryInterfaces\ServiceOrderRepositoryInterface;
use PHPUnit\Framework\TestCase;

class MaintenanceModuleTest extends TestCase
{
    // ── helpers ────────────────────────────────────────────────────────────
    private function makeOrder(
        ?int $id = 1,
        string $status = ServiceOrder::STATUS_DRAFT,
    ): ServiceOrder {
        return new ServiceOrder(
            $id, 1, 'SO-001',
            ServiceOrder::TYPE_CORRECTIVE,
            $status,
            ServiceOrder::PRIORITY_MEDIUM,
            'Fix broken pump', null,
            null, null, null, null,
            4.0, 0.0, 0.0, 0.0,
            null, null, null, null, null,
        );
    }

    private function makeSchedule(?int $id = 1, bool $active = true, ?\DateTimeInterface $nextRun = null): MaintenanceSchedule
    {
        return new MaintenanceSchedule(
            $id, 1, 'Monthly HVAC Check', null,
            ServiceOrder::TYPE_PREVENTIVE,
            1, 'month',
            null, $nextRun, $active, null, null,
        );
    }

    // ── ServiceOrder entity tests ─────────────────────────────────────────
    public function testServiceOrderCreation(): void
    {
        $o = $this->makeOrder();
        $this->assertSame('SO-001', $o->getOrderNumber());
        $this->assertSame(ServiceOrder::STATUS_DRAFT, $o->getStatus());
        $this->assertSame(ServiceOrder::TYPE_CORRECTIVE, $o->getType());
        $this->assertEqualsWithDelta(0.0, $o->getTotalCost(), 0.001);
        $this->assertFalse($o->isCompleted());
    }

    public function testServiceOrderStart(): void
    {
        $o = $this->makeOrder(status: ServiceOrder::STATUS_SCHEDULED);
        $o->start();
        $this->assertSame(ServiceOrder::STATUS_IN_PROGRESS, $o->getStatus());
        $this->assertNotNull($o->getStartedAt());
    }

    public function testServiceOrderStartFromDraftThrows(): void
    {
        $this->expectException(\DomainException::class);
        $o = $this->makeOrder(status: ServiceOrder::STATUS_DRAFT);
        $o->start();
    }

    public function testServiceOrderComplete(): void
    {
        $o = $this->makeOrder(status: ServiceOrder::STATUS_IN_PROGRESS);
        $o->complete(6.5, 300.0, 150.0);
        $this->assertTrue($o->isCompleted());
        $this->assertEqualsWithDelta(450.0, $o->getTotalCost(), 0.001);
        $this->assertEqualsWithDelta(6.5, $o->getActualHours(), 0.001);
        $this->assertNotNull($o->getCompletedAt());
    }

    public function testServiceOrderCompleteNotInProgressThrows(): void
    {
        $this->expectException(\DomainException::class);
        $o = $this->makeOrder(status: ServiceOrder::STATUS_SCHEDULED);
        $o->complete(1.0, 0.0, 0.0);
    }

    public function testServiceOrderCancel(): void
    {
        $o = $this->makeOrder(status: ServiceOrder::STATUS_SCHEDULED);
        $o->cancel();
        $this->assertSame(ServiceOrder::STATUS_CANCELLED, $o->getStatus());
    }

    public function testServiceOrderCancelCompletedThrows(): void
    {
        $this->expectException(\DomainException::class);
        $o = $this->makeOrder(status: ServiceOrder::STATUS_COMPLETED);
        $o->cancel();
    }

    public function testServiceOrderTotalCost(): void
    {
        $o = $this->makeOrder(status: ServiceOrder::STATUS_IN_PROGRESS);
        $o->complete(2.0, 200.0, 50.0);
        $this->assertEqualsWithDelta(250.0, $o->getTotalCost(), 0.001);
    }

    // ── ServiceOrderLine entity ───────────────────────────────────────────
    public function testServiceOrderLineCreation(): void
    {
        $line = new ServiceOrderLine(1, 1, 'Filter replacement', null, 2.0, 25.0, 50.0, null);
        $this->assertSame(1, $line->getServiceOrderId());
        $this->assertEqualsWithDelta(50.0, $line->getTotalCost(), 0.001);
        $this->assertSame('Filter replacement', $line->getDescription());
    }

    // ── MaintenanceSchedule entity ────────────────────────────────────────
    public function testMaintenanceScheduleCreation(): void
    {
        $s = $this->makeSchedule();
        $this->assertSame('Monthly HVAC Check', $s->getName());
        $this->assertTrue($s->isActive());
        $this->assertSame(1, $s->getFrequencyValue());
        $this->assertSame('month', $s->getFrequencyUnit());
    }

    public function testMaintenanceScheduleActivateDeactivate(): void
    {
        $s = $this->makeSchedule(active: false);
        $s->activate();
        $this->assertTrue($s->isActive());
        $s->deactivate();
        $this->assertFalse($s->isActive());
    }

    public function testMaintenanceScheduleRecordRun(): void
    {
        $s  = $this->makeSchedule();
        $dt = new \DateTimeImmutable('2024-03-01');
        $s->recordRun($dt);
        $this->assertSame($dt, $s->getLastRunAt());
        // next run = +1 month = 2024-04-01
        $this->assertSame('2024-04-01', $s->getNextRunAt()->format('Y-m-d'));
    }

    public function testMaintenanceScheduleWeeklyFrequency(): void
    {
        $s = new MaintenanceSchedule(1, 1, 'Weekly check', null, 'preventive', 2, 'week', null, null, true, null, null);
        $dt = new \DateTimeImmutable('2024-01-01');
        $s->recordRun($dt);
        $this->assertSame('2024-01-15', $s->getNextRunAt()->format('Y-m-d'));
    }

    // ── ServiceOrderService tests ─────────────────────────────────────────
    public function testServiceOrderServiceFindById(): void
    {
        $order = $this->makeOrder();
        $repo  = $this->createMock(ServiceOrderRepositoryInterface::class);
        $repo->method('findById')->willReturn($order);
        $result = (new ServiceOrderService($repo))->findById(1);
        $this->assertSame('SO-001', $result->getOrderNumber());
    }

    public function testServiceOrderServiceFindNotFoundThrows(): void
    {
        $repo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);
        $this->expectException(ServiceOrderNotFoundException::class);
        (new ServiceOrderService($repo))->findById(99);
    }

    public function testServiceOrderServiceCreate(): void
    {
        $order = $this->makeOrder();
        $repo  = $this->createMock(ServiceOrderRepositoryInterface::class);
        $repo->method('create')->willReturn($order);
        $result = (new ServiceOrderService($repo))->create(['order_number' => 'SO-001']);
        $this->assertSame(1, $result->getId());
    }

    public function testServiceOrderServiceStart(): void
    {
        $order    = $this->makeOrder(status: ServiceOrder::STATUS_SCHEDULED);
        $inProg   = $this->makeOrder(status: ServiceOrder::STATUS_IN_PROGRESS);
        $repo     = $this->createMock(ServiceOrderRepositoryInterface::class);
        $repo->method('findById')->willReturn($order);
        $repo->method('update')->willReturn($inProg);
        $result = (new ServiceOrderService($repo))->start(1);
        $this->assertSame(ServiceOrder::STATUS_IN_PROGRESS, $result->getStatus());
    }

    public function testServiceOrderServiceComplete(): void
    {
        $order     = $this->makeOrder(status: ServiceOrder::STATUS_IN_PROGRESS);
        $completed = $this->makeOrder(status: ServiceOrder::STATUS_COMPLETED);
        $repo      = $this->createMock(ServiceOrderRepositoryInterface::class);
        $repo->method('findById')->willReturn($order);
        $repo->method('update')->willReturn($completed);
        $result = (new ServiceOrderService($repo))->complete(1, 3.0, 200.0, 100.0);
        $this->assertSame(ServiceOrder::STATUS_COMPLETED, $result->getStatus());
    }

    public function testServiceOrderServiceCancel(): void
    {
        $order     = $this->makeOrder(status: ServiceOrder::STATUS_SCHEDULED);
        $cancelled = $this->makeOrder(status: ServiceOrder::STATUS_CANCELLED);
        $repo      = $this->createMock(ServiceOrderRepositoryInterface::class);
        $repo->method('findById')->willReturn($order);
        $repo->method('update')->willReturn($cancelled);
        $result = (new ServiceOrderService($repo))->cancel(1);
        $this->assertSame(ServiceOrder::STATUS_CANCELLED, $result->getStatus());
    }

    // ── MaintenanceScheduleService tests ─────────────────────────────────
    public function testScheduleServiceFindNotFoundThrows(): void
    {
        $sRepo = $this->createMock(MaintenanceScheduleRepositoryInterface::class);
        $sRepo->method('findById')->willReturn(null);
        $oRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $this->expectException(MaintenanceScheduleNotFoundException::class);
        (new MaintenanceScheduleService($sRepo, $oRepo))->findById(99);
    }

    public function testScheduleServiceProcessDue(): void
    {
        $due   = $this->makeSchedule(nextRun: new \DateTimeImmutable('-1 day'));
        $sRepo = $this->createMock(MaintenanceScheduleRepositoryInterface::class);
        $sRepo->method('findDue')->willReturn([$due]);
        $sRepo->method('update')->willReturn($due);

        $oRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $oRepo->method('create')->willReturn($this->makeOrder(status: ServiceOrder::STATUS_SCHEDULED));

        $count = (new MaintenanceScheduleService($sRepo, $oRepo))->processDue(1);
        $this->assertSame(1, $count);
    }

    public function testScheduleServiceProcessDueNone(): void
    {
        $sRepo = $this->createMock(MaintenanceScheduleRepositoryInterface::class);
        $sRepo->method('findDue')->willReturn([]);
        $oRepo = $this->createMock(ServiceOrderRepositoryInterface::class);
        $count = (new MaintenanceScheduleService($sRepo, $oRepo))->processDue(1);
        $this->assertSame(0, $count);
    }

    // ── Constants check ───────────────────────────────────────────────────
    public function testServiceOrderConstants(): void
    {
        $this->assertSame('corrective',  ServiceOrder::TYPE_CORRECTIVE);
        $this->assertSame('preventive',  ServiceOrder::TYPE_PREVENTIVE);
        $this->assertSame('in_progress', ServiceOrder::STATUS_IN_PROGRESS);
        $this->assertSame('critical',    ServiceOrder::PRIORITY_CRITICAL);
    }

    // ── Exception messages ────────────────────────────────────────────────
    public function testExceptionMessages(): void
    {
        $this->assertStringContainsString('42', (new ServiceOrderNotFoundException(42))->getMessage());
        $this->assertStringContainsString('7',  (new MaintenanceScheduleNotFoundException(7))->getMessage());
    }
}
