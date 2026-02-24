<?php

namespace Tests\Unit\Budget;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Budget\Application\UseCases\ApproveBudgetUseCase;
use Modules\Budget\Application\UseCases\CloseBudgetUseCase;
use Modules\Budget\Application\UseCases\CreateBudgetUseCase;
use Modules\Budget\Application\UseCases\GetBudgetVarianceReportUseCase;
use Modules\Budget\Application\UseCases\RecordActualSpendUseCase;
use Modules\Budget\Domain\Contracts\BudgetLineRepositoryInterface;
use Modules\Budget\Domain\Contracts\BudgetRepositoryInterface;
use Modules\Budget\Domain\Events\BudgetApproved;
use Modules\Budget\Domain\Events\BudgetClosed;
use Modules\Budget\Domain\Events\BudgetCreated;
use Modules\Budget\Domain\Events\BudgetLineOverspent;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Budget Management module use cases.
 *
 * Covers budget creation with BCMath total aggregation, approval with guard,
 * and close lifecycle with domain event dispatch.
 */
class BudgetUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeBudget(string $status = 'draft'): object
    {
        return (object) [
            'id'           => 'budget-uuid-1',
            'tenant_id'    => 'tenant-uuid-1',
            'name'         => 'Q1 Budget',
            'period'       => 'quarterly',
            'start_date'   => '2026-01-01',
            'end_date'     => '2026-03-31',
            'total_amount' => '1000.00000000',
            'status'       => $status,
        ];
    }

    // -------------------------------------------------------------------------
    // CreateBudgetUseCase
    // -------------------------------------------------------------------------

    public function test_create_budget_sets_status_draft_and_dispatches_event(): void
    {
        $budget     = $this->makeBudget('draft');
        $budgetRepo = Mockery::mock(BudgetRepositoryInterface::class);
        $lineRepo   = Mockery::mock(BudgetLineRepositoryInterface::class);

        $budgetRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'draft' && $data['name'] === 'Q1 Budget')
            ->andReturn($budget);

        $lineRepo->shouldReceive('create')->never();

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof BudgetCreated
                && $event->budgetId === 'budget-uuid-1'
                && $event->name === 'Q1 Budget');

        $useCase = new CreateBudgetUseCase($budgetRepo, $lineRepo);
        $result  = $useCase->execute([
            'tenant_id'  => 'tenant-uuid-1',
            'name'       => 'Q1 Budget',
            'period'     => 'quarterly',
            'start_date' => '2026-01-01',
            'end_date'   => '2026-03-31',
        ]);

        $this->assertSame('draft', $result->status);
    }

    public function test_create_budget_aggregates_line_totals_with_bcmath(): void
    {
        $budget     = (object) [
            'id'           => 'budget-uuid-2',
            'tenant_id'    => 'tenant-uuid-1',
            'name'         => 'Annual Budget',
            'total_amount' => '300.00000000',
            'status'       => 'draft',
        ];
        $budgetRepo = Mockery::mock(BudgetRepositoryInterface::class);
        $lineRepo   = Mockery::mock(BudgetLineRepositoryInterface::class);

        $budgetRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['total_amount'] === '300.00000000')
            ->andReturn($budget);

        $lineRepo->shouldReceive('create')->twice();

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new CreateBudgetUseCase($budgetRepo, $lineRepo);
        $result  = $useCase->execute([
            'tenant_id'  => 'tenant-uuid-1',
            'name'       => 'Annual Budget',
            'period'     => 'annually',
            'start_date' => '2026-01-01',
            'end_date'   => '2026-12-31',
            'lines'      => [
                ['category' => 'Salaries', 'planned_amount' => '200'],
                ['category' => 'Equipment', 'planned_amount' => '100'],
            ],
        ]);

        $this->assertSame('300.00000000', $result->total_amount);
    }

    // -------------------------------------------------------------------------
    // ApproveBudgetUseCase
    // -------------------------------------------------------------------------

    public function test_approve_throws_when_budget_not_found(): void
    {
        $budgetRepo = Mockery::mock(BudgetRepositoryInterface::class);
        $budgetRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ApproveBudgetUseCase($budgetRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id', 'approver-uuid-1');
    }

    public function test_approve_throws_when_budget_not_draft(): void
    {
        $budgetRepo = Mockery::mock(BudgetRepositoryInterface::class);
        $budgetRepo->shouldReceive('findById')->andReturn($this->makeBudget('approved'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ApproveBudgetUseCase($budgetRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/only draft/i');

        $useCase->execute('budget-uuid-1', 'approver-uuid-1');
    }

    public function test_approve_transitions_to_approved_and_dispatches_event(): void
    {
        $draft    = $this->makeBudget('draft');
        $approved = (object) array_merge((array) $draft, [
            'status'      => 'approved',
            'approved_by' => 'approver-uuid-1',
        ]);

        $budgetRepo = Mockery::mock(BudgetRepositoryInterface::class);
        $budgetRepo->shouldReceive('findById')->andReturn($draft);
        $budgetRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'approved' && $data['approved_by'] === 'approver-uuid-1')
            ->once()
            ->andReturn($approved);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof BudgetApproved
                && $event->approverId === 'approver-uuid-1');

        $useCase = new ApproveBudgetUseCase($budgetRepo);
        $result  = $useCase->execute('budget-uuid-1', 'approver-uuid-1');

        $this->assertSame('approved', $result->status);
    }

    // -------------------------------------------------------------------------
    // CloseBudgetUseCase
    // -------------------------------------------------------------------------

    public function test_close_throws_when_budget_not_found(): void
    {
        $budgetRepo = Mockery::mock(BudgetRepositoryInterface::class);
        $budgetRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CloseBudgetUseCase($budgetRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_close_throws_when_already_closed(): void
    {
        $budgetRepo = Mockery::mock(BudgetRepositoryInterface::class);
        $budgetRepo->shouldReceive('findById')->andReturn($this->makeBudget('closed'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CloseBudgetUseCase($budgetRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/already closed/i');

        $useCase->execute('budget-uuid-1');
    }

    public function test_close_transitions_to_closed_and_dispatches_event(): void
    {
        $approved = $this->makeBudget('approved');
        $closed   = (object) array_merge((array) $approved, ['status' => 'closed']);

        $budgetRepo = Mockery::mock(BudgetRepositoryInterface::class);
        $budgetRepo->shouldReceive('findById')->andReturn($approved);
        $budgetRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'closed')
            ->once()
            ->andReturn($closed);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof BudgetClosed
                && $event->budgetId === 'budget-uuid-1');

        $useCase = new CloseBudgetUseCase($budgetRepo);
        $result  = $useCase->execute('budget-uuid-1');

        $this->assertSame('closed', $result->status);
    }

    // -------------------------------------------------------------------------
    // RecordActualSpendUseCase
    // -------------------------------------------------------------------------

    private function makeLine(string $planned = '500.00000000', string $actual = '0.00000000'): object
    {
        return (object) [
            'id'             => 'line-uuid-1',
            'budget_id'      => 'budget-uuid-1',
            'tenant_id'      => 'tenant-uuid-1',
            'category'       => 'Salaries',
            'description'    => null,
            'planned_amount' => $planned,
            'actual_amount'  => $actual,
        ];
    }

    public function test_record_actual_spend_throws_when_amount_is_zero(): void
    {
        $budgetRepo = Mockery::mock(BudgetRepositoryInterface::class);
        $lineRepo   = Mockery::mock(BudgetLineRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RecordActualSpendUseCase($budgetRepo, $lineRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/positive/i');

        $useCase->execute('budget-uuid-1', 'line-uuid-1', '0');
    }

    public function test_record_actual_spend_throws_when_budget_not_found(): void
    {
        $budgetRepo = Mockery::mock(BudgetRepositoryInterface::class);
        $budgetRepo->shouldReceive('findById')->andReturn(null);
        $lineRepo = Mockery::mock(BudgetLineRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RecordActualSpendUseCase($budgetRepo, $lineRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/budget not found/i');

        $useCase->execute('missing', 'line-uuid-1', '100');
    }

    public function test_record_actual_spend_throws_when_budget_not_approved(): void
    {
        $budget     = $this->makeBudget('draft');
        $budgetRepo = Mockery::mock(BudgetRepositoryInterface::class);
        $budgetRepo->shouldReceive('findById')->andReturn($budget);
        $lineRepo = Mockery::mock(BudgetLineRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RecordActualSpendUseCase($budgetRepo, $lineRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/approved budget/i');

        $useCase->execute('budget-uuid-1', 'line-uuid-1', '100');
    }

    public function test_record_actual_spend_throws_when_line_not_found(): void
    {
        $budget     = $this->makeBudget('approved');
        $budgetRepo = Mockery::mock(BudgetRepositoryInterface::class);
        $budgetRepo->shouldReceive('findById')->andReturn($budget);
        $lineRepo = Mockery::mock(BudgetLineRepositoryInterface::class);
        $lineRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RecordActualSpendUseCase($budgetRepo, $lineRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/budget line not found/i');

        $useCase->execute('budget-uuid-1', 'line-uuid-1', '100');
    }

    public function test_record_actual_spend_updates_actual_amount_without_overspend_event(): void
    {
        $budget     = $this->makeBudget('approved');
        $line       = $this->makeLine('500.00000000', '0.00000000');
        $updatedLine = (object) array_merge((array) $line, ['actual_amount' => '200.00000000']);

        $budgetRepo = Mockery::mock(BudgetRepositoryInterface::class);
        $budgetRepo->shouldReceive('findById')->andReturn($budget);

        $lineRepo = Mockery::mock(BudgetLineRepositoryInterface::class);
        $lineRepo->shouldReceive('findById')->andReturn($line);
        $lineRepo->shouldReceive('addActualAmount')
            ->with('line-uuid-1', '200.00000000')
            ->once()
            ->andReturn($updatedLine);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new RecordActualSpendUseCase($budgetRepo, $lineRepo);
        $result  = $useCase->execute('budget-uuid-1', 'line-uuid-1', '200');

        $this->assertSame('200.00000000', $result->actual_amount);
    }

    public function test_record_actual_spend_fires_overspent_event_when_actual_exceeds_planned(): void
    {
        $budget     = $this->makeBudget('approved');
        $line       = $this->makeLine('500.00000000', '400.00000000');
        $updatedLine = (object) array_merge((array) $line, ['actual_amount' => '600.00000000']);

        $budgetRepo = Mockery::mock(BudgetRepositoryInterface::class);
        $budgetRepo->shouldReceive('findById')->andReturn($budget);

        $lineRepo = Mockery::mock(BudgetLineRepositoryInterface::class);
        $lineRepo->shouldReceive('findById')->andReturn($line);
        $lineRepo->shouldReceive('addActualAmount')
            ->once()
            ->andReturn($updatedLine);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof BudgetLineOverspent
                && $event->category === 'Salaries'
                && bccomp($event->overspendAmount, '100.00000000', 8) === 0);

        $useCase = new RecordActualSpendUseCase($budgetRepo, $lineRepo);
        $result  = $useCase->execute('budget-uuid-1', 'line-uuid-1', '200');

        $this->assertSame('600.00000000', $result->actual_amount);
    }

    // -------------------------------------------------------------------------
    // GetBudgetVarianceReportUseCase
    // -------------------------------------------------------------------------

    public function test_variance_report_throws_when_budget_not_found(): void
    {
        $budgetRepo = Mockery::mock(BudgetRepositoryInterface::class);
        $budgetRepo->shouldReceive('findById')->andReturn(null);
        $lineRepo = Mockery::mock(BudgetLineRepositoryInterface::class);

        $useCase = new GetBudgetVarianceReportUseCase($budgetRepo, $lineRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/budget not found/i');

        $useCase->execute('missing');
    }

    public function test_variance_report_returns_correct_variance_data(): void
    {
        $budget = (object) [
            'id'     => 'budget-uuid-1',
            'name'   => 'Q1 Budget',
            'status' => 'approved',
        ];

        $lines = collect([
            (object) [
                'id'             => 'line-1',
                'category'       => 'Salaries',
                'description'    => null,
                'planned_amount' => '1000.00000000',
                'actual_amount'  => '800.00000000',
            ],
            (object) [
                'id'             => 'line-2',
                'category'       => 'Equipment',
                'description'    => 'Hardware',
                'planned_amount' => '500.00000000',
                'actual_amount'  => '600.00000000',
            ],
        ]);

        $budgetRepo = Mockery::mock(BudgetRepositoryInterface::class);
        $budgetRepo->shouldReceive('findById')->andReturn($budget);

        $lineRepo = Mockery::mock(BudgetLineRepositoryInterface::class);
        $lineRepo->shouldReceive('findByBudget')->with('budget-uuid-1')->andReturn($lines);

        $useCase = new GetBudgetVarianceReportUseCase($budgetRepo, $lineRepo);
        $report  = $useCase->execute('budget-uuid-1');

        $this->assertSame('1500.00000000', $report['total_planned']);
        $this->assertSame('1400.00000000', $report['total_actual']);
        $this->assertSame('100.00000000',  $report['total_variance']);
        $this->assertFalse($report['overspent']);
        $this->assertCount(2, $report['lines']);

        $salaries = $report['lines'][0];
        $this->assertSame('200.00000000', $salaries['variance']);
        $this->assertFalse($salaries['overspent']);
        $this->assertSame('80.00', $salaries['utilisation_pct']);

        $equipment = $report['lines'][1];
        $this->assertSame('-100.00000000', $equipment['variance']);
        $this->assertTrue($equipment['overspent']);
    }

    public function test_variance_report_total_overspent_when_actuals_exceed_total_planned(): void
    {
        $budget = (object) ['id' => 'budget-uuid-1', 'name' => 'Test', 'status' => 'approved'];
        $lines  = collect([
            (object) [
                'id'             => 'line-1',
                'category'       => 'Operations',
                'description'    => null,
                'planned_amount' => '300.00000000',
                'actual_amount'  => '400.00000000',
            ],
        ]);

        $budgetRepo = Mockery::mock(BudgetRepositoryInterface::class);
        $budgetRepo->shouldReceive('findById')->andReturn($budget);
        $lineRepo = Mockery::mock(BudgetLineRepositoryInterface::class);
        $lineRepo->shouldReceive('findByBudget')->andReturn($lines);

        $useCase = new GetBudgetVarianceReportUseCase($budgetRepo, $lineRepo);
        $report  = $useCase->execute('budget-uuid-1');

        $this->assertTrue($report['overspent']);
        $this->assertSame('-100.00000000', $report['total_variance']);
    }

    public function test_variance_report_utilisation_pct_null_when_planned_is_zero(): void
    {
        $budget = (object) ['id' => 'budget-uuid-1', 'name' => 'Test', 'status' => 'draft'];
        $lines  = collect([
            (object) [
                'id'             => 'line-1',
                'category'       => 'Misc',
                'description'    => null,
                'planned_amount' => '0.00000000',
                'actual_amount'  => '50.00000000',
            ],
        ]);

        $budgetRepo = Mockery::mock(BudgetRepositoryInterface::class);
        $budgetRepo->shouldReceive('findById')->andReturn($budget);
        $lineRepo = Mockery::mock(BudgetLineRepositoryInterface::class);
        $lineRepo->shouldReceive('findByBudget')->andReturn($lines);

        $useCase = new GetBudgetVarianceReportUseCase($budgetRepo, $lineRepo);
        $report  = $useCase->execute('budget-uuid-1');

        $this->assertNull($report['lines'][0]['utilisation_pct']);
    }
}
