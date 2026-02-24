<?php

namespace Tests\Unit\Accounting;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Accounting\Application\UseCases\CloseAccountingPeriodUseCase;
use Modules\Accounting\Application\UseCases\CreateAccountingPeriodUseCase;
use Modules\Accounting\Application\UseCases\LockAccountingPeriodUseCase;
use Modules\Accounting\Application\UseCases\PostJournalEntryUseCase;
use Modules\Accounting\Domain\Contracts\AccountingPeriodRepositoryInterface;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\Events\AccountingPeriodClosed;
use Modules\Accounting\Domain\Events\AccountingPeriodCreated;
use Modules\Accounting\Domain\Events\AccountingPeriodLocked;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Accounting Period Management use cases.
 *
 * Covers:
 *  - CreateAccountingPeriodUseCase: validation guards, overlap guard, success + event
 *  - CloseAccountingPeriodUseCase: not-found, already-closed, locked guards, success + event
 *  - LockAccountingPeriodUseCase: not-found, already-locked, must-be-closed guards, success + event
 *  - PostJournalEntryUseCase: rejects posting into a closed/locked period
 */
class AccountingPeriodUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // CreateAccountingPeriodUseCase
    // -----------------------------------------------------------------------

    public function test_create_throws_when_name_is_empty(): void
    {
        $repo = Mockery::mock(AccountingPeriodRepositoryInterface::class);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateAccountingPeriodUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Period name is required.');
        $useCase->execute(['name' => '  ', 'start_date' => '2025-01-01', 'end_date' => '2025-03-31', 'tenant_id' => 't1']);
    }

    public function test_create_throws_when_end_date_not_after_start_date(): void
    {
        $repo = Mockery::mock(AccountingPeriodRepositoryInterface::class);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateAccountingPeriodUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('End date must be after start date.');
        $useCase->execute(['name' => 'Q1 2025', 'start_date' => '2025-03-31', 'end_date' => '2025-01-01', 'tenant_id' => 't1']);
    }

    public function test_create_throws_when_period_overlaps(): void
    {
        $repo = Mockery::mock(AccountingPeriodRepositoryInterface::class);
        $repo->shouldReceive('hasOverlap')->with('t1', '2025-01-01', '2025-03-31')->andReturn(true);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateAccountingPeriodUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('overlaps');
        $useCase->execute(['name' => 'Q1 2025', 'start_date' => '2025-01-01', 'end_date' => '2025-03-31', 'tenant_id' => 't1']);
    }

    public function test_create_succeeds_and_dispatches_event(): void
    {
        $created = (object) [
            'id'         => 'p-uuid-1',
            'tenant_id'  => 't1',
            'name'       => 'Q1 2025',
            'start_date' => '2025-01-01',
            'end_date'   => '2025-03-31',
            'status'     => 'draft',
        ];

        $repo = Mockery::mock(AccountingPeriodRepositoryInterface::class);
        $repo->shouldReceive('hasOverlap')->andReturn(false);
        $repo->shouldReceive('create')->once()->andReturn($created);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once()->with(Mockery::type(AccountingPeriodCreated::class));

        $useCase = new CreateAccountingPeriodUseCase($repo);
        $result  = $useCase->execute(['name' => 'Q1 2025', 'start_date' => '2025-01-01', 'end_date' => '2025-03-31', 'tenant_id' => 't1']);

        $this->assertSame('p-uuid-1', $result->id);
        $this->assertSame('draft', $result->status);
    }

    // -----------------------------------------------------------------------
    // CloseAccountingPeriodUseCase
    // -----------------------------------------------------------------------

    public function test_close_throws_when_period_not_found(): void
    {
        $repo = Mockery::mock(AccountingPeriodRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('missing')->andReturn(null);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CloseAccountingPeriodUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Accounting period not found.');
        $useCase->execute(['id' => 'missing']);
    }

    public function test_close_throws_when_already_closed(): void
    {
        $period = (object) ['id' => 'p1', 'tenant_id' => 't1', 'status' => 'closed'];
        $repo   = Mockery::mock(AccountingPeriodRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($period);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CloseAccountingPeriodUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('already closed');
        $useCase->execute(['id' => 'p1']);
    }

    public function test_close_throws_when_period_is_locked(): void
    {
        $period = (object) ['id' => 'p1', 'tenant_id' => 't1', 'status' => 'locked'];
        $repo   = Mockery::mock(AccountingPeriodRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($period);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CloseAccountingPeriodUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot close a locked');
        $useCase->execute(['id' => 'p1']);
    }

    public function test_close_succeeds_and_dispatches_event(): void
    {
        $period  = (object) ['id' => 'p1', 'tenant_id' => 't1', 'status' => 'open'];
        $updated = (object) ['id' => 'p1', 'tenant_id' => 't1', 'status' => 'closed'];

        $repo = Mockery::mock(AccountingPeriodRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($period);
        $repo->shouldReceive('update')->once()->andReturn($updated);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once()->with(Mockery::type(AccountingPeriodClosed::class));

        $useCase = new CloseAccountingPeriodUseCase($repo);
        $result  = $useCase->execute(['id' => 'p1', 'closed_by' => 'user-1']);

        $this->assertSame('closed', $result->status);
    }

    // -----------------------------------------------------------------------
    // LockAccountingPeriodUseCase
    // -----------------------------------------------------------------------

    public function test_lock_throws_when_period_not_found(): void
    {
        $repo = Mockery::mock(AccountingPeriodRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn(null);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new LockAccountingPeriodUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Accounting period not found.');
        $useCase->execute(['id' => 'missing']);
    }

    public function test_lock_throws_when_already_locked(): void
    {
        $period = (object) ['id' => 'p1', 'tenant_id' => 't1', 'status' => 'locked'];
        $repo   = Mockery::mock(AccountingPeriodRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($period);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new LockAccountingPeriodUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('already locked');
        $useCase->execute(['id' => 'p1']);
    }

    public function test_lock_throws_when_not_closed(): void
    {
        $period = (object) ['id' => 'p1', 'tenant_id' => 't1', 'status' => 'open'];
        $repo   = Mockery::mock(AccountingPeriodRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($period);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new LockAccountingPeriodUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Only a closed');
        $useCase->execute(['id' => 'p1']);
    }

    public function test_lock_succeeds_and_dispatches_event(): void
    {
        $period  = (object) ['id' => 'p1', 'tenant_id' => 't1', 'status' => 'closed', 'closed_at' => '2025-04-01 00:00:00'];
        $updated = (object) ['id' => 'p1', 'tenant_id' => 't1', 'status' => 'locked'];

        $repo = Mockery::mock(AccountingPeriodRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($period);
        $repo->shouldReceive('update')->once()->andReturn($updated);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once()->with(Mockery::type(AccountingPeriodLocked::class));

        $useCase = new LockAccountingPeriodUseCase($repo);
        $result  = $useCase->execute(['id' => 'p1', 'locked_by' => 'admin-1']);

        $this->assertSame('locked', $result->status);
    }

    // -----------------------------------------------------------------------
    // PostJournalEntryUseCase — period guard
    // -----------------------------------------------------------------------

    public function test_post_throws_when_entry_dated_in_closed_period(): void
    {
        $lines = [
            (object) ['debit' => '100.00000000', 'credit' => '0.00000000'],
            (object) ['debit' => '0.00000000', 'credit' => '100.00000000'],
        ];
        $entry = (object) [
            'id'         => 'je-1',
            'tenant_id'  => 't1',
            'entry_date' => '2025-02-15',
            'status'     => 'draft',
            'lines'      => $lines,
        ];
        $closedPeriod = (object) ['id' => 'p1', 'name' => 'Q1 2025', 'status' => 'closed'];

        $journalRepo = Mockery::mock(JournalEntryRepositoryInterface::class);
        $journalRepo->shouldReceive('findById')->andReturn($entry);

        $periodRepo = Mockery::mock(AccountingPeriodRepositoryInterface::class);
        $periodRepo->shouldReceive('findByDate')->with('t1', '2025-02-15')->andReturn($closedPeriod);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new PostJournalEntryUseCase($journalRepo, $periodRepo);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('closed accounting period');
        $useCase->execute(['id' => 'je-1']);
    }
}
