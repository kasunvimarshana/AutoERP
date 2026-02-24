<?php

namespace Tests\Unit\Workflow;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Workflow\Application\UseCases\CreateWorkflowUseCase;
use Modules\Workflow\Application\UseCases\TransitionWorkflowUseCase;
use Modules\Workflow\Domain\Contracts\WorkflowHistoryRepositoryInterface;
use Modules\Workflow\Domain\Contracts\WorkflowRepositoryInterface;
use Modules\Workflow\Domain\Events\WorkflowCreated;
use Modules\Workflow\Domain\Events\WorkflowTransitioned;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Workflow module use cases.
 *
 * Covers workflow creation (states validation), transition guard,
 * and domain event assertions.
 */
class WorkflowUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeWorkflow(): object
    {
        return (object) [
            'id'            => 'wf-uuid-1',
            'tenant_id'     => 'tenant-uuid-1',
            'name'          => 'Invoice Approval',
            'document_type' => 'invoice',
            'states'        => ['draft', 'pending_approval', 'approved'],
            'transitions'   => [
                ['from' => 'draft', 'to' => 'pending_approval'],
                ['from' => 'pending_approval', 'to' => 'approved'],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // CreateWorkflowUseCase
    // -------------------------------------------------------------------------

    public function test_create_workflow_dispatches_event(): void
    {
        $workflow = $this->makeWorkflow();

        $repo = Mockery::mock(WorkflowRepositoryInterface::class);
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['document_type'] === 'invoice' && count($data['states']) === 3)
            ->andReturn($workflow);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof WorkflowCreated && $e->documentType === 'invoice');

        $useCase = new CreateWorkflowUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id'     => 'tenant-uuid-1',
            'name'          => 'Invoice Approval',
            'document_type' => 'invoice',
            'states'        => ['draft', 'pending_approval', 'approved'],
            'transitions'   => [['from' => 'draft', 'to' => 'pending_approval']],
        ]);

        $this->assertSame('invoice', $result->document_type);
    }

    public function test_create_workflow_throws_when_no_states(): void
    {
        $repo = Mockery::mock(WorkflowRepositoryInterface::class);
        $repo->shouldNotReceive('create');

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CreateWorkflowUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/at least one state/i');

        $useCase->execute([
            'tenant_id'     => 'tenant-uuid-1',
            'name'          => 'Empty Workflow',
            'document_type' => 'invoice',
            'states'        => [],
        ]);
    }

    // -------------------------------------------------------------------------
    // TransitionWorkflowUseCase
    // -------------------------------------------------------------------------

    public function test_transition_records_history_and_dispatches_event(): void
    {
        $workflow = $this->makeWorkflow();
        $history  = (object) [
            'id'          => 'hist-uuid-1',
            'workflow_id' => $workflow->id,
            'from_state'  => 'draft',
            'to_state'    => 'pending_approval',
        ];

        $workflowRepo = Mockery::mock(WorkflowRepositoryInterface::class);
        $workflowRepo->shouldReceive('findById')->andReturn($workflow);

        $historyRepo = Mockery::mock(WorkflowHistoryRepositoryInterface::class);
        $historyRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['from_state'] === 'draft' && $data['to_state'] === 'pending_approval')
            ->andReturn($history);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof WorkflowTransitioned
                && $e->fromState === 'draft'
                && $e->toState === 'pending_approval');

        $useCase = new TransitionWorkflowUseCase($workflowRepo, $historyRepo);
        $result  = $useCase->execute([
            'tenant_id'     => 'tenant-uuid-1',
            'workflow_id'   => $workflow->id,
            'document_type' => 'invoice',
            'document_id'   => 'inv-uuid-1',
            'from_state'    => 'draft',
            'to_state'      => 'pending_approval',
            'actor_id'      => 'user-uuid-1',
        ]);

        $this->assertSame('pending_approval', $result->to_state);
    }

    public function test_transition_throws_when_not_permitted(): void
    {
        $workflow = $this->makeWorkflow();

        $workflowRepo = Mockery::mock(WorkflowRepositoryInterface::class);
        $workflowRepo->shouldReceive('findById')->andReturn($workflow);

        $historyRepo = Mockery::mock(WorkflowHistoryRepositoryInterface::class);
        $historyRepo->shouldNotReceive('create');

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new TransitionWorkflowUseCase($workflowRepo, $historyRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not permitted/i');

        $useCase->execute([
            'tenant_id'     => 'tenant-uuid-1',
            'workflow_id'   => $workflow->id,
            'document_type' => 'invoice',
            'document_id'   => 'inv-uuid-1',
            'from_state'    => 'draft',
            'to_state'      => 'approved',
            'actor_id'      => 'user-uuid-1',
        ]);
    }

    public function test_transition_throws_when_workflow_not_found(): void
    {
        $workflowRepo = Mockery::mock(WorkflowRepositoryInterface::class);
        $workflowRepo->shouldReceive('findById')->andReturn(null);

        $historyRepo = Mockery::mock(WorkflowHistoryRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new TransitionWorkflowUseCase($workflowRepo, $historyRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute([
            'tenant_id'     => 'tenant-uuid-1',
            'workflow_id'   => 'missing-id',
            'document_type' => 'invoice',
            'document_id'   => 'inv-uuid-1',
            'from_state'    => 'draft',
            'to_state'      => 'approved',
            'actor_id'      => 'user-uuid-1',
        ]);
    }
}
