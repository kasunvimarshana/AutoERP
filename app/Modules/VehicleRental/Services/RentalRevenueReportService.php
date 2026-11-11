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
        $grandTotalBaseRent = 0.0;
        $grandTotalExcessMileage = 0.0;
        $grandTotalOvertime = 0.0;
        $grandTotalNightOut = 0.0;
        $grandTotalTollPass = 0.0;
        $grandTotalRevenue = 0.0;

        foreach ($agreements as $agreement) {
            $baseCharges = CustomerBaseCharge::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->where('agreement_id', $agreement->id)
                ->whereNull('voided_at')
                ->where('period_from', '<=', $endDate)
                ->where('period_until', '>=', $startDate)
                ->get();
            $baseRent = (float) $baseCharges->sum('amount');

            $usageCharges = CustomerUsageCharge::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->where('agreement_id', $agreement->id)
                ->whereNull('voided_at')
                ->where('period_from', '<=', $endDate)
                ->where('period_until', '>=', $startDate)
                ->get();

            $excessMileage = (float) $usageCharges->where('component', 'excess_mileage')->sum('amount');
            $normalOt = (float) $usageCharges->where('component', UsageChargeComponent::NormalOvertime->value)->sum('amount');
            $doubleOt = (float) $usageCharges->where('component', UsageChargeComponent::DoubleOvertime->value)->sum('amount');
            $tripleOt = (float) $usageCharges->where('component', UsageChargeComponent::TripleOvertime->value)->sum('amount');
            $nightOut = (float) $usageCharges->where('component', UsageChargeComponent::NightOut->value)->sum('amount');
            $totalOvertime = $normalOt + $doubleOt + $tripleOt;

            $allocatedCustomerRecoveries = (float) RentalExpenseAllocation::query()
                ->where('target_customer_agreement_id', $agreement->id)
                ->where('allocation_type', RentalExpenseAllocationType::CustomerRecovery->value)
                ->where('status', RentalExpenseAllocationStatus::Confirmed->value)
                ->whereHas('expense', fn (Builder $q) => $q
                    ->where('expense_date', '>=', $startDate)
                    ->where('expense_date', '<=', $endDate)
                    ->whereNotIn('status', [RentalExpenseStatus::Rejected->value, RentalExpenseStatus::Reversed->value])
                )
                ->sum('total_amount');

            $directTollPass = (float) RentalExpense::query()
                ->forTenant($context->tenantId)
                ->where('customer_agreement_id', $agreement->id)
                ->where('expense_type', RentalExpenseType::Toll)
                ->where('expense_date', '>=', $startDate)
                ->where('expense_date', '<=', $endDate)
                ->whereNotIn('status', [RentalExpenseStatus::Rejected->value, RentalExpenseStatus::Reversed->value])
                ->whereDoesntHave('allocations')
                ->sum('gross_amount');

            $tollPass = $allocatedCustomerRecoveries + $directTollPass;

            $totalAgreementRevenue = $baseRent + $excessMileage + $totalOvertime + $nightOut + $tollPass;

            $primaryUse = $agreement->vehicleUses->firstWhere('replaces_use_id', null) ?? $agreement->vehicleUses->first();
            $vehicleNo = $primaryUse?->vehicle_label_snapshot
                ?? $primaryUse?->vehicle?->registration_number
                ?? $primaryUse?->vehicle?->vehicle_number
                ?? 'N/A';

            $grandTotalBaseRent += $baseRent;
            $grandTotalExcessMileage += $excessMileage;
            $grandTotalOvertime += $totalOvertime;
            $grandTotalNightOut += $nightOut;
            $grandTotalTollPass += $tollPass;
            $grandTotalRevenue += $totalAgreementRevenue;

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
                'base_rent' => number_format($baseRent, 2, '.', ''),
                'excess_mileage' => number_format($excessMileage, 2, '.', ''),
                'overtime' => number_format($totalOvertime, 2, '.', ''),
                'night_out' => number_format($nightOut, 2, '.', ''),
                'toll_pass' => number_format($tollPass, 2, '.', ''),
                'total_revenue' => number_format($totalAgreementRevenue, 2, '.', ''),
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
                'total_base_rent' => number_format($grandTotalBaseRent, 2, '.', ''),
                'total_excess_mileage' => number_format($grandTotalExcessMileage, 2, '.', ''),
                'total_overtime' => number_format($grandTotalOvertime, 2, '.', ''),
                'total_night_out' => number_format($grandTotalNightOut, 2, '.', ''),
                'total_toll_pass' => number_format($grandTotalTollPass, 2, '.', ''),
                'grand_total_revenue' => number_format($grandTotalRevenue, 2, '.', ''),
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

        // If no formal posted invoices in invoices table, synthesize from agreement charges
        if (empty($invoices) && ! empty($agreementRows)) {
            foreach ($agreementRows as $index => $row) {
                $baseAmt = (float) $row['base_rent'];
                $excessAmt = (float) $row['excess_mileage'];
                $totalAmt = (float) $row['total_revenue'];
                $vatAmt = round(($totalAmt * 0.18), 2); // 18% standard VAT
                $grossWithVat = $totalAmt + $vatAmt;

                $invNo = 'DIN'.str_pad((string) (7000100 + $index), 7, '0', STR_PAD_LEFT);
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
                    'rental_charge' => number_format($baseAmt, 2, '.', ''),
                    'rental_charge_raw' => $baseAmt,
                    'excess_km_charge' => number_format($excessAmt, 2, '.', ''),
                    'excess_km_charge_raw' => $excessAmt,
                    'vat_amount' => number_format($vatAmt, 2, '.', ''),
                    'vat_amount_raw' => $vatAmt,
                    'total_amount' => number_format($grossWithVat, 2, '.', ''),
                    'total_amount_raw' => $grossWithVat,
                ];
            }
        }

        return $invoices;
    }
}
