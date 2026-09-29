<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;
use Modules\Invoice\DTOs\ManualInvoiceData;
use Modules\Invoice\DTOs\ManualInvoiceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Services\InvoiceReversalService;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Invoice\Services\ManualInvoiceService;
use Modules\Tax\Services\TaxMasterDataService;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\BaseRentPolicy;
use Modules\VehicleRental\Models\CustomerBaseCharge;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\BaseRentBilling;
use Modules\VehicleRental\Services\RentalAuthorization;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\Support\FinancePostingFixture;
use Tests\TestCase;

final class BaseRentBillingTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(RentalAuthorization::class, fn ($mock) => $mock->shouldReceive('assert', 'assertBilling')->zeroOrMoreTimes());
    }

    public function test_customer_and_owner_create_independent_immutable_sources_and_invoice_drafts(): void
    {
        [$context, $customer, $owner] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customer, $owner): void {
            foreach ([[AgreementKind::Customer, $customer, '3000', InvoiceDirection::Outbound], [AgreementKind::Owner, $owner, '1800', InvoiceDirection::Inbound]] as [$kind, $input, $rate, $direction]) {
                $agreement = $this->agreement($kind, $context, $input, $rate);
                $invoice = app(BaseRentBilling::class)->create($kind, $context, $agreement->id, $this->input($agreement));
                $this->assertSame(InvoiceStatus::Draft, $invoice->status);
                $this->assertSame($direction, $invoice->direction);
                $this->assertSame($rate.'.000000', $invoice->grand_total);
                $this->assertSame('1.000000', $invoice->sourceLines->sole()->invoiced_quantity);
                $this->assertSame('0.000000', $invoice->sourceLines->sole()->remaining_quantity);
                $this->assertNotNull($invoice->postingPlan);
                $this->assertSame('2026-09-07', $invoice->documentSnapshot->supply_period_start->toDateString());
            }
            $this->assertDatabaseCount('vehicle_rental_customer_base_charges', 1);
            $this->assertDatabaseCount('vehicle_rental_owner_base_charges', 1);
            $this->assertDatabaseCount('finance_journal_entries', 0);
        });
    }

    public function test_overlapping_period_cannot_be_billed_twice_but_adjacent_period_can(): void
    {
        [$context, $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $agreement = $this->agreement(AgreementKind::Customer, $context, $input);
            $service = app(BaseRentBilling::class);
            $service->create(AgreementKind::Customer, $context, $agreement->id, $this->input($agreement));
            $service->create(AgreementKind::Customer, $context, $agreement->id, array_replace($this->input($agreement), ['from' => '2026-10-07', 'until' => '2026-11-06']));
            try {
                $service->create(AgreementKind::Customer, $context, $agreement->id, array_replace($this->input($agreement), ['from' => '2026-10-06', 'until' => '2026-10-10']));
                $this->fail('Overlapping charge accepted.');
            } catch (ConflictHttpException) {
                $this->assertDatabaseCount('vehicle_rental_customer_base_charges', 2);
                $this->assertDatabaseCount('invoices', 2);
            }
        });
    }

    public function test_cancel_then_reissue_preserves_original_calculation_and_prevents_duplicate_live_invoice(): void
    {
        [$context, $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $agreement = $this->agreement(AgreementKind::Customer, $context, $input);
            $service = app(BaseRentBilling::class);
            $invoice = $service->create(AgreementKind::Customer, $context, $agreement->id, $this->input($agreement));
            $charge = CustomerBaseCharge::query()->sole();
            $original = $charge->attributesToArray();
            app(InvoiceStatusService::class)->transitionIfVersion($invoice, InvoiceStatus::Cancelled, $invoice->row_version, $context->actorId, 'Correct invoice date');
            $new = $service->reissue(AgreementKind::Customer, $context, $agreement->id, $charge->id, array_replace($this->input($agreement), ['invoice_date' => '2026-09-12', 'amount' => '1']));
            $this->assertSame('3000.000000', $new->grand_total);
            $this->assertSame($original, $charge->refresh()->attributesToArray());
            $this->assertDatabaseCount('invoices', 2);
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Invoice quantity cannot exceed source remaining quantity.');
            $service->reissue(AgreementKind::Customer, $context, $agreement->id, $charge->id, $this->input($agreement));
        });
    }

    public function test_charge_rejects_mutation(): void
    {
        [$context, $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $agreement = $this->agreement(AgreementKind::Customer, $context, $input);
            app(BaseRentBilling::class)->create(AgreementKind::Customer, $context, $agreement->id, $this->input($agreement));
            $charge = CustomerBaseCharge::query()->sole();
            $this->expectException(LogicException::class);
            $charge->forceFill(['amount' => '1'])->save();
        });
    }

    public function test_draft_is_not_billable(): void
    {
        [$context, $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $agreement = app(AgreementService::class)->create(AgreementKind::Customer, $context, $input);
            $this->expectException(ValidationException::class);
            app(BaseRentBilling::class)->create(AgreementKind::Customer, $context, $agreement->id, $this->input($agreement));
        });
    }

    public function test_stale_revision_is_rejected_before_creating_financial_records(): void
    {
        [$context, $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $agreement = $this->agreement(AgreementKind::Customer, $context, $input);
            try {
                app(BaseRentBilling::class)->create(AgreementKind::Customer, $context, $agreement->id, array_replace($this->input($agreement), ['expected_version' => 1]));
                $this->fail('Stale revision accepted.');
            } catch (ConflictHttpException) {
                $this->assertDatabaseCount('vehicle_rental_customer_base_charges', 0);
                $this->assertDatabaseCount('invoices', 0);
            }
        });
    }

    public function test_void_requires_released_invoices_and_preserves_history_before_replacement(): void
    {
        [$context, $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $agreement = $this->agreement(AgreementKind::Customer, $context, $input);
            $service = app(BaseRentBilling::class);
            $invoice = $service->create(AgreementKind::Customer, $context, $agreement->id, $this->input($agreement));
            $charge = CustomerBaseCharge::query()->sole();
            $calculation = $charge->calculation;
            $command = ['expected_version' => $agreement->row_version, 'expected_charge_version' => $charge->row_version, 'reason' => 'Corrected billing instruction'];
            try {
                $service->void(AgreementKind::Customer, $context, $agreement->id, $charge->id, $command);
                $this->fail('Live invoice charge was voided.');
            } catch (ConflictHttpException) {
                $this->assertNull($charge->refresh()->voided_at);
            }
            app(InvoiceStatusService::class)->transitionIfVersion($invoice, InvoiceStatus::Cancelled, $invoice->row_version, $context->actorId, 'Correct charge');
            $service->void(AgreementKind::Customer, $context, $agreement->id, $charge->id, $command);
            $this->assertSame($calculation, $charge->refresh()->calculation);
            $this->assertSame(2, $charge->row_version);
            $this->assertSame($context->actorId, (int) $charge->voided_by);
            $service->create(AgreementKind::Customer, $context, $agreement->id, $this->input($agreement));
            $this->assertDatabaseCount('vehicle_rental_customer_base_charges', 2);
            $listed = $service->list(AgreementKind::Customer, $context, $agreement->id, 25)->items();
            $this->assertCount(2, $listed);
            $this->assertSame('draft', $listed[0]['invoices'][0]['status']);
            $this->expectException(ConflictHttpException::class);
            $service->reissue(AgreementKind::Customer, $context, $agreement->id, $charge->id, $this->input($agreement));
        });
    }

    public function test_zero_charge_does_not_create_an_unpostable_invoice_or_consume_a_period(): void
    {
        [$context, $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $agreement = $this->agreement(AgreementKind::Customer, $context, $input, '0');
            try {
                app(BaseRentBilling::class)->create(AgreementKind::Customer, $context, $agreement->id, $this->input($agreement));
                $this->fail('Zero invoice created.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('vehicle_rental_customer_base_charges', 0);
                $this->assertDatabaseCount('invoices', 0);
            }
        });
    }

    public function test_tax_configuration_and_posting_plan_reconcile_inclusive_tax_and_withholding(): void
    {
        [$context, $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $taxes = app(TaxMasterDataService::class);
            $lines = [];
            foreach ([['INC', 'VAT', 'inclusive', false], ['WHT', 'WHT', 'exclusive', true]] as $index => [$code, $type, $method, $withholding]) {
                $tax = $taxes->saveTax(['tenant_id' => $context->tenantId, 'organization_unit_id' => $context->organizationUnitId, 'code' => $code,
                    'name' => $code, 'tax_type' => $type, 'calculation_method' => $method, 'is_withholding' => $withholding,
                    'recoverable' => false, 'payable' => true, 'receivable' => false, 'active' => true]);
                $taxes->saveRate($tax, ['rate' => '10', 'effective_from' => '2026-01-01', 'effective_to' => null, 'active' => true]);
                $lines[] = ['tax_id' => $tax->id, 'sequence' => $index + 1, 'active' => true];
            }
            $taxes->saveGroup(['tenant_id' => $context->tenantId, 'organization_unit_id' => $context->organizationUnitId, 'code' => 'TEST', 'name' => 'Synthetic tax test', 'is_default' => true, 'active' => true], $lines);
            $agreement = $this->agreement(AgreementKind::Customer, $context, $input, '110');
            $invoice = app(BaseRentBilling::class)->create(AgreementKind::Customer, $context, $agreement->id, $this->input($agreement));
            $this->assertSame('99.000000', $invoice->grand_total);
            $this->assertSame('21.000000', $invoice->tax_total);
            $plan = collect($invoice->postingPlan->lines)->keyBy('role');
            $this->assertSame('100.000000', $plan['rental_revenue']['credit']);
            $this->assertSame('10.000000', $plan['tax_payable']['credit']);
            $this->assertSame('11.000000', $plan['withholding_receivable']['debit']);
            $this->assertSame('99.000000', $plan['receivable']['debit']);
            $this->assertDatabaseCount('tax_document_snapshots', 2);
            $manual = app(ManualInvoiceService::class)->create(new ManualInvoiceData(
                tenantId: $context->tenantId, direction: InvoiceDirection::Outbound, invoiceDate: '2026-09-12',
                organizationUnitId: $context->organizationUnitId, customerId: $input['party_id'], currencyId: $input['currency_id'],
                lines: [new ManualInvoiceLineData(description: 'Shared tax preparation', quantity: '1', unitPrice: '110')]), 'manual-tax-regression');
            $this->assertSame('99.000000', $manual->grand_total);
            $manualPlan = collect($manual->postingPlan->lines)->keyBy('role');
            $this->assertSame('100.000000', $manualPlan['revenue']['credit']);
            $this->assertSame('10.000000', $manualPlan['tax_payable']['credit']);
            $this->assertSame('11.000000', $manualPlan['withholding_receivable']['debit']);

        });
    }

    public function test_both_sides_post_to_finance_and_reverse_without_changing_charge_evidence(): void
    {
        [$context, $customer, $owner] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customer, $owner): void {
            FinancePostingFixture::seedRentalInvoiceProfiles($context->tenantId, $context->organizationUnitId);
            foreach ([[AgreementKind::Customer, $customer], [AgreementKind::Owner, $owner]] as [$kind, $input]) {
                $agreement = $this->agreement($kind, $context, $input);
                $billing = app(BaseRentBilling::class);
                $invoice = $billing->create($kind, $context, $agreement->id, $this->input($agreement));
                $statuses = app(InvoiceStatusService::class);
                $invoice = $statuses->transitionIfVersion($invoice, InvoiceStatus::Approved, $invoice->row_version, $context->actorId);
                $invoice = $statuses->transitionIfVersion($invoice, InvoiceStatus::Posted, $invoice->row_version, $context->actorId);
                $this->assertSame(InvoiceStatus::Posted, $invoice->status);
                $this->assertNotNull($invoice->refresh()->postingPlan->finance_posting_reference);
                $invoice = app(InvoiceReversalService::class)->reverse($invoice, $invoice->row_version, now()->toDateString(), 'Reverse test posting', $context->actorId);
                $this->assertSame(InvoiceStatus::Reversed, $invoice->status);
                $chargeId = $invoice->sourceLines->sole()->source_id;
                $new = $billing->reissue($kind, $context, $agreement->id, $chargeId, $this->input($agreement));
                $this->assertSame('3000.000000', $new->grand_total);
                $this->assertSame(InvoiceStatus::Draft, $new->status);
            }
            $this->assertDatabaseCount('finance_journal_entries', 4);
            $this->assertDatabaseCount('vehicle_rental_customer_base_charges', 1);
            $this->assertDatabaseCount('vehicle_rental_owner_base_charges', 1);
        });
    }

    public function test_tax_preparation_failure_rolls_back_charge_and_invoice_atomically(): void
    {
        [$context, $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $taxes = app(TaxMasterDataService::class);
            $tax = $taxes->saveTax(['tenant_id' => $context->tenantId, 'organization_unit_id' => $context->organizationUnitId,
                'code' => 'NO-RATE', 'name' => 'Missing effective rate', 'tax_type' => 'VAT', 'calculation_method' => 'exclusive',
                'is_withholding' => false, 'recoverable' => false, 'payable' => true, 'receivable' => false, 'active' => true]);
            $taxes->saveGroup(['tenant_id' => $context->tenantId, 'organization_unit_id' => $context->organizationUnitId,
                'code' => 'NO-RATE', 'name' => 'Incomplete test configuration', 'is_default' => true, 'active' => true], [['tax_id' => $tax->id, 'sequence' => 1, 'active' => true]]);
            $agreement = $this->agreement(AgreementKind::Customer, $context, $input);
            try {
                app(BaseRentBilling::class)->create(AgreementKind::Customer, $context, $agreement->id, $this->input($agreement));
                $this->fail('Missing tax rate accepted.');
            } catch (InvalidArgumentException) {
                $this->assertDatabaseCount('vehicle_rental_customer_base_charges', 0);
                $this->assertDatabaseCount('invoices', 0);
                $this->assertDatabaseCount('invoice_source_lines', 0);
            }
        });
    }

    public function test_amount_exceeding_storage_precision_is_rejected_before_persistence(): void
    {
        [$context, $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $agreement = $this->agreement(AgreementKind::Customer, $context, array_replace($input, ['basis' => 'daily']), '99999999999999');
            try {
                app(BaseRentBilling::class)->create(AgreementKind::Customer, $context, $agreement->id, $this->input($agreement));
                $this->fail('Oversized charge accepted.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('vehicle_rental_customer_base_charges', 0);
                $this->assertDatabaseCount('invoices', 0);
            }
        });
    }

    private function agreement($kind, $context, $input, string $rate = '3000')
    {
        $service = app(AgreementService::class);
        $agreement = $service->create($kind, $context, array_replace($input, ['terms' => ['base_rate' => $rate]]));

        return $service->change($kind, $context, $agreement->id, $agreement->row_version, AgreementAction::Activate);
    }

    private function input($agreement): array
    {
        return ['expected_version' => $agreement->row_version, 'policy' => BaseRentPolicy::ActualCalendarDays->value,
            'from' => '2026-09-07', 'until' => '2026-10-06', 'invoice_date' => '2026-09-11', 'exchange_rate' => '1'];
    }
}
