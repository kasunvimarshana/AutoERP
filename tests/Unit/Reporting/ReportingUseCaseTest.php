<?php

namespace Tests\Unit\Reporting;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Reporting\Application\UseCases\CreateDashboardUseCase;
use Modules\Reporting\Application\UseCases\SaveReportUseCase;
use Modules\Reporting\Domain\Contracts\DashboardRepositoryInterface;
use Modules\Reporting\Domain\Contracts\ReportRepositoryInterface;
use Modules\Reporting\Domain\Events\DashboardCreated;
use Modules\Reporting\Domain\Events\ReportSaved;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Reporting module use cases.
 *
 * Covers dashboard creation, report saving, and domain event assertions.
 */
class ReportingUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // CreateDashboardUseCase
    // -------------------------------------------------------------------------

    public function test_create_dashboard_dispatches_event(): void
    {
        $dashboard = (object) [
            'id'        => 'dash-uuid-1',
            'tenant_id' => 'tenant-uuid-1',
            'user_id'   => 'user-uuid-1',
            'name'      => 'Sales Overview',
        ];

        $repo = Mockery::mock(DashboardRepositoryInterface::class);
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['name'] === 'Sales Overview' && $data['is_shared'] === false)
            ->andReturn($dashboard);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof DashboardCreated && $e->name === 'Sales Overview');

        $useCase = new CreateDashboardUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'user_id'   => 'user-uuid-1',
            'name'      => 'Sales Overview',
        ]);

        $this->assertSame('Sales Overview', $result->name);
    }

    // -------------------------------------------------------------------------
    // SaveReportUseCase
    // -------------------------------------------------------------------------

    public function test_save_report_dispatches_event(): void
    {
        $report = (object) [
            'id'        => 'report-uuid-1',
            'tenant_id' => 'tenant-uuid-1',
            'user_id'   => 'user-uuid-1',
            'name'      => 'Monthly Sales',
            'type'      => 'sales',
        ];

        $repo = Mockery::mock(ReportRepositoryInterface::class);
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['type'] === 'sales')
            ->andReturn($report);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof ReportSaved && $e->name === 'Monthly Sales');

        $useCase = new SaveReportUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'user_id'   => 'user-uuid-1',
            'name'      => 'Monthly Sales',
            'type'      => 'sales',
        ]);

        $this->assertSame('sales', $result->type);
    }

    public function test_save_report_defaults_to_custom_type(): void
    {
        $report = (object) [
            'id'        => 'report-uuid-2',
            'tenant_id' => 'tenant-uuid-1',
            'user_id'   => 'user-uuid-1',
            'name'      => 'Custom Report',
            'type'      => 'custom',
        ];

        $repo = Mockery::mock(ReportRepositoryInterface::class);
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['type'] === 'custom')
            ->andReturn($report);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new SaveReportUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'user_id'   => 'user-uuid-1',
            'name'      => 'Custom Report',
        ]);

        $this->assertSame('custom', $result->type);
    }
}
