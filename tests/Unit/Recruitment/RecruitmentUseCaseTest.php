<?php

namespace Tests\Unit\Recruitment;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Recruitment\Application\UseCases\CreateJobApplicationUseCase;
use Modules\Recruitment\Application\UseCases\CreateJobPositionUseCase;
use Modules\Recruitment\Application\UseCases\HireApplicantUseCase;
use Modules\Recruitment\Application\UseCases\RejectApplicantUseCase;
use Modules\Recruitment\Domain\Contracts\JobApplicationRepositoryInterface;
use Modules\Recruitment\Domain\Contracts\JobPositionRepositoryInterface;
use Modules\Recruitment\Domain\Events\ApplicantHired;
use Modules\Recruitment\Domain\Events\ApplicantRejected;
use Modules\Recruitment\Domain\Events\JobApplicationReceived;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Recruitment module use cases.
 *
 * Covers job position creation, application submission with open-position guard,
 * and hire/reject workflow with domain event dispatch.
 */
class RecruitmentUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makePosition(string $status = 'open'): object
    {
        return (object) [
            'id'        => 'pos-uuid-1',
            'tenant_id' => 'tenant-uuid-1',
            'title'     => 'Software Engineer',
            'status'    => $status,
        ];
    }

    private function makeApplication(string $status = 'new'): object
    {
        return (object) [
            'id'             => 'app-uuid-1',
            'tenant_id'      => 'tenant-uuid-1',
            'position_id'    => 'pos-uuid-1',
            'candidate_name' => 'Jane Doe',
            'email'          => 'jane@example.com',
            'status'         => $status,
        ];
    }

    // -------------------------------------------------------------------------
    // CreateJobPositionUseCase
    // -------------------------------------------------------------------------

    public function test_create_job_position_returns_open_position(): void
    {
        $position = $this->makePosition();

        $positionRepo = Mockery::mock(JobPositionRepositoryInterface::class);
        $positionRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'open' && $data['title'] === 'Software Engineer')
            ->andReturn($position);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CreateJobPositionUseCase($positionRepo);
        $result  = $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'title'     => 'Software Engineer',
        ]);

        $this->assertSame('open', $result->status);
    }

    // -------------------------------------------------------------------------
    // CreateJobApplicationUseCase
    // -------------------------------------------------------------------------

    public function test_create_application_throws_when_position_not_found(): void
    {
        $positionRepo    = Mockery::mock(JobPositionRepositoryInterface::class);
        $applicationRepo = Mockery::mock(JobApplicationRepositoryInterface::class);

        $positionRepo->shouldReceive('findById')->andReturn(null);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CreateJobApplicationUseCase($applicationRepo, $positionRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute([
            'tenant_id'      => 'tenant-uuid-1',
            'position_id'    => 'missing-pos',
            'candidate_name' => 'Jane Doe',
            'email'          => 'jane@example.com',
        ]);
    }

    public function test_create_application_throws_when_position_not_open(): void
    {
        $positionRepo    = Mockery::mock(JobPositionRepositoryInterface::class);
        $applicationRepo = Mockery::mock(JobApplicationRepositoryInterface::class);

        $positionRepo->shouldReceive('findById')->andReturn($this->makePosition('closed'));
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CreateJobApplicationUseCase($applicationRepo, $positionRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not open/i');

        $useCase->execute([
            'tenant_id'      => 'tenant-uuid-1',
            'position_id'    => 'pos-uuid-1',
            'candidate_name' => 'Jane Doe',
            'email'          => 'jane@example.com',
        ]);
    }

    public function test_create_application_creates_new_record_and_dispatches_event(): void
    {
        $application     = $this->makeApplication('new');
        $positionRepo    = Mockery::mock(JobPositionRepositoryInterface::class);
        $applicationRepo = Mockery::mock(JobApplicationRepositoryInterface::class);

        $positionRepo->shouldReceive('findById')->andReturn($this->makePosition());
        $applicationRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'new')
            ->andReturn($application);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof JobApplicationReceived
                && $event->candidateName === 'Jane Doe');

        $useCase = new CreateJobApplicationUseCase($applicationRepo, $positionRepo);
        $result  = $useCase->execute([
            'tenant_id'      => 'tenant-uuid-1',
            'position_id'    => 'pos-uuid-1',
            'candidate_name' => 'Jane Doe',
            'email'          => 'jane@example.com',
        ]);

        $this->assertSame('new', $result->status);
    }

    // -------------------------------------------------------------------------
    // HireApplicantUseCase
    // -------------------------------------------------------------------------

    public function test_hire_throws_when_application_not_found(): void
    {
        $applicationRepo = Mockery::mock(JobApplicationRepositoryInterface::class);
        $applicationRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new HireApplicantUseCase($applicationRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id', 'reviewer-uuid-1');
    }

    public function test_hire_throws_when_already_hired(): void
    {
        $applicationRepo = Mockery::mock(JobApplicationRepositoryInterface::class);
        $applicationRepo->shouldReceive('findById')->andReturn($this->makeApplication('hired'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new HireApplicantUseCase($applicationRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/already hired/i');

        $useCase->execute('app-uuid-1', 'reviewer-uuid-1');
    }

    public function test_hire_transitions_to_hired_and_dispatches_event(): void
    {
        $application = $this->makeApplication('offer');
        $hired       = (object) array_merge((array) $application, [
            'status'      => 'hired',
            'reviewer_id' => 'reviewer-uuid-1',
        ]);

        $applicationRepo = Mockery::mock(JobApplicationRepositoryInterface::class);
        $applicationRepo->shouldReceive('findById')->andReturn($application);
        $applicationRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'hired')
            ->once()
            ->andReturn($hired);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof ApplicantHired
                && $event->reviewerId === 'reviewer-uuid-1');

        $useCase = new HireApplicantUseCase($applicationRepo);
        $result  = $useCase->execute('app-uuid-1', 'reviewer-uuid-1');

        $this->assertSame('hired', $result->status);
    }

    // -------------------------------------------------------------------------
    // RejectApplicantUseCase
    // -------------------------------------------------------------------------

    public function test_reject_throws_when_application_not_found(): void
    {
        $applicationRepo = Mockery::mock(JobApplicationRepositoryInterface::class);
        $applicationRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RejectApplicantUseCase($applicationRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id', 'reviewer-uuid-1');
    }

    public function test_reject_throws_when_already_rejected(): void
    {
        $applicationRepo = Mockery::mock(JobApplicationRepositoryInterface::class);
        $applicationRepo->shouldReceive('findById')->andReturn($this->makeApplication('rejected'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RejectApplicantUseCase($applicationRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/already rejected/i');

        $useCase->execute('app-uuid-1', 'reviewer-uuid-1');
    }

    public function test_reject_transitions_to_rejected_and_dispatches_event(): void
    {
        $application = $this->makeApplication('in_review');
        $rejected    = (object) array_merge((array) $application, ['status' => 'rejected']);

        $applicationRepo = Mockery::mock(JobApplicationRepositoryInterface::class);
        $applicationRepo->shouldReceive('findById')->andReturn($application);
        $applicationRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'rejected')
            ->once()
            ->andReturn($rejected);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof ApplicantRejected
                && $event->reviewerId === 'reviewer-uuid-1');

        $useCase = new RejectApplicantUseCase($applicationRepo);
        $result  = $useCase->execute('app-uuid-1', 'reviewer-uuid-1', 'Not enough experience.');

        $this->assertSame('rejected', $result->status);
    }
}
