<?php

namespace Tests\Unit\HR;

use Mockery;
use Modules\HR\Application\Listeners\HandleApplicantHiredListener;
use Modules\HR\Application\UseCases\CreateEmployeeUseCase;
use Modules\HR\Domain\Contracts\EmployeeRepositoryInterface;
use Modules\Recruitment\Domain\Events\ApplicantHired;
use PHPUnit\Framework\TestCase;


class ApplicantHiredEmployeeCreationListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeEvent(
        string $applicationId = 'app-1',
        string $tenantId      = 'tenant-1',
        string $positionId    = 'pos-1',
        string $reviewerId    = 'rev-1',
        string $candidateName = 'Jane Doe',
        string $email         = 'jane.doe@example.com',
        string $phone         = '+1234567890',
    ): ApplicantHired {
        return new ApplicantHired(
            applicationId: $applicationId,
            tenantId:      $tenantId,
            positionId:    $positionId,
            reviewerId:    $reviewerId,
            candidateName: $candidateName,
            email:         $email,
            phone:         $phone,
        );
    }

    private function makeListener(
        CreateEmployeeUseCase $useCase,
        EmployeeRepositoryInterface $employeeRepo,
    ): HandleApplicantHiredListener {
        return new HandleApplicantHiredListener($useCase, $employeeRepo);
    }

    // -------------------------------------------------------------------------
    // Guard: skip when tenantId is empty
    // -------------------------------------------------------------------------

    public function test_skips_when_tenant_id_empty(): void
    {
        $useCase      = Mockery::mock(CreateEmployeeUseCase::class);
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);

        $useCase->shouldNotReceive('execute');
        $employeeRepo->shouldNotReceive('findByEmail');

        $event = $this->makeEvent(tenantId: '');

        $this->makeListener($useCase, $employeeRepo)->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip when candidateName is empty
    // -------------------------------------------------------------------------

    public function test_skips_when_candidate_name_empty(): void
    {
        $useCase      = Mockery::mock(CreateEmployeeUseCase::class);
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);

        $useCase->shouldNotReceive('execute');
        $employeeRepo->shouldNotReceive('findByEmail');

        $event = $this->makeEvent(candidateName: '');

        $this->makeListener($useCase, $employeeRepo)->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip when email is empty
    // -------------------------------------------------------------------------

    public function test_skips_when_email_empty(): void
    {
        $useCase      = Mockery::mock(CreateEmployeeUseCase::class);
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);

        $useCase->shouldNotReceive('execute');
        $employeeRepo->shouldNotReceive('findByEmail');

        $event = $this->makeEvent(email: '');

        $this->makeListener($useCase, $employeeRepo)->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip when employee with same email already exists (idempotency)
    // -------------------------------------------------------------------------

    public function test_skips_when_employee_with_email_already_exists(): void
    {
        $useCase      = Mockery::mock(CreateEmployeeUseCase::class);
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);

        $employeeRepo->shouldReceive('findByEmail')
            ->with('tenant-1', 'jane.doe@example.com')
            ->once()
            ->andReturn((object) ['id' => 'emp-existing']);

        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent();

        $this->makeListener($useCase, $employeeRepo)->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: creates employee with correct fields
    // -------------------------------------------------------------------------

    public function test_creates_employee_for_hired_applicant(): void
    {
        $useCase      = Mockery::mock(CreateEmployeeUseCase::class);
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);

        $employeeRepo->shouldReceive('findByEmail')
            ->with('tenant-1', 'jane.doe@example.com')
            ->once()
            ->andReturn(null);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['tenant_id']  === 'tenant-1'
                    && $data['first_name'] === 'Jane'
                    && $data['last_name']  === 'Doe'
                    && $data['email']      === 'jane.doe@example.com'
                    && $data['phone']      === '+1234567890'
                    && $data['status']     === 'active';
            }))
            ->andReturn((object) ['id' => 'emp-new']);

        $event = $this->makeEvent();

        $this->makeListener($useCase, $employeeRepo)->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Name parsing: single-word name uses full name as both first and last
    // -------------------------------------------------------------------------

    public function test_single_word_name_uses_full_name_as_last_name(): void
    {
        $useCase      = Mockery::mock(CreateEmployeeUseCase::class);
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);

        $employeeRepo->shouldReceive('findByEmail')->andReturn(null);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['first_name'] === 'Madonna'
                    && $data['last_name']  === 'Madonna';
            }))
            ->andReturn((object) ['id' => 'emp-new']);

        $event = $this->makeEvent(candidateName: 'Madonna');

        $this->makeListener($useCase, $employeeRepo)->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Name parsing: three-word name has first token as first_name, rest as last_name
    // -------------------------------------------------------------------------

    public function test_three_word_name_first_token_is_first_name(): void
    {
        $useCase      = Mockery::mock(CreateEmployeeUseCase::class);
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);

        $employeeRepo->shouldReceive('findByEmail')->andReturn(null);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['first_name'] === 'Mary'
                    && $data['last_name']  === 'Jane Watson';
            }))
            ->andReturn((object) ['id' => 'emp-new']);

        $event = $this->makeEvent(candidateName: 'Mary Jane Watson');

        $this->makeListener($useCase, $employeeRepo)->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Phone is optional: passes empty phone string correctly
    // -------------------------------------------------------------------------

    public function test_creates_employee_without_phone(): void
    {
        $useCase      = Mockery::mock(CreateEmployeeUseCase::class);
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);

        $employeeRepo->shouldReceive('findByEmail')->andReturn(null);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['phone'] === '';
            }))
            ->andReturn((object) ['id' => 'emp-new']);

        $event = $this->makeEvent(phone: '');

        $this->makeListener($useCase, $employeeRepo)->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Graceful degradation: CreateEmployeeUseCase throws DomainException
    // -------------------------------------------------------------------------

    public function test_graceful_degradation_on_domain_exception(): void
    {
        $useCase      = Mockery::mock(CreateEmployeeUseCase::class);
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);

        $employeeRepo->shouldReceive('findByEmail')->andReturn(null);

        $useCase->shouldReceive('execute')
            ->once()
            ->andThrow(new \DomainException('Employee creation failed'));

        $event = $this->makeEvent();

        // Must not throw — graceful degradation.
        $this->makeListener($useCase, $employeeRepo)->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Graceful degradation: CreateEmployeeUseCase throws RuntimeException
    // -------------------------------------------------------------------------

    public function test_graceful_degradation_on_runtime_exception(): void
    {
        $useCase      = Mockery::mock(CreateEmployeeUseCase::class);
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);

        $employeeRepo->shouldReceive('findByEmail')->andReturn(null);

        $useCase->shouldReceive('execute')
            ->once()
            ->andThrow(new \RuntimeException('DB connection lost'));

        $event = $this->makeEvent();

        // Must not throw — graceful degradation.
        $this->makeListener($useCase, $employeeRepo)->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Backwards compatibility: event without optional fields (defaults)
    // -------------------------------------------------------------------------

    public function test_event_defaults_when_optional_fields_not_provided(): void
    {
        // Backwards-compatible construction with only positional required args.
        $event = new ApplicantHired(
            applicationId: 'app-legacy',
            tenantId:      'tenant-legacy',
            positionId:    'pos-legacy',
            reviewerId:    'rev-legacy',
        );

        $this->assertSame('', $event->candidateName);
        $this->assertSame('', $event->email);
        $this->assertSame('', $event->phone);
    }

    // -------------------------------------------------------------------------
    // Event carries enriched fields when provided
    // -------------------------------------------------------------------------

    public function test_event_carries_enriched_fields(): void
    {
        $event = $this->makeEvent(
            candidateName: 'John Smith',
            email:         'john@example.com',
            phone:         '+9876543210',
        );

        $this->assertSame('John Smith', $event->candidateName);
        $this->assertSame('john@example.com', $event->email);
        $this->assertSame('+9876543210', $event->phone);
    }

    // -------------------------------------------------------------------------
    // Hire date is set to today's date string
    // -------------------------------------------------------------------------

    public function test_hire_date_is_set_in_created_employee(): void
    {
        $useCase      = Mockery::mock(CreateEmployeeUseCase::class);
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);

        $employeeRepo->shouldReceive('findByEmail')->andReturn(null);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return isset($data['hire_date'])
                    && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['hire_date']) === 1;
            }))
            ->andReturn((object) ['id' => 'emp-new']);

        $event = $this->makeEvent();

        $this->makeListener($useCase, $employeeRepo)->handle($event);

        $this->addToAssertionCount(1);
    }
}
