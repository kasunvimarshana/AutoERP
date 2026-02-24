<?php

namespace Tests\Unit\Accounting;

use Mockery;
use Modules\Accounting\Application\Listeners\HandleExpenseClaimReimbursedListener;
use Modules\Accounting\Application\UseCases\CreateInvoiceUseCase;
use Modules\Expense\Domain\Events\ExpenseClaimReimbursed;
use PHPUnit\Framework\TestCase;


class ExpenseClaimReimbursedVendorBillListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeEvent(
        string $claimId     = 'claim-1',
        string $tenantId    = 'tenant-1',
        string $employeeId  = 'emp-1',
        string $totalAmount = '250.00',
        string $currency    = 'USD',
    ): ExpenseClaimReimbursed {
        return new ExpenseClaimReimbursed(
            claimId:     $claimId,
            tenantId:    $tenantId,
            employeeId:  $employeeId,
            totalAmount: $totalAmount,
            currency:    $currency,
        );
    }

    // -------------------------------------------------------------------------
    // Guard: skip when tenantId is empty
    // -------------------------------------------------------------------------

    public function test_skips_when_tenant_id_empty(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(tenantId: '');

        (new HandleExpenseClaimReimbursedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip when totalAmount is zero
    // -------------------------------------------------------------------------

    public function test_skips_when_total_amount_is_zero(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(totalAmount: '0');

        (new HandleExpenseClaimReimbursedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip when totalAmount is negative
    // -------------------------------------------------------------------------

    public function test_skips_when_total_amount_is_negative(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(totalAmount: '-50.00');

        (new HandleExpenseClaimReimbursedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: creates vendor bill with correct invoice type
    // -------------------------------------------------------------------------

    public function test_creates_vendor_bill_for_expense_claim(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['invoice_type'] === 'vendor_bill'
                    && $data['partner_type'] === 'vendor'
                    && $data['tenant_id']    === 'tenant-1';
            }))
            ->andReturn((object) ['id' => 'inv-1']);

        $event = $this->makeEvent();

        (new HandleExpenseClaimReimbursedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: employee ID set as partner_id
    // -------------------------------------------------------------------------

    public function test_employee_id_is_set_as_partner_id(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['partner_id'] === 'emp-1';
            }))
            ->andReturn((object) ['id' => 'inv-1']);

        $event = $this->makeEvent(employeeId: 'emp-1');

        (new HandleExpenseClaimReimbursedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: vendor bill has single line with totalAmount as unit_price
    // -------------------------------------------------------------------------

    public function test_vendor_bill_line_has_correct_amount(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                $lines = $data['lines'] ?? [];

                return count($lines) === 1
                    && $lines[0]['quantity']   === '1'
                    && $lines[0]['unit_price'] === '250.00'
                    && $lines[0]['tax_rate']   === '0';
            }))
            ->andReturn((object) ['id' => 'inv-1']);

        $event = $this->makeEvent(totalAmount: '250.00');

        (new HandleExpenseClaimReimbursedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: notes reference claim ID
    // -------------------------------------------------------------------------

    public function test_notes_reference_claim_id(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return str_contains($data['notes'] ?? '', 'claim-99');
            }))
            ->andReturn((object) ['id' => 'inv-1']);

        $event = $this->makeEvent(claimId: 'claim-99');

        (new HandleExpenseClaimReimbursedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: currency is passed from event
    // -------------------------------------------------------------------------

    public function test_currency_is_passed_from_event(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['currency'] === 'EUR';
            }))
            ->andReturn((object) ['id' => 'inv-1']);

        $event = $this->makeEvent(currency: 'EUR');

        (new HandleExpenseClaimReimbursedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Empty employeeId: partner_id is null
    // -------------------------------------------------------------------------

    public function test_partner_id_is_null_when_employee_id_empty(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['partner_id'] === null;
            }))
            ->andReturn((object) ['id' => 'inv-1']);

        $event = $this->makeEvent(employeeId: '');

        (new HandleExpenseClaimReimbursedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Empty currency: defaults to USD
    // -------------------------------------------------------------------------

    public function test_currency_defaults_to_usd_when_empty(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['currency'] === 'USD';
            }))
            ->andReturn((object) ['id' => 'inv-1']);

        $event = $this->makeEvent(currency: '');

        (new HandleExpenseClaimReimbursedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Graceful degradation: CreateInvoiceUseCase throws
    // -------------------------------------------------------------------------

    public function test_graceful_degradation_when_create_invoice_throws(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->andThrow(new \RuntimeException('Accounting DB error'));

        $event = $this->makeEvent();

        // Must not throw — graceful degradation.
        (new HandleExpenseClaimReimbursedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Backwards compatibility: event without optional fields (defaults)
    // -------------------------------------------------------------------------

    public function test_event_defaults_when_optional_fields_not_provided(): void
    {
        $event = new ExpenseClaimReimbursed(
            claimId:  'claim-legacy',
            tenantId: 'tenant-legacy',
        );

        $this->assertSame('', $event->employeeId);
        $this->assertSame('0', $event->totalAmount);
        $this->assertSame('USD', $event->currency);
    }

    // -------------------------------------------------------------------------
    // Event carries enriched fields when provided
    // -------------------------------------------------------------------------

    public function test_event_carries_enriched_fields(): void
    {
        $event = $this->makeEvent(
            employeeId:  'emp-42',
            totalAmount: '1200.50',
            currency:    'GBP',
        );

        $this->assertSame('emp-42', $event->employeeId);
        $this->assertSame('1200.50', $event->totalAmount);
        $this->assertSame('GBP', $event->currency);
    }
}
