<?php

namespace Tests\Unit\Accounting;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Accounting\Application\UseCases\IssueCreditNoteUseCase;
use Modules\Accounting\Domain\Contracts\InvoiceRepositoryInterface;
use Modules\Accounting\Domain\Events\CreditNoteIssued;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for IssueCreditNoteUseCase.
 *
 * Covers:
 *  - Source invoice not found guard
 *  - Source invoice must be posted (sent/overdue) guard
 *  - Amount must be greater than zero guard
 *  - Amount cannot exceed source invoice total guard
 *  - Successful credit note creation with correct BCMath values + CreditNoteIssued event
 */
class IssueCreditNoteUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeInvoice(string $status, string $total = '500.00000000'): object
    {
        return (object) [
            'id'           => 'inv-uuid-1',
            'tenant_id'    => 'tenant-1',
            'number'       => 'INV-000001',
            'partner_id'   => 'partner-uuid-1',
            'partner_type' => 'customer',
            'status'       => $status,
            'total'        => $total,
            'currency'     => 'USD',
        ];
    }

    public function test_throws_when_source_invoice_not_found(): void
    {
        $repo = Mockery::mock(InvoiceRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new IssueCreditNoteUseCase($repo);

        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Source invoice not found.');
        $useCase->execute(['source_invoice_id' => 'missing', 'amount' => '100', 'tenant_id' => 'tenant-1']);
    }

    public function test_throws_when_source_invoice_is_draft(): void
    {
        $invoice = $this->makeInvoice('draft');
        $repo = Mockery::mock(InvoiceRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($invoice);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new IssueCreditNoteUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Credit notes can only be issued against posted (sent/overdue) invoices.');
        $useCase->execute(['source_invoice_id' => 'inv-uuid-1', 'amount' => '100', 'tenant_id' => 'tenant-1']);
    }

    public function test_throws_when_source_invoice_is_paid(): void
    {
        $invoice = $this->makeInvoice('paid');
        $repo = Mockery::mock(InvoiceRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($invoice);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new IssueCreditNoteUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Credit notes can only be issued against posted (sent/overdue) invoices.');
        $useCase->execute(['source_invoice_id' => 'inv-uuid-1', 'amount' => '100', 'tenant_id' => 'tenant-1']);
    }

    public function test_throws_when_amount_is_zero(): void
    {
        $invoice = $this->makeInvoice('sent');
        $repo = Mockery::mock(InvoiceRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($invoice);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new IssueCreditNoteUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Credit note amount must be greater than zero.');
        $useCase->execute(['source_invoice_id' => 'inv-uuid-1', 'amount' => '0', 'tenant_id' => 'tenant-1']);
    }

    public function test_throws_when_amount_exceeds_source_total(): void
    {
        $invoice = $this->makeInvoice('sent', '300.00000000');
        $repo = Mockery::mock(InvoiceRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($invoice);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new IssueCreditNoteUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Credit note amount cannot exceed the source invoice total.');
        $useCase->execute(['source_invoice_id' => 'inv-uuid-1', 'amount' => '350', 'tenant_id' => 'tenant-1']);
    }

    public function test_issues_credit_note_against_sent_invoice_and_dispatches_event(): void
    {
        $invoice = $this->makeInvoice('sent', '500.00000000');
        $creditNote = (object) [
            'id'                => 'cn-uuid-1',
            'tenant_id'         => 'tenant-1',
            'number'            => 'CN-2024-000001',
            'invoice_type'      => 'credit_note',
            'source_invoice_id' => 'inv-uuid-1',
            'status'            => 'draft',
            'total'             => '200.00000000',
            'currency'          => 'USD',
        ];

        $repo = Mockery::mock(InvoiceRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('inv-uuid-1')->andReturn($invoice);
        $repo->shouldReceive('nextCreditNoteNumber')->with('tenant-1')->andReturn('CN-2024-000001');
        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($d) =>
                $d['invoice_type'] === 'credit_note' &&
                $d['source_invoice_id'] === 'inv-uuid-1' &&
                $d['total'] === '200.00000000' &&
                $d['status'] === 'draft'
            ))
            ->andReturn($creditNote);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once()->with(Mockery::type(CreditNoteIssued::class));

        $useCase = new IssueCreditNoteUseCase($repo);
        $result = $useCase->execute([
            'source_invoice_id' => 'inv-uuid-1',
            'amount'            => '200',
            'tenant_id'         => 'tenant-1',
        ]);

        $this->assertSame('cn-uuid-1', $result->id);
        $this->assertSame('credit_note', $result->invoice_type);
        $this->assertSame('200.00000000', $result->total);
    }

    public function test_issues_credit_note_against_overdue_invoice(): void
    {
        $invoice = $this->makeInvoice('overdue', '1000.00000000');
        $creditNote = (object) [
            'id'                => 'cn-uuid-2',
            'tenant_id'         => 'tenant-1',
            'number'            => 'CN-2024-000002',
            'invoice_type'      => 'credit_note',
            'source_invoice_id' => 'inv-uuid-1',
            'status'            => 'draft',
            'total'             => '1000.00000000',
            'currency'          => 'USD',
        ];

        $repo = Mockery::mock(InvoiceRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('inv-uuid-1')->andReturn($invoice);
        $repo->shouldReceive('nextCreditNoteNumber')->with('tenant-1')->andReturn('CN-2024-000002');
        $repo->shouldReceive('create')->once()->andReturn($creditNote);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once()->with(Mockery::type(CreditNoteIssued::class));

        $useCase = new IssueCreditNoteUseCase($repo);
        $result = $useCase->execute([
            'source_invoice_id' => 'inv-uuid-1',
            'amount'            => '1000',
            'tenant_id'         => 'tenant-1',
        ]);

        $this->assertSame('1000.00000000', $result->total);
    }
}
