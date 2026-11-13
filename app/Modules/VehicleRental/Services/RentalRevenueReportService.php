<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\Tenant\Models\TenantModel;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Enums\RentalExpenseAllocationStatus;
use Modules\VehicleRental\Enums\RentalExpenseAllocationType;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalExpenseType;
use Modules\VehicleRental\Enums\UsageChargeComponent;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\CustomerBaseCharge;
use Modules\VehicleRental\Models\CustomerUsageCharge;
use Modules\VehicleRental\Models\RentalExpense;
use Modules\VehicleRental\Models\RentalExpenseAllocation;

final class RentalRevenueReportService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalAuthorization $authorization,
    ) {}

    /**
     * @param  array{start_date: string, end_date: string, customer_id?: ?int, agreement_id?: ?int, report_type?: ?string}  $params
     * @return array<string, mixed>
     */
    public function generate(AgreementContext $context, array $params): array
    {
        $this->authorization->assertReport($context);

        if (empty($params['start_date']) || empty($params['end_date'])) {
            throw ValidationException::withMessages(['start_date' => ['Specify both start date and end date for the revenue report.']]);
        }

        $startDate = (string) $params['start_date'];
        $endDate = (string) $params['end_date'];

        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->endOfDay();

        if ($end->lessThan($start)) {
            throw ValidationException::withMessages(['end_date' => ['End date must be on or after start date.']]);
        }

        $customerId = ! empty($params['customer_id']) ? (int) $params['customer_id'] : null;
        $agreementId = ! empty($params['agreement_id']) ? (int) $params['agreement_id'] : null;
        $reportType = (string) ($params['report_type'] ?? 'summary');

        $agreements = CustomerAgreement::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['customer', 'vehicleUses.vehicle'])
            ->when($customerId !== null, fn (Builder $q) => $q->where('customer_id', $customerId))
            ->when($agreementId !== null, fn (Builder $q) => $q->where('id', $agreementId))
            ->whereIn('status', [AgreementStatus::Active->value, AgreementStatus::Closed->value])
            ->where('starts_on', '<=', $endDate)
            ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', $startDate))
            ->orderBy('reference')
            ->get();

        $rows = [];
        $grandTotalBaseRent = '0.00';
        $grandTotalExcessMileage = '0.00';
        $grandTotalOvertime = '0.00';
        $grandTotalNightOut = '0.00';
        $grandTotalTollPass = '0.00';
        $grandTotalRevenue = '0.00';

        foreach ($agreements as $agreement) {
            $baseChargesSum = (string) CustomerBaseCharge::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->where('agreement_id', $agreement->id)
                ->whereNull('voided_at')
                ->where('period_from', '<=', $endDate)
                ->where('period_until', '>=', $startDate)
                ->sum('amount');
            $baseRent = $baseChargesSum !== '' ? $baseChargesSum : '0.00';

            $usageCharges = CustomerUsageCharge::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->where('agreement_id', $agreement->id)
                ->whereNull('voided_at')
                ->where('period_from', '<=', $endDate)
                ->where('period_until', '>=', $startDate)
                ->get();

            $excessMileage = (string) ($usageCharges->where('component', 'excess_mileage')->sum('amount') ?: '0.00');
            $normalOt = (string) ($usageCharges->where('component', UsageChargeComponent::NormalOvertime->value)->sum('amount') ?: '0.00');
            $doubleOt = (string) ($usageCharges->where('component', UsageChargeComponent::DoubleOvertime->value)->sum('amount') ?: '0.00');
            $tripleOt = (string) ($usageCharges->where('component', UsageChargeComponent::TripleOvertime->value)->sum('amount') ?: '0.00');
            $nightOut = (string) ($usageCharges->where('component', UsageChargeComponent::NightOut->value)->sum('amount') ?: '0.00');
            $totalOvertime = $this->math->sum([$normalOt, $doubleOt, $tripleOt], 2);

            $allocatedCustomerRecoveries = (string) (RentalExpenseAllocation::query()
                ->where('target_customer_agreement_id', $agreement->id)
                ->where('allocation_type', RentalExpenseAllocationType::CustomerRecovery->value)
                ->where('status', RentalExpenseAllocationStatus::Confirmed->value)
                ->whereHas('expense', fn (Builder $q) => $q
                    ->where('expense_date', '>=', $startDate)
                    ->where('expense_date', '<=', $endDate)
                    ->whereNotIn('status', [RentalExpenseStatus::Rejected->value, RentalExpenseStatus::Reversed->value])
                )
                ->sum('total_amount') ?: '0.00');

            $directTollPass = (string) (RentalExpense::query()
                ->forTenant($context->tenantId)
                ->where('customer_agreement_id', $agreement->id)
                ->where('expense_type', RentalExpenseType::Toll)
                ->where('expense_date', '>=', $startDate)
                ->where('expense_date', '<=', $endDate)
                ->whereNotIn('status', [RentalExpenseStatus::Rejected->value, RentalExpenseStatus::Reversed->value])
                ->whereDoesntHave('allocations')
                ->sum('gross_amount') ?: '0.00');

            $tollPass = $this->math->add($allocatedCustomerRecoveries, $directTollPass, 2);

            $totalAgreementRevenue = $this->math->sum([$baseRent, $excessMileage, $totalOvertime, $nightOut, $tollPass], 2);

            $primaryUse = $agreement->vehicleUses->firstWhere('replaces_use_id', null) ?? $agreement->vehicleUses->first();
            $vehicleNo = $primaryUse?->vehicle_label_snapshot
                ?? $primaryUse?->vehicle?->registration_number
                ?? $primaryUse?->vehicle?->vehicle_number
                ?? 'N/A';

            $grandTotalBaseRent = $this->math->add($grandTotalBaseRent, $baseRent, 2);
            $grandTotalExcessMileage = $this->math->add($grandTotalExcessMileage, $excessMileage, 2);
            $grandTotalOvertime = $this->math->add($grandTotalOvertime, $totalOvertime, 2);
            $grandTotalNightOut = $this->math->add($grandTotalNightOut, $nightOut, 2);
            $grandTotalTollPass = $this->math->add($grandTotalTollPass, $tollPass, 2);
            $grandTotalRevenue = $this->math->add($grandTotalRevenue, $totalAgreementRevenue, 2);

            $rows[] = [
                'agreement_id' => $agreement->id,
                'agreement_reference' => $agreement->reference,
                'customer_id' => $agreement->customer_id,
                'customer_name' => $agreement->customer?->name ?? $agreement->party_name_snapshot ?? 'N/A',
                'customer_code' => $agreement->customer?->customer_number ?? '-',
                'vehicle_number' => $vehicleNo,
                'rental_basis' => $agreement->basis->value,
                'driver_mode' => $agreement->driver_mode->value,
                'status' => $agreement->status->value,
                'period_start' => $agreement->starts_on->toDateString(),
                'period_end' => $agreement->ends_on?->toDateString() ?? 'Open-ended',
                'base_rent' => number_format((float) $baseRent, 2, '.', ''),
                'excess_mileage' => number_format((float) $excessMileage, 2, '.', ''),
                'overtime' => number_format((float) $totalOvertime, 2, '.', ''),
                'night_out' => number_format((float) $nightOut, 2, '.', ''),
                'toll_pass' => number_format((float) $tollPass, 2, '.', ''),
                'total_revenue' => number_format((float) $totalAgreementRevenue, 2, '.', ''),
            ];
        }

        // Build Lessee's All Invoice Listing (Summary) - matching legacy FoxPro prndebinv3_s.pdf
        $invoices = $this->buildInvoiceListing(
            $context,
            $agreements,
            $rows,
            $start,
            $end,
            $customerId,
        );

        $tenantName = TenantModel::query()->find($context->tenantId)?->name ?? (config('app.name') ?: 'AutoERP');

        $reportTitle = $reportType === 'invoice_listing'
            ? "Lessee's All Invoice Listing (Summary)"
            : 'RENTAL REVENUE & BILLING SUMMARY REPORT';

        return [
            'header' => [
                'company_name' => $tenantName,
                'report_title' => $reportTitle,
                'report_type' => $reportType,
                'duration' => $start->format('d/m/Y').' To '.$end->format('d/m/Y'),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'report_date' => now()->format('d/m/Y'),
            ],
            'filters' => [
                'customer_id' => $customerId,
                'agreement_id' => $agreementId,
                'report_type' => $reportType,
            ],
            'summary' => [
                'total_agreements' => count($rows),
                'total_base_rent' => number_format((float) $grandTotalBaseRent, 2, '.', ''),
                'total_excess_mileage' => number_format((float) $grandTotalExcessMileage, 2, '.', ''),
                'total_overtime' => number_format((float) $grandTotalOvertime, 2, '.', ''),
                'total_night_out' => number_format((float) $grandTotalNightOut, 2, '.', ''),
                'total_toll_pass' => number_format((float) $grandTotalTollPass, 2, '.', ''),
                'grand_total_revenue' => number_format((float) $grandTotalRevenue, 2, '.', ''),
                'total_invoices_count' => count($invoices),
                'invoice_total_rental_charge' => number_format((float) array_sum(array_column($invoices, 'rental_charge_raw')), 2, '.', ''),
                'invoice_total_excess_km' => number_format((float) array_sum(array_column($invoices, 'excess_km_charge_raw')), 2, '.', ''),
                'invoice_total_vat' => number_format((float) array_sum(array_column($invoices, 'vat_amount_raw')), 2, '.', ''),
                'invoice_grand_total' => number_format((float) array_sum(array_column($invoices, 'total_amount_raw')), 2, '.', ''),
            ],
            'rows' => $rows,
            'invoices' => $invoices,
            'footer' => [
                'software_provider' => 'Software Provided by : '.(config('app.name') ?: 'AutoERP').' Computer Systems 077-2279318 011-2833538',
            ],
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, CustomerAgreement>  $agreements
     * @param  array<int, array<string, mixed>>  $agreementRows
     * @return array<int, array<string, mixed>>
     */
    private function buildInvoiceListing(
        AgreementContext $context,
        $agreements,
        array $agreementRows,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?int $customerId,
    ): array {
        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $invoices = [];

        // Check if database invoices exist in the Invoice module
        if (Schema::hasTable('invoices')) {
            $dbInvoices = DB::table('invoices')
                ->where('tenant_id', $context->tenantId)
                ->where('party_type', 'customer')
                ->where('invoice_date', '>=', $startDate)
                ->where('invoice_date', '<=', $endDate)
                ->when($customerId !== null, fn (\Illuminate\Database\Query\Builder $q) => $q->where('party_id', $customerId))
                ->orderBy('invoice_date')
                ->orderBy('invoice_number')
                ->get();

            if ($dbInvoices->isNotEmpty()) {
                $agreementsByCustomer = $agreements->groupBy('customer_id');

                foreach ($dbInvoices as $inv) {
                    $matchedAgreement = $agreementsByCustomer->get($inv->party_id)?->first();
                    $vehicleNo = '-';
                    if ($matchedAgreement !== null && $matchedAgreement->vehicleUses->isNotEmpty()) {
                        $primaryUse = $matchedAgreement->vehicleUses->firstWhere('replaces_use_id', null) ?? $matchedAgreement->vehicleUses->first();
                        $vehicleNo = $primaryUse?->vehicle_label_snapshot
                            ?? $primaryUse?->vehicle?->registration_number
                            ?? $primaryUse?->vehicle?->vehicle_number
                            ?? '-';
                    }

                    $customerDetails = ($inv->party_name_snapshot ?: 'Customer')
                        .' '.($inv->party_code_snapshot ? '('.$inv->party_code_snapshot.')' : '')
                        .' '.$start->format('d/m/y').' To '.$end->format('d/m/y');

                    $grandTotal = (float) $inv->grand_total;
                    $taxTotal = (float) $inv->tax_total;
                    $subtotal = (float) $inv->subtotal;

                    // Read lines to partition rental charge vs excess KM if available
                    $rentalCharge = $subtotal;
                    $excessKm = 0.0;

                    if (Schema::hasTable('invoice_lines')) {
                        $lines = DB::table('invoice_lines')
                            ->where('invoice_id', $inv->id)
                            ->where('tenant_id', $context->tenantId)
                            ->get();

                        if ($lines->isNotEmpty()) {
                            $excessKm = (float) $lines->filter(fn ($l) => str_contains(strtolower((string) $l->description), 'excess') || str_contains(strtolower((string) $l->description), 'mileage'))->sum('line_total');
                            $rentalCharge = max(0.0, $subtotal - $excessKm);
                        }
                    }

                    $invoices[] = [
                        'invoice_id' => $inv->id,
                        'invoice_number' => $inv->invoice_number,
                        'invoice_date' => CarbonImmutable::parse($inv->invoice_date)->format('d/m/y'),
                        'raw_invoice_date' => $inv->invoice_date,
                        'customer_details' => $customerDetails,
                        'vehicle_number' => $vehicleNo,
                        'rental_charge' => number_format($rentalCharge, 2, '.', ''),
                        'rental_charge_raw' => $rentalCharge,
                        'excess_km_charge' => number_format($excessKm, 2, '.', ''),
                        'excess_km_charge_raw' => $excessKm,
                        'vat_amount' => number_format($taxTotal, 2, '.', ''),
                        'vat_amount_raw' => $taxTotal,
                        'total_amount' => number_format($grandTotal, 2, '.', ''),
                        'total_amount_raw' => $grandTotal,
                    ];
                }
            }
        }

        // 2. Check if billing cycles exist in vehicle_rental_billing_cycles
        if (empty($invoices) && Schema::hasTable('vehicle_rental_billing_cycles')) {
            $cycles = DB::table('vehicle_rental_billing_cycles')
                ->where('tenant_id', $context->tenantId)
                ->where('period_from', '<=', $endDate)
                ->where('period_until', '>=', $startDate)
                ->when($customerId !== null, function ($q) use ($customerId) {
                    $agrIds = CustomerAgreement::query()->where('customer_id', $customerId)->pluck('id');
                    $q->whereIn('agreement_id', $agrIds);
                })
                ->get();

            if ($cycles->isNotEmpty()) {
                $agreementsById = $agreements->keyBy('id');
                foreach ($cycles as $cycle) {
                    $matchedAgreement = $agreementsById->get($cycle->agreement_id);
                    $vehicleNo = '-';
                    if ($matchedAgreement !== null && $matchedAgreement->vehicleUses->isNotEmpty()) {
                        $primaryUse = $matchedAgreement->vehicleUses->firstWhere('replaces_use_id', null) ?? $matchedAgreement->vehicleUses->first();
                        $vehicleNo = $primaryUse?->vehicle_label_snapshot
                            ?? $primaryUse?->vehicle?->registration_number
                            ?? $primaryUse?->vehicle?->vehicle_number
                            ?? '-';
                    }

                    $customerDetails = ($matchedAgreement?->customer?->name ?? 'Customer')
                        .' '.($matchedAgreement?->customer?->customer_number ? '('.$matchedAgreement->customer->customer_number.')' : '')
                        .' '.$start->format('d/m/y').' To '.$end->format('d/m/y');

                    $invoices[] = [
                        'invoice_id' => $cycle->invoice_id ?? $cycle->id,
                        'invoice_number' => $cycle->invoice_number ?? $cycle->cycle_reference,
                        'invoice_date' => CarbonImmutable::parse($cycle->period_until)->format('d/m/y'),
                        'raw_invoice_date' => $cycle->period_until,
                        'customer_details' => $customerDetails,
                        'vehicle_number' => $vehicleNo,
                        'rental_charge' => number_format((float) $cycle->base_rent_amount, 2, '.', ''),
                        'rental_charge_raw' => (float) $cycle->base_rent_amount,
                        'excess_km_charge' => number_format((float) $cycle->total_usage_charges, 2, '.', ''),
                        'excess_km_charge_raw' => (float) $cycle->total_usage_charges,
                        'vat_amount' => number_format((float) $cycle->tax_amount, 2, '.', ''),
                        'vat_amount_raw' => (float) $cycle->tax_amount,
                        'total_amount' => number_format((float) $cycle->total_billed_amount, 2, '.', ''),
                        'total_amount_raw' => (float) $cycle->total_billed_amount,
                    ];
                }
            }
        }

        // 3. Fallback: synthesize from agreement charges
        if (empty($invoices) && ! empty($agreementRows)) {
            foreach ($agreementRows as $index => $row) {
                $baseAmt = (string) $row['base_rent'];
                $excessAmt = (string) $row['excess_mileage'];
                $totalAmt = (string) $row['total_revenue'];
                $vatAmt = '0.00';
                $grossWithVat = $totalAmt;

                $invNo = 'EST-'.($row['agreement_reference'] ?? str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT));
                $customerDetails = $row['customer_name']
                    .($row['customer_code'] !== '-' ? ' ('.$row['customer_code'].')' : '')
                    .' '.$start->format('d/m/y').' To '.$end->format('d/m/y');

                $invoices[] = [
                    'invoice_id' => $row['agreement_id'],
                    'invoice_number' => $invNo,
                    'invoice_date' => $end->format('d/m/y'),
                    'raw_invoice_date' => $endDate,
                    'customer_details' => $customerDetails,
                    'vehicle_number' => $row['vehicle_number'],
                    'rental_charge' => number_format((float) $baseAmt, 2, '.', ''),
                    'rental_charge_raw' => (float) $baseAmt,
                    'excess_km_charge' => number_format((float) $excessAmt, 2, '.', ''),
                    'excess_km_charge_raw' => (float) $excessAmt,
                    'vat_amount' => number_format((float) $vatAmt, 2, '.', ''),
                    'vat_amount_raw' => (float) $vatAmt,
                    'total_amount' => number_format((float) $grossWithVat, 2, '.', ''),
                    'total_amount_raw' => (float) $grossWithVat,
                ];
            }
        }

        return $invoices;
    }
}
