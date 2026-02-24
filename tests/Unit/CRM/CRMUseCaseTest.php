<?php

namespace Tests\Unit\CRM;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\CRM\Application\UseCases\CompleteActivityUseCase;
use Modules\CRM\Application\UseCases\ConvertLeadUseCase;
use Modules\CRM\Application\UseCases\CreateActivityUseCase;
use Modules\CRM\Application\UseCases\CreateLeadUseCase;
use Modules\CRM\Application\UseCases\UpdateOpportunityStageUseCase;
use Modules\CRM\Domain\Contracts\ActivityRepositoryInterface;
use Modules\CRM\Domain\Contracts\LeadRepositoryInterface;
use Modules\CRM\Domain\Contracts\OpportunityRepositoryInterface;
use Modules\CRM\Domain\Enums\LeadStatus;
use Modules\CRM\Domain\Events\ActivityCompleted;
use Modules\CRM\Domain\Events\LeadConverted;
use Modules\CRM\Domain\Events\LeadCreated;
use Modules\CRM\Domain\Events\OpportunityStageChanged;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CRM module use cases.
 *
 * Covers lead creation and conversion lifecycle, activity tracking,
 * and opportunity stage updates with domain event dispatch.
 */
class CRMUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeLead(string $status = 'new'): object
    {
        return (object) [
            'id'          => 'lead-uuid-1',
            'tenant_id'   => 'tenant-uuid-1',
            'name'        => 'Acme Corp',
            'email'       => 'contact@acme.com',
            'status'      => $status,
            'assigned_to' => 'user-uuid-1',
        ];
    }

    private function makeOpportunity(string $stage = 'prospecting'): object
    {
        return (object) [
            'id'    => 'opp-uuid-1',
            'stage' => $stage,
        ];
    }

    private function makeActivity(string $status = 'planned'): object
    {
        return (object) [
            'id'     => 'activity-uuid-1',
            'status' => $status,
        ];
    }

    // -------------------------------------------------------------------------
    // CreateLeadUseCase
    // -------------------------------------------------------------------------

    public function test_create_lead_persists_and_dispatches_event(): void
    {
        $lead     = $this->makeLead();
        $leadRepo = Mockery::mock(LeadRepositoryInterface::class);

        $leadRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['name'] === 'Acme Corp')
            ->andReturn($lead);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof LeadCreated
                && $event->leadId === 'lead-uuid-1');

        $useCase = new CreateLeadUseCase($leadRepo);
        $result  = $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'name'      => 'Acme Corp',
            'email'     => 'contact@acme.com',
        ]);

        $this->assertSame('lead-uuid-1', $result->id);
    }

    // -------------------------------------------------------------------------
    // ConvertLeadUseCase
    // -------------------------------------------------------------------------

    public function test_convert_lead_throws_when_lead_not_found(): void
    {
        $leadRepo  = Mockery::mock(LeadRepositoryInterface::class);
        $oppRepo   = Mockery::mock(OpportunityRepositoryInterface::class);

        $leadRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ConvertLeadUseCase($leadRepo, $oppRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute(['lead_id' => 'missing-id']);
    }

    public function test_convert_lead_throws_when_already_converted(): void
    {
        $leadRepo  = Mockery::mock(LeadRepositoryInterface::class);
        $oppRepo   = Mockery::mock(OpportunityRepositoryInterface::class);

        $leadRepo->shouldReceive('findById')
            ->andReturn($this->makeLead(LeadStatus::Converted->value));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ConvertLeadUseCase($leadRepo, $oppRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/already converted/i');

        $useCase->execute(['lead_id' => 'lead-uuid-1']);
    }

    public function test_convert_lead_creates_opportunity_and_dispatches_event(): void
    {
        $lead        = $this->makeLead();
        $opportunity = $this->makeOpportunity();

        $leadRepo = Mockery::mock(LeadRepositoryInterface::class);
        $oppRepo  = Mockery::mock(OpportunityRepositoryInterface::class);

        $leadRepo->shouldReceive('findById')->andReturn($lead);
        $oppRepo->shouldReceive('create')->once()->andReturn($opportunity);
        $leadRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $data['status'] === LeadStatus::Converted->value);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof LeadConverted
                && $event->leadId === 'lead-uuid-1'
                && $event->opportunityId === 'opp-uuid-1');

        $useCase = new ConvertLeadUseCase($leadRepo, $oppRepo);
        $result  = $useCase->execute(['lead_id' => 'lead-uuid-1', 'title' => 'New Deal']);

        $this->assertSame('opp-uuid-1', $result->id);
    }

    // -------------------------------------------------------------------------
    // UpdateOpportunityStageUseCase
    // -------------------------------------------------------------------------

    public function test_update_opportunity_stage_throws_when_not_found(): void
    {
        $oppRepo = Mockery::mock(OpportunityRepositoryInterface::class);
        $oppRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new UpdateOpportunityStageUseCase($oppRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id', 'qualified');
    }

    public function test_update_opportunity_stage_transitions_and_dispatches_event(): void
    {
        $opp     = $this->makeOpportunity('prospecting');
        $updated = (object) ['id' => 'opp-uuid-1', 'stage' => 'qualified'];

        $oppRepo = Mockery::mock(OpportunityRepositoryInterface::class);
        $oppRepo->shouldReceive('findById')->andReturn($opp);
        $oppRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $data['stage'] === 'qualified')
            ->andReturn($updated);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof OpportunityStageChanged
                && $event->opportunityId === 'opp-uuid-1'
                && $event->fromStage === 'prospecting'
                && $event->toStage === 'qualified');

        $useCase = new UpdateOpportunityStageUseCase($oppRepo);
        $result  = $useCase->execute('opp-uuid-1', 'qualified');

        $this->assertSame('qualified', $result->stage);
    }

    // -------------------------------------------------------------------------
    // CreateActivityUseCase
    // -------------------------------------------------------------------------

    public function test_create_activity_persists_record(): void
    {
        $activity     = $this->makeActivity();
        $activityRepo = Mockery::mock(ActivityRepositoryInterface::class);

        $activityRepo->shouldReceive('create')
            ->once()
            ->andReturn($activity);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CreateActivityUseCase($activityRepo);
        $result  = $useCase->execute([
            'tenant_id'    => 'tenant-uuid-1',
            'type'         => 'call',
            'subject'      => 'Follow-up call',
            'due_date'     => '2026-03-01',
            'assigned_to'  => 'user-uuid-1',
        ]);

        $this->assertSame('activity-uuid-1', $result->id);
    }

    // -------------------------------------------------------------------------
    // CompleteActivityUseCase
    // -------------------------------------------------------------------------

    public function test_complete_activity_throws_when_not_found(): void
    {
        $activityRepo = Mockery::mock(ActivityRepositoryInterface::class);
        $activityRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CompleteActivityUseCase($activityRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_complete_activity_transitions_to_done_and_dispatches_event(): void
    {
        $activity = $this->makeActivity('planned');
        $done     = (object) array_merge((array) $activity, ['status' => 'done']);

        $activityRepo = Mockery::mock(ActivityRepositoryInterface::class);
        $activityRepo->shouldReceive('findById')->andReturn($activity);
        $activityRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $data['status'] === 'done')
            ->andReturn($done);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof ActivityCompleted
                && $event->activityId === 'activity-uuid-1');

        $useCase = new CompleteActivityUseCase($activityRepo);
        $result  = $useCase->execute('activity-uuid-1');

        $this->assertSame('done', $result->status);
    }
}
