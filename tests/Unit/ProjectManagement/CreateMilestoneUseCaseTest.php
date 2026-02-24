<?php

namespace Tests\Unit\ProjectManagement;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\ProjectManagement\Application\UseCases\CreateMilestoneUseCase;
use Modules\ProjectManagement\Domain\Contracts\MilestoneRepositoryInterface;
use Modules\ProjectManagement\Domain\Contracts\ProjectRepositoryInterface;
use Modules\ProjectManagement\Domain\Events\MilestoneCreated;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateMilestoneUseCase.
 *
 * Covers: empty-name guard, missing project_id guard, missing due_date guard,
 * project-not-found guard, and successful creation with event dispatch.
 */
class CreateMilestoneUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeProject(): object
    {
        return (object) [
            'id'        => 'project-uuid-1',
            'tenant_id' => 'tenant-uuid-1',
            'name'      => 'Test Project',
        ];
    }

    private function makeMilestone(string $status = 'pending'): object
    {
        return (object) [
            'id'         => 'milestone-uuid-1',
            'project_id' => 'project-uuid-1',
            'tenant_id'  => 'tenant-uuid-1',
            'name'       => 'Launch MVP',
            'due_date'   => '2024-12-31',
            'status'     => $status,
        ];
    }

    // -------------------------------------------------------------------------
    // Guard: empty name
    // -------------------------------------------------------------------------

    public function test_throws_when_name_is_empty(): void
    {
        $milestoneRepo = Mockery::mock(MilestoneRepositoryInterface::class);
        $projectRepo   = Mockery::mock(ProjectRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateMilestoneUseCase($milestoneRepo, $projectRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Milestone name is required.');

        $useCase->execute([
            'name'       => '',
            'project_id' => 'project-uuid-1',
            'due_date'   => '2024-12-31',
            'tenant_id'  => 'tenant-uuid-1',
        ]);
    }

    // -------------------------------------------------------------------------
    // Guard: missing project_id
    // -------------------------------------------------------------------------

    public function test_throws_when_project_id_is_missing(): void
    {
        $milestoneRepo = Mockery::mock(MilestoneRepositoryInterface::class);
        $projectRepo   = Mockery::mock(ProjectRepositoryInterface::class);

        $useCase = new CreateMilestoneUseCase($milestoneRepo, $projectRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Project ID is required.');

        $useCase->execute([
            'name'      => 'Launch MVP',
            'due_date'  => '2024-12-31',
            'tenant_id' => 'tenant-uuid-1',
        ]);
    }

    // -------------------------------------------------------------------------
    // Guard: missing due_date
    // -------------------------------------------------------------------------

    public function test_throws_when_due_date_is_missing(): void
    {
        $milestoneRepo = Mockery::mock(MilestoneRepositoryInterface::class);
        $projectRepo   = Mockery::mock(ProjectRepositoryInterface::class);

        $useCase = new CreateMilestoneUseCase($milestoneRepo, $projectRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Due date is required.');

        $useCase->execute([
            'name'       => 'Launch MVP',
            'project_id' => 'project-uuid-1',
            'tenant_id'  => 'tenant-uuid-1',
        ]);
    }

    // -------------------------------------------------------------------------
    // Guard: project not found
    // -------------------------------------------------------------------------

    public function test_throws_when_project_not_found(): void
    {
        $milestoneRepo = Mockery::mock(MilestoneRepositoryInterface::class);
        $projectRepo   = Mockery::mock(ProjectRepositoryInterface::class);
        $projectRepo->shouldReceive('findById')->with('missing-project')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateMilestoneUseCase($milestoneRepo, $projectRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute([
            'name'       => 'Launch MVP',
            'project_id' => 'missing-project',
            'due_date'   => '2024-12-31',
            'tenant_id'  => 'tenant-uuid-1',
        ]);
    }

    // -------------------------------------------------------------------------
    // Successful creation
    // -------------------------------------------------------------------------

    public function test_creates_milestone_with_default_pending_status_and_dispatches_event(): void
    {
        $project   = $this->makeProject();
        $milestone = $this->makeMilestone('pending');

        $milestoneRepo = Mockery::mock(MilestoneRepositoryInterface::class);
        $milestoneRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'pending' && $data['name'] === 'Launch MVP')
            ->andReturn($milestone);

        $projectRepo = Mockery::mock(ProjectRepositoryInterface::class);
        $projectRepo->shouldReceive('findById')->with('project-uuid-1')->andReturn($project);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof MilestoneCreated
                && $event->milestoneId === 'milestone-uuid-1'
                && $event->projectId  === 'project-uuid-1'
                && $event->name       === 'Launch MVP');

        $useCase = new CreateMilestoneUseCase($milestoneRepo, $projectRepo);
        $result  = $useCase->execute([
            'name'       => 'Launch MVP',
            'project_id' => 'project-uuid-1',
            'due_date'   => '2024-12-31',
            'tenant_id'  => 'tenant-uuid-1',
        ]);

        $this->assertSame('milestone-uuid-1', $result->id);
        $this->assertSame('pending', $result->status);
    }

    public function test_accepts_explicit_status_override(): void
    {
        $project   = $this->makeProject();
        $milestone = $this->makeMilestone('in_progress');

        $milestoneRepo = Mockery::mock(MilestoneRepositoryInterface::class);
        $milestoneRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'in_progress')
            ->andReturn($milestone);

        $projectRepo = Mockery::mock(ProjectRepositoryInterface::class);
        $projectRepo->shouldReceive('findById')->andReturn($project);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new CreateMilestoneUseCase($milestoneRepo, $projectRepo);
        $result  = $useCase->execute([
            'name'       => 'Launch MVP',
            'project_id' => 'project-uuid-1',
            'due_date'   => '2024-12-31',
            'status'     => 'in_progress',
            'tenant_id'  => 'tenant-uuid-1',
        ]);

        $this->assertSame('in_progress', $result->status);
    }
}
