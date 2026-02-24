<?php

namespace Tests\Unit\QualityControl;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\QualityControl\Application\UseCases\CreateInspectionUseCase;
use Modules\QualityControl\Application\UseCases\CreateQualityAlertUseCase;
use Modules\QualityControl\Application\UseCases\FailInspectionUseCase;
use Modules\QualityControl\Application\UseCases\PassInspectionUseCase;
use Modules\QualityControl\Domain\Contracts\InspectionRepositoryInterface;
use Modules\QualityControl\Domain\Contracts\QualityAlertRepositoryInterface;
use Modules\QualityControl\Domain\Events\InspectionFailed;
use Modules\QualityControl\Domain\Events\InspectionPassed;
use Modules\QualityControl\Domain\Events\QualityAlertRaised;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for QualityControl use cases.
 *
 * Covers inspection creation, pass/fail lifecycle guards, quantity validation,
 * quality alert creation, and domain event dispatch.
 */
class QualityControlUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeInspection(string $status = 'draft'): object
    {
        return (object) [
            'id'        => 'insp-uuid-1',
            'tenant_id' => 'tenant-uuid-1',
            'status'    => $status,
            'qty_inspected' => '10.00000000',
            'qty_failed'    => '0.00000000',
            'notes'     => null,
        ];
    }

    // -------------------------------------------------------------------------
    // CreateInspectionUseCase
    // -------------------------------------------------------------------------

    public function test_creates_inspection_with_draft_status(): void
    {
        $inspection = $this->makeInspection();

        $repo = Mockery::mock(InspectionRepositoryInterface::class);
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'draft' && $data['tenant_id'] === 'tenant-uuid-1')
            ->andReturn($inspection);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('whereYear')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);

        $useCase = new CreateInspectionUseCase($repo);
        $result  = $useCase->execute(['tenant_id' => 'tenant-uuid-1', 'qty_inspected' => '10']);

        $this->assertSame('draft', $result->status);
    }

    // -------------------------------------------------------------------------
    // PassInspectionUseCase
    // -------------------------------------------------------------------------

    public function test_pass_throws_when_inspection_not_found(): void
    {
        $repo = Mockery::mock(InspectionRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('missing-id')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new PassInspectionUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_pass_throws_when_already_passed(): void
    {
        $repo = Mockery::mock(InspectionRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($this->makeInspection('passed'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new PassInspectionUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/draft or in-progress/i');

        $useCase->execute('insp-uuid-1');
    }

    public function test_pass_transitions_to_passed_and_dispatches_event(): void
    {
        $inspection = $this->makeInspection('draft');
        $passed     = (object) array_merge((array) $inspection, ['status' => 'passed']);

        $repo = Mockery::mock(InspectionRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($inspection);
        $repo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'passed')
            ->once()
            ->andReturn($passed);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof InspectionPassed);

        $useCase = new PassInspectionUseCase($repo);
        $result  = $useCase->execute('insp-uuid-1');

        $this->assertSame('passed', $result->status);
    }

    // -------------------------------------------------------------------------
    // FailInspectionUseCase
    // -------------------------------------------------------------------------

    public function test_fail_throws_when_qty_failed_exceeds_inspected(): void
    {
        $repo = Mockery::mock(InspectionRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($this->makeInspection('draft'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new FailInspectionUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/cannot exceed/i');

        $useCase->execute('insp-uuid-1', ['qty_inspected' => '5', 'qty_failed' => '10']);
    }

    public function test_fail_transitions_to_failed_and_dispatches_event(): void
    {
        $inspection = $this->makeInspection('in_progress');
        $failed     = (object) array_merge((array) $inspection, [
            'status'    => 'failed',
            'qty_failed' => '3.00000000',
        ]);

        $repo = Mockery::mock(InspectionRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($inspection);
        $repo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'failed')
            ->once()
            ->andReturn($failed);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof InspectionFailed);

        $useCase = new FailInspectionUseCase($repo);
        $result  = $useCase->execute('insp-uuid-1', ['qty_inspected' => '10', 'qty_failed' => '3']);

        $this->assertSame('failed', $result->status);
    }

    // -------------------------------------------------------------------------
    // CreateQualityAlertUseCase
    // -------------------------------------------------------------------------

    public function test_creates_quality_alert_and_dispatches_event(): void
    {
        $alert = (object) [
            'id'        => 'alert-uuid-1',
            'tenant_id' => 'tenant-uuid-1',
            'title'     => 'Surface crack detected',
            'priority'  => 'high',
            'status'    => 'open',
        ];

        $repo = Mockery::mock(QualityAlertRepositoryInterface::class);
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'open' && $data['priority'] === 'high')
            ->andReturn($alert);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof QualityAlertRaised);

        $useCase = new CreateQualityAlertUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'title'     => 'Surface crack detected',
            'priority'  => 'high',
        ]);

        $this->assertSame('open', $result->status);
        $this->assertSame('high', $result->priority);
    }
}
