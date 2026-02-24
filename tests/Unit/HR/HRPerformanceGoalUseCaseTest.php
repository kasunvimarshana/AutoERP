<?php

namespace Tests\Unit\HR;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\HR\Application\UseCases\CompletePerformanceGoalUseCase;
use Modules\HR\Application\UseCases\CreatePerformanceGoalUseCase;
use Modules\HR\Domain\Contracts\PerformanceGoalRepositoryInterface;
use Modules\HR\Domain\Enums\GoalStatus;
use Modules\HR\Domain\Events\PerformanceGoalCompleted;
use Modules\HR\Domain\Events\PerformanceGoalCreated;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for HR Performance Goal use cases.
 *
 * Verifies goal creation, completion guards, and domain event dispatch.
 */
class HRPerformanceGoalUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // CreatePerformanceGoalUseCase
    // -----------------------------------------------------------------------

    public function test_create_goal_sets_active_status_and_dispatches_event(): void
    {
        $repo = Mockery::mock(PerformanceGoalRepositoryInterface::class);

        $goal = (object) ['id' => 'goal-1', 'status' => GoalStatus::Active->value];
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(function (array $data) {
                return $data['status'] === GoalStatus::Active->value
                    && $data['title'] === 'Increase sales by 20%';
            })
            ->andReturn($goal);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof PerformanceGoalCreated && $e->goalId === 'goal-1');

        $useCase = new CreatePerformanceGoalUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id'   => 'tenant-1',
            'employee_id' => 'emp-1',
            'title'       => 'Increase sales by 20%',
            'period'      => 'q1',
            'year'        => 2025,
        ]);

        $this->assertSame(GoalStatus::Active->value, $result->status);
    }

    public function test_create_goal_with_all_fields(): void
    {
        $repo = Mockery::mock(PerformanceGoalRepositoryInterface::class);

        $goal = (object) [
            'id'          => 'goal-2',
            'status'      => GoalStatus::Active->value,
            'title'       => 'Reduce churn by 10%',
            'period'      => 'annual',
            'year'        => 2025,
            'due_date'    => '2025-12-31',
            'description' => 'Focus on customer retention initiatives.',
        ];

        $repo->shouldReceive('create')->once()->andReturn($goal);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new CreatePerformanceGoalUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id'   => 'tenant-1',
            'employee_id' => 'emp-2',
            'title'       => 'Reduce churn by 10%',
            'description' => 'Focus on customer retention initiatives.',
            'period'      => 'annual',
            'year'        => 2025,
            'due_date'    => '2025-12-31',
        ]);

        $this->assertSame('Reduce churn by 10%', $result->title);
        $this->assertSame('annual', $result->period);
    }

    // -----------------------------------------------------------------------
    // CompletePerformanceGoalUseCase
    // -----------------------------------------------------------------------

    public function test_complete_throws_when_goal_not_found(): void
    {
        $repo = Mockery::mock(PerformanceGoalRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('missing')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CompletePerformanceGoalUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing');
    }

    public function test_complete_throws_when_already_completed(): void
    {
        $repo = Mockery::mock(PerformanceGoalRepositoryInterface::class);
        $goal = (object) ['id' => 'goal-1', 'status' => GoalStatus::Completed->value];
        $repo->shouldReceive('findById')->with('goal-1')->andReturn($goal);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CompletePerformanceGoalUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/already completed/i');

        $useCase->execute('goal-1');
    }

    public function test_complete_throws_when_goal_is_cancelled(): void
    {
        $repo = Mockery::mock(PerformanceGoalRepositoryInterface::class);
        $goal = (object) ['id' => 'goal-3', 'status' => GoalStatus::Cancelled->value];
        $repo->shouldReceive('findById')->with('goal-3')->andReturn($goal);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CompletePerformanceGoalUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/cancelled/i');

        $useCase->execute('goal-3');
    }

    public function test_complete_transitions_to_completed_and_dispatches_event(): void
    {
        $repo = Mockery::mock(PerformanceGoalRepositoryInterface::class);
        $goal = (object) ['id' => 'goal-1', 'status' => GoalStatus::Active->value];
        $completed = (object) ['id' => 'goal-1', 'status' => GoalStatus::Completed->value];

        $repo->shouldReceive('findById')->with('goal-1')->andReturn($goal);
        $repo->shouldReceive('update')
            ->once()
            ->withArgs(function (string $id, array $data) {
                return $id === 'goal-1'
                    && $data['status'] === GoalStatus::Completed->value
                    && isset($data['completed_at']);
            })
            ->andReturn($completed);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof PerformanceGoalCompleted && $e->goalId === 'goal-1');

        $useCase = new CompletePerformanceGoalUseCase($repo);
        $result  = $useCase->execute('goal-1');

        $this->assertSame(GoalStatus::Completed->value, $result->status);
    }
}
