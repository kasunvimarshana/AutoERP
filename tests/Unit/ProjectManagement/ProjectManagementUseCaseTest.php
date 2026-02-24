<?php

namespace Tests\Unit\ProjectManagement;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\ProjectManagement\Application\UseCases\CompleteTaskUseCase;
use Modules\ProjectManagement\Application\UseCases\LogTimeUseCase;
use Modules\ProjectManagement\Domain\Contracts\ProjectRepositoryInterface;
use Modules\ProjectManagement\Domain\Contracts\TaskRepositoryInterface;
use Modules\ProjectManagement\Domain\Contracts\TimeEntryRepositoryInterface;
use Modules\ProjectManagement\Domain\Events\TaskCompleted;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ProjectManagement use cases.
 *
 * Covers CompleteTaskUseCase (not-found guard, actual_hours accumulation,
 * TaskCompleted event) and LogTimeUseCase (BCMath spent accumulation).
 */
class ProjectManagementUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // CompleteTaskUseCase
    // -------------------------------------------------------------------------

    private function makeTask(string $id = 'task-uuid-1', string $status = 'in_progress'): object
    {
        return (object) [
            'id'           => $id,
            'project_id'   => 'project-uuid-1',
            'tenant_id'    => 'tenant-uuid-1',
            'status'       => $status,
            'actual_hours' => '3.50',
        ];
    }

    public function test_complete_task_throws_when_not_found(): void
    {
        $repo = Mockery::mock(TaskRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('missing-id')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CompleteTaskUseCase($repo);

        $this->expectException(ModelNotFoundException::class);

        $useCase->execute('missing-id');
    }

    public function test_complete_task_sets_status_done_and_dispatches_event(): void
    {
        $task    = $this->makeTask();
        $updated = (object) array_merge((array) $task, ['status' => 'done']);

        $repo = Mockery::mock(TaskRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($task);
        $repo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $data['status'] === 'done')
            ->andReturn($updated);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof TaskCompleted);

        $useCase = new CompleteTaskUseCase($repo);
        $result  = $useCase->execute('task-uuid-1');

        $this->assertSame('done', $result->status);
    }

    public function test_complete_task_accumulates_actual_hours_using_bcmath(): void
    {
        // existing actual_hours=3.50, adding 1.25 → should be 4.75 (bcadd scale 2)
        $task    = $this->makeTask();
        $updated = (object) array_merge((array) $task, [
            'status'       => 'done',
            'actual_hours' => '4.75',
        ]);

        $repo = Mockery::mock(TaskRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($task);
        $repo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => isset($data['actual_hours']) && $data['actual_hours'] === '4.75')
            ->andReturn($updated);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new CompleteTaskUseCase($repo);
        $result  = $useCase->execute('task-uuid-1', '1.25');

        $this->assertSame('4.75', $result->actual_hours);
    }

    public function test_complete_task_without_actual_hours_does_not_update_hours(): void
    {
        $task    = $this->makeTask();
        $updated = (object) array_merge((array) $task, ['status' => 'done']);

        $repo = Mockery::mock(TaskRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($task);
        $repo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => ! isset($data['actual_hours']))
            ->andReturn($updated);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new CompleteTaskUseCase($repo);
        $useCase->execute('task-uuid-1');
        $this->assertTrue(true); // no exception = pass
    }

    // -------------------------------------------------------------------------
    // LogTimeUseCase
    // -------------------------------------------------------------------------

    public function test_log_time_creates_entry_and_updates_project_spent(): void
    {
        $entry   = (object) ['id' => 'entry-uuid-1', 'hours' => '2.50'];
        $project = (object) ['id' => 'proj-uuid-1', 'spent' => '5.00000000'];

        $timeEntryRepo = Mockery::mock(TimeEntryRepositoryInterface::class);
        $timeEntryRepo->shouldReceive('create')
            ->once()
            ->andReturn($entry);

        $projectRepo = Mockery::mock(ProjectRepositoryInterface::class);
        $projectRepo->shouldReceive('findById')->andReturn($project);
        $projectRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $data['spent'] === '7.50000000') // 5 + 2.5
            ->andReturn($project);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new LogTimeUseCase($timeEntryRepo, $projectRepo);
        $result  = $useCase->execute([
            'project_id' => 'proj-uuid-1',
            'hours'      => '2.50',
            'entry_date' => '2024-06-01',
            'tenant_id'  => 'tenant-uuid-1',
            'user_id'    => 'user-uuid-1',
        ]);

        $this->assertSame('entry-uuid-1', $result->id);
    }

    public function test_log_time_skips_project_update_when_project_not_found(): void
    {
        $entry = (object) ['id' => 'entry-uuid-2', 'hours' => '1.00'];

        $timeEntryRepo = Mockery::mock(TimeEntryRepositoryInterface::class);
        $timeEntryRepo->shouldReceive('create')->once()->andReturn($entry);

        $projectRepo = Mockery::mock(ProjectRepositoryInterface::class);
        $projectRepo->shouldReceive('findById')->andReturn(null);
        $projectRepo->shouldReceive('update')->never();

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new LogTimeUseCase($timeEntryRepo, $projectRepo);
        $result  = $useCase->execute([
            'project_id' => 'proj-uuid-missing',
            'hours'      => '1.00',
            'entry_date' => '2024-06-01',
            'tenant_id'  => 'tenant-uuid-1',
            'user_id'    => 'user-uuid-1',
        ]);

        $this->assertSame('entry-uuid-2', $result->id);
    }
}
