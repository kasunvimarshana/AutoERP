<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Services\InvoiceReversalService;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Tax\Services\TaxMasterDataService;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\MileagePolicy;
use Modules\VehicleRental\Enums\RentalBasis;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\UsageChargeComponent;
use Modules\VehicleRental\Enums\UsageChargePolicy;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Models\CustomerUsageCharge;
use Modules\VehicleRental\Models\OwnerUsageCharge;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\MileageAllowance;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RunningChartService;
use Modules\VehicleRental\Services\UsageChargeBilling;
use Modules\VehicleRental\Services\VehicleUseService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\Support\FinancePostingFixture;
use Tests\TestCase;

final class UsageChargeBillingTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-09-12T12:00:00Z'));
        $this->mock(RentalAuthorization::class, fn ($mock) => $mock->shouldReceive('assert', 'assertUse', 'assertChart', 'assertBilling')->zeroOrMoreTimes());
    }

    public function test_both_sides_post_reverse_and_reissue_through_finance(): void
    {
        $this->billable(function ($ctx, $c, $o, $chart): void {
            FinancePostingFixture::seedRentalInvoiceProfiles($ctx->tenantId, $ctx->organizationUnitId);
            foreach ([[AgreementKind::Customer, $c], [AgreementKind::Owner, $o]] as [$kind, $agreement]) {
                $billing = app(UsageChargeBilling::class);
                $invoice = $billing->create($kind, $ctx, $chart->id, $this->input($agreement, $chart));
                $amount = $invoice->grand_total;
                $statuses = app(InvoiceStatusService::class);
                $invoice = $statuses->transitionIfVersion($invoice, InvoiceStatus::Approved, $invoice->row_version, $ctx->actorId);
                $invoice = $statuses->transitionIfVersion($invoice, InvoiceStatus::Posted, $invoice->row_version, $ctx->actorId);
                $this->assertNotNull($invoice->refresh()->postingPlan->finance_posting_reference);
                $invoice = app(InvoiceReversalService::class)->reverse($invoice, $invoice->row_version, now()->toDateString(), 'Correct document', $ctx->actorId);
                $this->assertSame(InvoiceStatus::Reversed, $invoice->status);
                $new = $billing->reissue($kind, $ctx, $chart->id, $invoice->sourceLines->sole()->source_id, $this->input($agreement, $chart));
                $this->assertSame($amount, $new->grand_total);
            }
            $this->assertDatabaseCount('finance_journal_entries', 4);
            $this->assertDatabaseCount('vehicle_rental_customer_usage_charges', 1);
            $this->assertDatabaseCount('vehicle_rental_owner_usage_charges', 1);
        });
    }

    public function test_missing_tax_configuration_rolls_back_the_usage_charge(): void
    {
        $this->billable(function ($ctx, $c, $o, $chart): void {
            $taxes = app(TaxMasterDataService::class);
            $tax = $taxes->saveTax(['tenant_id' => $ctx->tenantId, 'organization_unit_id' => $ctx->organizationUnitId,
                'code' => 'MISSING-RATE', 'name' => 'Missing rate', 'tax_type' => 'VAT', 'calculation_method' => 'exclusive',
                'is_withholding' => false, 'recoverable' => false, 'payable' => true, 'receivable' => false, 'active' => true]);
            $taxes->saveGroup(['tenant_id' => $ctx->tenantId, 'organization_unit_id' => $ctx->organizationUnitId,
                'code' => 'MISSING-RATE', 'name' => 'Missing rate', 'is_default' => true, 'active' => true], [['tax_id' => $tax->id, 'sequence' => 1, 'active' => true]]);
            try {
                app(UsageChargeBilling::class)->create(AgreementKind::Customer, $ctx, $chart->id, $this->input($c, $chart));
                $this->fail('Accepted incomplete tax configuration.');
            } catch (\InvalidArgumentException) {
                $this->assertDatabaseCount('vehicle_rental_customer_usage_charges', 0);
                $this->assertDatabaseCount('invoices', 0);
                $this->assertDatabaseCount('invoice_source_lines', 0);
            }
        });
    }

    public function test_preview_is_read_only_and_monetary_overflow_is_rejected(): void
    {
        $this->billable(function ($ctx, $c, $o, $chart): void {
            $billing = app(UsageChargeBilling::class);
            $quotes = $billing->list(AgreementKind::Customer, $ctx, $chart->id, 25)['components'];
            $this->assertNotNull($quotes[0]['error']);
            $this->assertDatabaseCount('invoices', 0);
            try {
                $billing->create(AgreementKind::Customer, $ctx, $chart->id, $this->input($c, $chart));
                $this->fail('Monetary overflow accepted.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('vehicle_rental_customer_usage_charges', 0);
            }
        }, ['normal_ot_minutes' => 2147483647], ['normal_ot_rate' => '99999999999999.999999']);
    }

    public function test_mileage_pool_is_shared_across_charts_and_zero_assessments_are_preserved(): void
    {
        $this->billable(function ($ctx, $c, $o, $chart): void {
            $service = app(UsageChargeBilling::class);
            $quote = $service->previewMileage(AgreementKind::Customer, $ctx, $chart->id);
            $this->assertSame('100.000000', $quote['allowance']);
            $this->assertSame('0.000000', $quote['amount']);
            $first = $service->assessMileage(AgreementKind::Customer, $ctx, $chart->id, $this->mileageInput($c, $chart, $quote));
            $this->assertNull($first['invoice']);
            $this->assertDatabaseCount('invoices', 0);
            $next = $this->nextMileageChart($ctx, $chart, '90');
            $quote = $service->previewMileage(AgreementKind::Customer, $ctx, $next->id);
            $this->assertSame('60.000000', $quote['included_applied']);
            $this->assertSame('30.000000', $quote['excess_km']);
            $this->assertSame('2700.000000', $quote['amount']);
            $second = $service->assessMileage(AgreementKind::Customer, $ctx, $next->id, $this->mileageInput($c, $next, $quote));
            $this->assertSame('2700.000000', $second['invoice']->grand_total);
            $this->assertSame('2026-09-08', $second['invoice']->documentSnapshot->supply_period_start->toDateString());
            $this->assertSame('2026-09-08', $second['invoice']->documentSnapshot->supply_period_end->toDateString());
            $this->assertDatabaseCount('vehicle_rental_customer_usage_charges', 2);
            $this->expectException(ConflictHttpException::class);
            $service->assessMileage(AgreementKind::Customer, $ctx, $chart->id, $this->mileageInput($c, $chart, $quote));
        }, ['starts_at' => '2026-09-07T09:00:00+00:00', 'ends_at' => '2026-09-07T17:00:00+00:00', 'commercial_km' => '40'], ['included_km' => '100', 'excess_km_rate' => '90']);
    }

    public function test_mileage_corrections_unwind_dependencies_and_preserve_both_sides(): void
    {
        $this->billable(function ($ctx, $c, $o, $chart): void {
            $service = app(UsageChargeBilling::class);
            $quote = $service->previewMileage(AgreementKind::Customer, $ctx, $chart->id);
            $first = $service->assessMileage(AgreementKind::Customer, $ctx, $chart->id, $this->mileageInput($c, $chart, $quote));
            $next = $this->nextMileageChart($ctx, $chart, '90');
            $quote = $service->previewMileage(AgreementKind::Customer, $ctx, $next->id);
            $second = $service->assessMileage(AgreementKind::Customer, $ctx, $next->id, $this->mileageInput($c, $next, $quote));
            $command = $this->input($c, $chart) + ['expected_charge_version' => 1, 'reason' => 'Correction'];
            try {
                $service->void(AgreementKind::Customer, $ctx, $chart->id, $first['assessment']['id'], $command);
                $this->fail('Voided an assessment with surviving dependents.');
            } catch (ConflictHttpException) {
                $this->assertNull(CustomerUsageCharge::query()->findOrFail($first['assessment']['id'])->voided_at);
            }
            $invoice = $second['invoice'];
            app(InvoiceStatusService::class)->transitionIfVersion($invoice, InvoiceStatus::Cancelled, $invoice->row_version, $ctx->actorId, 'Correct mileage');
            $service->void(AgreementKind::Customer, $ctx, $next->id, $second['assessment']['id'], $command + ['expected_chart_version' => $next->row_version]);
            $service->void(AgreementKind::Customer, $ctx, $chart->id, $first['assessment']['id'], $command);
            $quote = $service->previewMileage(AgreementKind::Customer, $ctx, $next->id);
            $this->assertSame('0.000000', $quote['amount']);
            $owner = $service->previewMileage(AgreementKind::Owner, $ctx, $chart->id);
            $this->assertSame('0', $owner['prior_distance']);
            $this->assertDatabaseCount('vehicle_rental_customer_usage_charges', 2);
        }, ['starts_at' => '2026-09-07T09:00:00+00:00', 'ends_at' => '2026-09-07T17:00:00+00:00', 'commercial_km' => '40'], ['included_km' => '100', 'excess_km_rate' => '90']);
    }

    public function test_mileage_quote_revision_and_cumulative_rounding_prevent_stale_or_lost_amounts(): void
    {
        $this->billable(function ($ctx, $c, $o, $chart): void {
            $service = app(UsageChargeBilling::class);
            $next = $this->nextMileageChart($ctx, $chart, '0.000001');
            $old = $service->previewMileage(AgreementKind::Customer, $ctx, $next->id);
            $firstQuote = $service->previewMileage(AgreementKind::Customer, $ctx, $chart->id);
            $first = $service->assessMileage(AgreementKind::Customer, $ctx, $chart->id, $this->mileageInput($c, $chart, $firstQuote));
            $this->assertNull($first['invoice']);
            try {
                $service->assessMileage(AgreementKind::Customer, $ctx, $next->id, $this->mileageInput($c, $next, $old));
                $this->fail('Stale allowance quote accepted.');
            } catch (ConflictHttpException) {
                $this->assertDatabaseCount('vehicle_rental_customer_usage_charges', 1);
            }
            $quote = $service->previewMileage(AgreementKind::Customer, $ctx, $next->id);
            $this->assertSame('0.000001', $quote['amount']);
            $second = $service->assessMileage(AgreementKind::Customer, $ctx, $next->id, $this->mileageInput($c, $next, $quote));
            $this->assertSame('0.000001', $second['invoice']->grand_total);
        }, ['starts_at' => '2026-09-07T09:00:00+00:00', 'ends_at' => '2026-09-07T17:00:00+00:00', 'commercial_km' => '0.000001'], ['included_km' => '0', 'excess_km_rate' => '0.500001']);
    }

    public function test_mileage_calendar_cycles_partial_months_and_unsplittable_evidence(): void
    {
        $this->billable(function ($ctx, $c, $o, $chart): void {
            $calculator = app(MileageAllowance::class);
            $terms = ['included_km' => '2800', 'excess_km_rate' => '90'];
            $c->starts_on = '2026-01-31';
            $c->ends_on = '2026-02-13';
            $chart->starts_at = '2026-02-01 09:00:00';
            $chart->ends_at = '2026-02-01 17:00:00';
            $quote = $calculator->quote(AgreementKind::Customer, $ctx, $c, $chart, $terms);
            $this->assertSame(28, $quote['cycle_days']);
            $this->assertSame(14, $quote['covered_days']);
            $this->assertSame('1400.000000', $quote['allowance']);
            $c->ends_on = null;
            $chart->starts_at = '2026-03-01 09:00:00';
            $chart->ends_at = '2026-03-01 17:00:00';
            $quote = $calculator->quote(AgreementKind::Customer, $ctx, $c, $chart, $terms);
            $this->assertSame('2026-02-28', $quote['cycle_from']);
            $this->assertSame('2026-03-30', $quote['cycle_until']);
            $this->assertSame(31, $quote['cycle_days']);
            $c->basis = RentalBasis::Daily;
            $quote = $calculator->quote(AgreementKind::Customer, $ctx, $c, $chart, $terms);
            $this->assertSame('2026-03-01', $quote['cycle_from']);
            $this->assertSame('2026-03-01', $quote['cycle_until']);
            $chart->ends_at = '2026-03-02 01:00:00';
            $this->expectException(ValidationException::class);
            $calculator->quote(AgreementKind::Customer, $ctx, $c, $chart, $terms);
        }, ['commercial_km' => '40']);
    }

    private function mileageInput($agreement, $chart, array $quote): array
    {
        return array_replace($this->input($agreement, $chart), ['policy' => MileagePolicy::CommercialCalendarCycles->value, 'expected_pool_head' => $quote['pool_head'], 'expected_timezone' => $quote['timezone']]);
    }

    private function nextMileageChart($context, $chart, string $distance)
    {
        $use = $chart->vehicleUse;
        $service = app(RunningChartService::class);
        $next = $service->create($context, $use->id, $use->row_version, ['reference' => 'MILEAGE-NEXT', 'starts_at' => '2026-09-08T09:00:00+00:00', 'ends_at' => '2026-09-08T17:00:00+00:00', 'commercial_km' => $distance]);

        return $service->change($context, $next->id, $next->row_version, RunningChartAction::Finalize);
    }

    private function billable(callable $work, array $facts = [], array $terms = []): void
    {
        [$context, $customer, $owner] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customer, $owner, $facts, $terms, $work): void {
            $service = app(AgreementService::class);
            $base = ['normal_ot_rate' => '500', 'double_ot_rate' => '700', 'triple_ot_rate' => '900', 'night_out_rate' => '2000'];
            $customer['terms'] = array_replace($base, $terms);
            $owner['terms'] = array_replace($base, ['normal_ot_rate' => '250'], $terms);
            $c = $service->create(AgreementKind::Customer, $context, $customer);
            $o = $service->create(AgreementKind::Owner, $context, $owner);
            $c = $service->change(AgreementKind::Customer, $context, $c->id, $c->row_version, AgreementAction::Activate);
            $o = $service->change(AgreementKind::Owner, $context, $o->id, $o->row_version, AgreementAction::Activate);
            $uses = app(VehicleUseService::class);
            $use = $uses->plan($context, $c->id, $c->row_version, ['vehicle_id' => $owner['vehicle_id'], 'owner_agreement_id' => $o->id, 'starts_at' => '2026-09-07T00:00:00+05:30', 'ends_at' => '2026-09-10T00:00:00+05:30']);
            $use = $uses->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, ['occurred_at' => '2026-09-07T00:00:00+05:30', 'reason' => 'Collected']);
            $chart = app(RunningChartService::class)->create($context, $use->id, $use->row_version, array_replace([
                'reference' => 'CHART-BILL', 'starts_at' => '2026-09-07T00:00:00+05:30', 'ends_at' => '2026-09-09T00:00:00+05:30',
                'normal_ot_minutes' => 1470, 'double_ot_minutes' => 60, 'triple_ot_minutes' => 30, 'night_outs' => 2,
            ], $facts));
            $chart = app(RunningChartService::class)->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize);
            $work($context, $c, $o, $chart);
        });
    }

    private function input($agreement, $chart, UsageChargeComponent $component = UsageChargeComponent::NormalOvertime): array
    {
        return ['expected_version' => $agreement->row_version, 'expected_chart_version' => $chart->row_version,
            'invoice_date' => '2026-09-12', 'exchange_rate' => '1', 'policy' => UsageChargePolicy::RecordedMinutesAndNights->value, 'component' => $component->value];
    }

    public function test_independent_sides_use_exact_minutes_and_category_rates_without_extra_multipliers(): void
    {
        $this->billable(function ($ctx, $c, $o, $chart): void {
            $service = app(UsageChargeBilling::class);
            foreach ([[AgreementKind::Customer, $c, UsageChargeComponent::NormalOvertime, '12250.000000'],
                [AgreementKind::Owner, $o, UsageChargeComponent::NormalOvertime, '6125.000000'],
                [AgreementKind::Customer, $c, UsageChargeComponent::DoubleOvertime, '700.000000'],
                [AgreementKind::Customer, $c, UsageChargeComponent::TripleOvertime, '450.000000'],
                [AgreementKind::Customer, $c, UsageChargeComponent::NightOut, '4000.000000']] as [$kind, $agreement, $component, $amount]) {
                $invoice = $service->create($kind, $ctx, $chart->id, $this->input($agreement, $chart, $component));
                $this->assertSame($amount, $invoice->grand_total);
                $this->assertSame('1.000000', $invoice->sourceLines->sole()->invoiced_quantity);
                $this->assertSame('2026-09-07', $invoice->documentSnapshot->supply_period_start->toDateString());
                $this->assertSame(InvoiceStatus::Draft, $invoice->status);
            }
            $this->assertDatabaseCount('vehicle_rental_customer_usage_charges', 4);
            $this->assertDatabaseCount('vehicle_rental_owner_usage_charges', 1);
            $list = $service->list(AgreementKind::Customer, $ctx, $chart->id, 25);
            $this->assertSame($c->reference, $list['agreement']['reference']);
            $this->assertCount(4, $list['charges']->items());
            $first = CustomerUsageCharge::query()->orderBy('id')->first();
            $this->assertSame(1470, $first->calculation['quantity']);
            $this->assertSame(60, $first->calculation['denominator']);
            $this->assertSame($c->row_version, $first->calculation['agreement']['version']);
            $this->expectException(ConflictHttpException::class);
            $service->create(AgreementKind::Customer, $ctx, $chart->id, $this->input($c, $chart));
        });
    }

    public function test_minutes_are_multiplied_before_division_and_client_amount_is_ignored(): void
    {
        $this->billable(function ($ctx, $c, $o, $chart): void {
            $invoice = app(UsageChargeBilling::class)->create(AgreementKind::Customer, $ctx, $chart->id, $this->input($c, $chart) + ['amount' => '999', 'rate' => '999', 'quantity' => 90]);
            $this->assertSame('1.666666', $invoice->grand_total);
            $this->assertSame('100.000000', CustomerUsageCharge::query()->sole()->calculation['rate']);
        }, ['normal_ot_minutes' => 1], ['normal_ot_rate' => '100']);
    }

    public function test_unknown_quantity_or_rate_and_zero_amount_do_not_create_or_consume_sources(): void
    {
        foreach ([[null, '100'], [60, null], [0, '100'], [60, '0']] as [$minutes, $rate]) {
            $this->billable(function ($ctx, $c, $o, $chart): void {
                try {
                    app(UsageChargeBilling::class)->create(AgreementKind::Customer, $ctx, $chart->id, $this->input($c, $chart));
                    $this->fail('Invalid amount invoiced.');
                } catch (ValidationException) {
                    $this->assertDatabaseCount('vehicle_rental_customer_usage_charges', 0);
                    $this->assertDatabaseCount('invoices', 0);
                }
            }, ['normal_ot_minutes' => $minutes], ['normal_ot_rate' => $rate]);
        }
    }

    public function test_stale_chart_and_agreement_are_rejected_atomically(): void
    {
        $this->billable(function ($ctx, $c, $o, $chart): void {
            foreach ([['expected_version' => 1], ['expected_chart_version' => 1]] as $stale) {
                try {
                    app(UsageChargeBilling::class)->create(AgreementKind::Customer, $ctx, $chart->id, array_replace($this->input($c, $chart), $stale));
                    $this->fail('Stale state accepted.');
                } catch (ConflictHttpException) {
                    $this->assertDatabaseCount('vehicle_rental_customer_usage_charges', 0);
                }
            }
        });
    }

    public function test_both_sides_must_release_consumption_before_chart_reversal(): void
    {
        $this->billable(function ($ctx, $c, $o, $chart): void {
            $service = app(UsageChargeBilling::class);
            foreach ([[AgreementKind::Customer, $c, CustomerUsageCharge::class], [AgreementKind::Owner, $o, OwnerUsageCharge::class]] as [$kind, $agreement, $class]) {
                $invoice = $service->create($kind, $ctx, $chart->id, $this->input($agreement, $chart));
                $charge = $class::query()->sole();
                $command = $this->input($agreement, $chart) + ['expected_charge_version' => $charge->row_version, 'reason' => 'Correct physical evidence'];
                try {
                    $service->void($kind, $ctx, $chart->id, $charge->id, $command);
                    $this->fail('Voided live invoice source.');
                } catch (ConflictHttpException) {
                    $this->assertNull($charge->refresh()->voided_at);
                }
                app(InvoiceStatusService::class)->transitionIfVersion($invoice, InvoiceStatus::Cancelled, $invoice->row_version, $ctx->actorId, 'Correct evidence');
                try {
                    app(RunningChartService::class)->change($ctx, $chart->id, $chart->row_version, RunningChartAction::Reverse, reason: 'Correction');
                    $this->fail('Reversed consumed chart.');
                } catch (ConflictHttpException) {
                    $this->assertSame($chart->row_version, $chart->refresh()->row_version);
                }
                $service->void($kind, $ctx, $chart->id, $charge->id, $command);
                $this->assertNotNull($charge->refresh()->voided_at);
            }
            $reversed = app(RunningChartService::class)->change($ctx, $chart->id, $chart->row_version, RunningChartAction::Reverse, reason: 'Correct after both sides released');
            $this->expectException(ConflictHttpException::class);
            $service->create(AgreementKind::Customer, $ctx, $chart->id, $this->input($c, $reversed));
        });
    }

    public function test_reissue_keeps_snapshot_and_void_allows_corrected_source_without_erasing_history(): void
    {
        $this->billable(function ($ctx, $c, $o, $chart): void {
            $service = app(UsageChargeBilling::class);
            $input = $this->input($c, $chart);
            $invoice = $service->create(AgreementKind::Customer, $ctx, $chart->id, $input);
            $charge = CustomerUsageCharge::query()->sole();
            $snapshot = $charge->calculation;
            app(InvoiceStatusService::class)->transitionIfVersion($invoice, InvoiceStatus::Cancelled, $invoice->row_version, $ctx->actorId, 'Wrong document date');
            $reissued = $service->reissue(AgreementKind::Customer, $ctx, $chart->id, $charge->id, $input + ['amount' => '1']);
            $this->assertSame($invoice->grand_total, $reissued->grand_total);
            $this->assertSame($snapshot, $charge->refresh()->calculation);
            app(InvoiceStatusService::class)->transitionIfVersion($reissued, InvoiceStatus::Cancelled, $reissued->row_version, $ctx->actorId, 'Release');
            $service->void(AgreementKind::Customer, $ctx, $chart->id, $charge->id, $input + ['expected_charge_version' => $charge->row_version, 'reason' => 'Audited correction']);
            $service->create(AgreementKind::Customer, $ctx, $chart->id, $input);
            $this->assertDatabaseCount('vehicle_rental_customer_usage_charges', 2);
            $this->expectException(LogicException::class);
            $charge->forceFill(['amount' => '1'])->save();
        });
    }
}
