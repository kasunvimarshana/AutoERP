<?php

namespace Tests\Unit\Accounting;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Accounting\Application\UseCases\RecordPaymentUseCase;
use Modules\Accounting\Domain\Contracts\InvoiceRepositoryInterface;
use Modules\Accounting\Domain\Events\PaymentRecorded;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RecordPaymentUseCase.
 *
 * Verifies BCMath-based payment logic, status transitions, and guard clauses.
 */
class RecordPaymentUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeInvoice(string $total, string $amountPaid, string $status): object
    {
        return (object) [
            'id'          => 'inv-uuid-1',
            'total'       => $total,
            'amount_paid' => $amountPaid,
            'amount_due'  => bcsub($total, $amountPaid, 8),
            'status'      => $status,
        ];
    }

    public function test_throws_when_invoice_not_found(): void
    {
        $repo = Mockery::mock(InvoiceRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn(null);

        $useCase = new RecordPaymentUseCase($repo);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $this->expectException(ModelNotFoundException::class);
        $useCase->execute(['invoice_id' => 'missing', 'amount' => '100']);
    }

    public function test_throws_when_invoice_is_already_paid(): void
    {
        $invoice = $this->makeInvoice('500.00000000', '500.00000000', 'paid');

        $repo = Mockery::mock(InvoiceRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($invoice);

        $useCase = new RecordPaymentUseCase($repo);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $this->expectException(DomainException::class);
        $useCase->execute(['invoice_id' => 'inv-uuid-1', 'amount' => '50']);
    }

    public function test_throws_when_payment_exceeds_amount_due(): void
    {
        $invoice = $this->makeInvoice('500.00000000', '0.00000000', 'sent');

        $repo = Mockery::mock(InvoiceRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($invoice);

        $useCase = new RecordPaymentUseCase($repo);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/exceeds/i');
        $useCase->execute(['invoice_id' => 'inv-uuid-1', 'amount' => '600']);
    }

    public function test_partial_payment_keeps_status_unchanged(): void
    {
        $invoice = $this->makeInvoice('500.00000000', '0.00000000', 'sent');

        $updated = (object) [
            'id'          => 'inv-uuid-1',
            'amount_paid' => '200.00000000',
            'amount_due'  => '300.00000000',
            'status'      => 'sent',
        ];

        $repo = Mockery::mock(InvoiceRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($invoice);
        $repo->shouldReceive('update')
            ->with('inv-uuid-1', Mockery::on(fn ($d) => $d['status'] === 'sent'))
            ->andReturn($updated);

        $useCase = new RecordPaymentUseCase($repo);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof PaymentRecorded);

        $result = $useCase->execute(['invoice_id' => 'inv-uuid-1', 'amount' => '200']);
        $this->assertSame('sent', $result->status);
    }

    public function test_full_payment_transitions_status_to_paid(): void
    {
        $invoice = $this->makeInvoice('500.00000000', '0.00000000', 'sent');

        $updated = (object) [
            'id'          => 'inv-uuid-1',
            'amount_paid' => '500.00000000',
            'amount_due'  => '0.00000000',
            'status'      => 'paid',
        ];

        $repo = Mockery::mock(InvoiceRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($invoice);
        $repo->shouldReceive('update')
            ->with('inv-uuid-1', Mockery::on(fn ($d) => $d['status'] === 'paid'))
            ->andReturn($updated);

        $useCase = new RecordPaymentUseCase($repo);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof PaymentRecorded);

        $result = $useCase->execute(['invoice_id' => 'inv-uuid-1', 'amount' => '500']);
        $this->assertSame('paid', $result->status);
    }
}
