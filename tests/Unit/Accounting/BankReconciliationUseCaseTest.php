<?php

namespace Tests\Unit\Accounting;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Accounting\Application\UseCases\CreateBankAccountUseCase;
use Modules\Accounting\Application\UseCases\RecordBankTransactionUseCase;
use Modules\Accounting\Application\UseCases\ReconcileBankTransactionUseCase;
use Modules\Accounting\Domain\Contracts\BankAccountRepositoryInterface;
use Modules\Accounting\Domain\Contracts\BankTransactionRepositoryInterface;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\Events\BankAccountCreated;
use Modules\Accounting\Domain\Events\BankTransactionRecorded;
use Modules\Accounting\Domain\Events\BankTransactionReconciled;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Bank Account Management & Reconciliation use cases.
 *
 * Covers:
 *  - CreateBankAccountUseCase: validation guards, successful creation + event
 *  - RecordBankTransactionUseCase: inactive account guard, zero-amount guard, success + event
 *  - ReconcileBankTransactionUseCase: not-found guard, already-reconciled guard, draft JE guard, success + event
 */
class BankReconciliationUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // CreateBankAccountUseCase
    // -----------------------------------------------------------------------

    public function test_create_bank_account_throws_when_name_is_empty(): void
    {
        $repo = Mockery::mock(BankAccountRepositoryInterface::class);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateBankAccountUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Bank account name is required.');
        $useCase->execute(['name' => '   ', 'account_number' => '123', 'bank_name' => 'Test']);
    }

    public function test_create_bank_account_throws_when_account_number_is_empty(): void
    {
        $repo = Mockery::mock(BankAccountRepositoryInterface::class);
        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateBankAccountUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Account number is required.');
        $useCase->execute(['name' => 'Main Account', 'account_number' => '', 'bank_name' => 'Test']);
    }

    public function test_create_bank_account_succeeds_and_dispatches_event(): void
    {
        $created = (object) [
            'id'        => 'ba-uuid-1',
            'name'      => 'Main USD Account',
            'tenant_id' => 'tenant-1',
        ];

        $repo = Mockery::mock(BankAccountRepositoryInterface::class);
        $repo->shouldReceive('create')->once()->andReturn($created);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once()->with(Mockery::type(BankAccountCreated::class));

        $useCase = new CreateBankAccountUseCase($repo);
        $result = $useCase->execute([
            'name'           => 'Main USD Account',
            'account_number' => '001-123456',
            'bank_name'      => 'First National Bank',
            'currency'       => 'USD',
            'tenant_id'      => 'tenant-1',
        ]);

        $this->assertSame('ba-uuid-1', $result->id);
        $this->assertSame('Main USD Account', $result->name);
    }

    // -----------------------------------------------------------------------
    // RecordBankTransactionUseCase
    // -----------------------------------------------------------------------

    public function test_record_transaction_throws_when_bank_account_not_found(): void
    {
        $bankAccountRepo = Mockery::mock(BankAccountRepositoryInterface::class);
        $bankAccountRepo->shouldReceive('findById')->andReturn(null);
        $transactionRepo = Mockery::mock(BankTransactionRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new RecordBankTransactionUseCase($bankAccountRepo, $transactionRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Bank account not found.');
        $useCase->execute(['bank_account_id' => 'missing', 'amount' => '100', 'type' => 'credit', 'transaction_date' => '2024-01-15', 'description' => 'Deposit']);
    }

    public function test_record_transaction_throws_when_bank_account_is_inactive(): void
    {
        $bankAccount = (object) ['id' => 'ba-1', 'is_active' => false];
        $bankAccountRepo = Mockery::mock(BankAccountRepositoryInterface::class);
        $bankAccountRepo->shouldReceive('findById')->andReturn($bankAccount);
        $transactionRepo = Mockery::mock(BankTransactionRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new RecordBankTransactionUseCase($bankAccountRepo, $transactionRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot record transactions for an inactive bank account.');
        $useCase->execute(['bank_account_id' => 'ba-1', 'amount' => '100', 'type' => 'credit', 'transaction_date' => '2024-01-15', 'description' => 'Deposit']);
    }

    public function test_record_transaction_throws_when_amount_is_zero(): void
    {
        $bankAccount = (object) ['id' => 'ba-1', 'is_active' => true];
        $bankAccountRepo = Mockery::mock(BankAccountRepositoryInterface::class);
        $bankAccountRepo->shouldReceive('findById')->andReturn($bankAccount);
        $transactionRepo = Mockery::mock(BankTransactionRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new RecordBankTransactionUseCase($bankAccountRepo, $transactionRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Transaction amount must be greater than zero.');
        $useCase->execute(['bank_account_id' => 'ba-1', 'amount' => '0', 'type' => 'credit', 'transaction_date' => '2024-01-15', 'description' => 'Deposit']);
    }

    public function test_record_transaction_succeeds_and_dispatches_event(): void
    {
        $bankAccount = (object) ['id' => 'ba-1', 'is_active' => true];
        $created = (object) [
            'id'              => 'tx-uuid-1',
            'bank_account_id' => 'ba-1',
            'type'            => 'credit',
            'amount'          => '500.00000000',
            'tenant_id'       => 'tenant-1',
        ];

        $bankAccountRepo = Mockery::mock(BankAccountRepositoryInterface::class);
        $bankAccountRepo->shouldReceive('findById')->with('ba-1')->andReturn($bankAccount);
        $transactionRepo = Mockery::mock(BankTransactionRepositoryInterface::class);
        $transactionRepo->shouldReceive('create')->once()->andReturn($created);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once()->with(Mockery::type(BankTransactionRecorded::class));

        $useCase = new RecordBankTransactionUseCase($bankAccountRepo, $transactionRepo);
        $result = $useCase->execute([
            'bank_account_id'  => 'ba-1',
            'type'             => 'credit',
            'amount'           => '500',
            'transaction_date' => '2024-01-15',
            'description'      => 'Customer payment received',
            'tenant_id'        => 'tenant-1',
        ]);

        $this->assertSame('tx-uuid-1', $result->id);
        $this->assertSame('500.00000000', $result->amount);
    }

    // -----------------------------------------------------------------------
    // ReconcileBankTransactionUseCase
    // -----------------------------------------------------------------------

    public function test_reconcile_throws_when_transaction_not_found(): void
    {
        $transactionRepo  = Mockery::mock(BankTransactionRepositoryInterface::class);
        $transactionRepo->shouldReceive('findById')->andReturn(null);
        $journalEntryRepo = Mockery::mock(JournalEntryRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new ReconcileBankTransactionUseCase($transactionRepo, $journalEntryRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Bank transaction not found.');
        $useCase->execute(['transaction_id' => 'missing', 'journal_entry_id' => 'je-1']);
    }

    public function test_reconcile_throws_when_already_reconciled(): void
    {
        $tx = (object) ['id' => 'tx-1', 'status' => 'reconciled'];
        $transactionRepo  = Mockery::mock(BankTransactionRepositoryInterface::class);
        $transactionRepo->shouldReceive('findById')->andReturn($tx);
        $journalEntryRepo = Mockery::mock(JournalEntryRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new ReconcileBankTransactionUseCase($transactionRepo, $journalEntryRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Bank transaction is already reconciled.');
        $useCase->execute(['transaction_id' => 'tx-1', 'journal_entry_id' => 'je-1']);
    }

    public function test_reconcile_throws_when_journal_entry_not_found(): void
    {
        $tx = (object) ['id' => 'tx-1', 'status' => 'unreconciled'];
        $transactionRepo  = Mockery::mock(BankTransactionRepositoryInterface::class);
        $transactionRepo->shouldReceive('findById')->andReturn($tx);
        $journalEntryRepo = Mockery::mock(JournalEntryRepositoryInterface::class);
        $journalEntryRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new ReconcileBankTransactionUseCase($transactionRepo, $journalEntryRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Journal entry not found.');
        $useCase->execute(['transaction_id' => 'tx-1', 'journal_entry_id' => 'je-missing']);
    }

    public function test_reconcile_throws_when_journal_entry_is_draft(): void
    {
        $tx = (object) ['id' => 'tx-1', 'status' => 'unreconciled'];
        $je = (object) ['id' => 'je-1', 'status' => 'draft'];
        $transactionRepo  = Mockery::mock(BankTransactionRepositoryInterface::class);
        $transactionRepo->shouldReceive('findById')->andReturn($tx);
        $journalEntryRepo = Mockery::mock(JournalEntryRepositoryInterface::class);
        $journalEntryRepo->shouldReceive('findById')->andReturn($je);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new ReconcileBankTransactionUseCase($transactionRepo, $journalEntryRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot reconcile against a draft journal entry.');
        $useCase->execute(['transaction_id' => 'tx-1', 'journal_entry_id' => 'je-1']);
    }

    public function test_reconcile_succeeds_and_dispatches_event(): void
    {
        $tx = (object) ['id' => 'tx-1', 'status' => 'unreconciled', 'tenant_id' => 'tenant-1'];
        $je = (object) ['id' => 'je-1', 'status' => 'posted'];
        $updated = (object) ['id' => 'tx-1', 'status' => 'reconciled', 'journal_entry_id' => 'je-1'];

        $transactionRepo  = Mockery::mock(BankTransactionRepositoryInterface::class);
        $transactionRepo->shouldReceive('findById')->with('tx-1')->andReturn($tx);
        $transactionRepo->shouldReceive('update')
            ->once()
            ->with('tx-1', ['status' => 'reconciled', 'journal_entry_id' => 'je-1'])
            ->andReturn($updated);

        $journalEntryRepo = Mockery::mock(JournalEntryRepositoryInterface::class);
        $journalEntryRepo->shouldReceive('findById')->with('je-1')->andReturn($je);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once()->with(Mockery::type(BankTransactionReconciled::class));

        $useCase = new ReconcileBankTransactionUseCase($transactionRepo, $journalEntryRepo);
        $result = $useCase->execute(['transaction_id' => 'tx-1', 'journal_entry_id' => 'je-1', 'tenant_id' => 'tenant-1']);

        $this->assertSame('reconciled', $result->status);
        $this->assertSame('je-1', $result->journal_entry_id);
    }
}
