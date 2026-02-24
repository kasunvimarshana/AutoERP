<?php

namespace Tests\Unit\Expense;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Expense\Application\UseCases\ApproveExpenseClaimUseCase;
use Modules\Expense\Application\UseCases\CreateExpenseClaimUseCase;
use Modules\Expense\Application\UseCases\ReimburseExpenseClaimUseCase;
use Modules\Expense\Application\UseCases\SubmitExpenseClaimUseCase;
use Modules\Expense\Domain\Contracts\ExpenseClaimLineRepositoryInterface;
use Modules\Expense\Domain\Contracts\ExpenseClaimRepositoryInterface;
use Modules\Expense\Domain\Events\ExpenseClaimApproved;
use Modules\Expense\Domain\Events\ExpenseClaimReimbursed;
use Modules\Expense\Domain\Events\ExpenseClaimSubmitted;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Expense Management use cases.
 *
 * Covers expense claim creation with BCMath totals, submission, approval,
 * reimbursement status guards, and domain event dispatch.
 */
class ExpenseUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeClaim(string $status = 'draft'): object
    {
        return (object) [
            'id'           => 'claim-uuid-1',
            'tenant_id'    => 'tenant-uuid-1',
            'employee_id'  => 'emp-uuid-1',
            'title'        => 'Q1 Travel Expenses',
            'total_amount' => '250.00000000',
            'status'       => $status,
        ];
    }

    // -------------------------------------------------------------------------
    // CreateExpenseClaimUseCase
    // -------------------------------------------------------------------------

    public function test_creates_expense_claim_with_bcmath_total(): void
    {
        $claim = $this->makeClaim('draft');

        $claimRepo = Mockery::mock(ExpenseClaimRepositoryInterface::class);
        $claimRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) =>
                $data['status'] === 'draft' &&
                $data['total_amount'] === '250.00000000'
            )
            ->andReturn($claim);

        $lineRepo = Mockery::mock(ExpenseClaimLineRepositoryInterface::class);
        $lineRepo->shouldReceive('create')->twice()->andReturn((object) []);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CreateExpenseClaimUseCase($claimRepo, $lineRepo);
        $result  = $useCase->execute([
            'tenant_id'   => 'tenant-uuid-1',
            'employee_id' => 'emp-uuid-1',
            'title'       => 'Q1 Travel Expenses',
            'lines'       => [
                ['description' => 'Flight', 'expense_date' => '2026-01-10', 'amount' => '150'],
                ['description' => 'Hotel',  'expense_date' => '2026-01-11', 'amount' => '100'],
            ],
        ]);

        $this->assertSame('draft', $result->status);
        $this->assertSame('250.00000000', $result->total_amount);
    }

    // -------------------------------------------------------------------------
    // SubmitExpenseClaimUseCase
    // -------------------------------------------------------------------------

    public function test_submit_throws_when_claim_not_found(): void
    {
        $claimRepo = Mockery::mock(ExpenseClaimRepositoryInterface::class);
        $claimRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new SubmitExpenseClaimUseCase($claimRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_submit_throws_when_not_draft(): void
    {
        $claimRepo = Mockery::mock(ExpenseClaimRepositoryInterface::class);
        $claimRepo->shouldReceive('findById')->andReturn($this->makeClaim('submitted'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new SubmitExpenseClaimUseCase($claimRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/draft/i');

        $useCase->execute('claim-uuid-1');
    }

    public function test_submit_transitions_to_submitted_and_dispatches_event(): void
    {
        $claim     = $this->makeClaim('draft');
        $submitted = (object) array_merge((array) $claim, ['status' => 'submitted']);

        $claimRepo = Mockery::mock(ExpenseClaimRepositoryInterface::class);
        $claimRepo->shouldReceive('findById')->andReturn($claim);
        $claimRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'submitted')
            ->once()
            ->andReturn($submitted);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof ExpenseClaimSubmitted);

        $useCase = new SubmitExpenseClaimUseCase($claimRepo);
        $result  = $useCase->execute('claim-uuid-1');

        $this->assertSame('submitted', $result->status);
    }

    // -------------------------------------------------------------------------
    // ApproveExpenseClaimUseCase
    // -------------------------------------------------------------------------

    public function test_approve_throws_when_not_submitted(): void
    {
        $claimRepo = Mockery::mock(ExpenseClaimRepositoryInterface::class);
        $claimRepo->shouldReceive('findById')->andReturn($this->makeClaim('draft'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new ApproveExpenseClaimUseCase($claimRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/submitted/i');

        $useCase->execute('claim-uuid-1', 'approver-uuid-1');
    }

    public function test_approve_transitions_to_approved_and_dispatches_event(): void
    {
        $claim    = $this->makeClaim('submitted');
        $approved = (object) array_merge((array) $claim, ['status' => 'approved']);

        $claimRepo = Mockery::mock(ExpenseClaimRepositoryInterface::class);
        $claimRepo->shouldReceive('findById')->andReturn($claim);
        $claimRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'approved')
            ->once()
            ->andReturn($approved);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof ExpenseClaimApproved
                && $event->approverId === 'approver-uuid-1');

        $useCase = new ApproveExpenseClaimUseCase($claimRepo);
        $result  = $useCase->execute('claim-uuid-1', 'approver-uuid-1');

        $this->assertSame('approved', $result->status);
    }

    // -------------------------------------------------------------------------
    // ReimburseExpenseClaimUseCase
    // -------------------------------------------------------------------------

    public function test_reimburse_throws_when_not_approved(): void
    {
        $claimRepo = Mockery::mock(ExpenseClaimRepositoryInterface::class);
        $claimRepo->shouldReceive('findById')->andReturn($this->makeClaim('submitted'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new ReimburseExpenseClaimUseCase($claimRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/approved/i');

        $useCase->execute('claim-uuid-1');
    }

    public function test_reimburse_transitions_to_reimbursed_and_dispatches_event(): void
    {
        $claim       = $this->makeClaim('approved');
        $reimbursed  = (object) array_merge((array) $claim, ['status' => 'reimbursed']);

        $claimRepo = Mockery::mock(ExpenseClaimRepositoryInterface::class);
        $claimRepo->shouldReceive('findById')->andReturn($claim);
        $claimRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'reimbursed')
            ->once()
            ->andReturn($reimbursed);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof ExpenseClaimReimbursed);

        $useCase = new ReimburseExpenseClaimUseCase($claimRepo);
        $result  = $useCase->execute('claim-uuid-1');

        $this->assertSame('reimbursed', $result->status);
    }
}
