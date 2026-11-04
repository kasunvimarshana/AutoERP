<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\Tenant\Models\TenantModel;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalExpenseType;
use Modules\VehicleRental\Enums\UsageChargeComponent;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\CustomerBaseCharge;
use Modules\VehicleRental\Models\CustomerUsageCharge;
use Modules\VehicleRental\Models\RentalExpense;

final class RentalRevenueReportService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalAuthorization $authorization,
    ) {}

    /**
     * @param  array{start_date: string, end_date: string, customer_id?: ?int, agreement_id?: ?int}  $params
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

            $tollExpenses = RentalExpense::query()
                ->forTenant($context->tenantId)
                ->where('customer_agreement_id', $agreement->id)
                ->where('expense_type', RentalExpenseType::Toll)
                ->where('expense_date', '>=', $startDate)
                ->where('expense_date', '<=', $endDate)
                ->whereNotIn('status', [RentalExpenseStatus::Rejected->value, RentalExpenseStatus::Reversed->value])
                ->get();
            $tollPass = (float) $tollExpenses->sum('gross_amount');

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
                'period_start' => $agreement->starts_on,
                'period_end' => $agreement->ends_on ?? 'Open-ended',
                'base_rent' => number_format($baseRent, 2, '.', ''),
                'excess_mileage' => number_format($excessMileage, 2, '.', ''),
                'overtime' => number_format($totalOvertime, 2, '.', ''),
                'night_out' => number_format($nightOut, 2, '.', ''),
                'toll_pass' => number_format($tollPass, 2, '.', ''),
                'total_revenue' => number_format($totalAgreementRevenue, 2, '.', ''),
            ];
        }

        $tenantName = TenantModel::query()->find($context->tenantId)?->name ?? (config('app.name') ?: 'AutoERP');

        return [
            'header' => [
                'company_name' => $tenantName,
                'report_title' => 'RENTAL REVENUE & BILLING SUMMARY REPORT',
                'duration' => $start->format('d/m/Y').' To '.$end->format('d/m/Y'),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'report_date' => now()->format('d/m/Y'),
            ],
            'filters' => [
                'customer_id' => $customerId,
                'agreement_id' => $agreementId,
            ],
            'summary' => [
                'total_agreements' => count($rows),
                'total_base_rent' => number_format($grandTotalBaseRent, 2, '.', ''),
                'total_excess_mileage' => number_format($grandTotalExcessMileage, 2, '.', ''),
                'total_overtime' => number_format($grandTotalOvertime, 2, '.', ''),
                'total_night_out' => number_format($grandTotalNightOut, 2, '.', ''),
                'total_toll_pass' => number_format($grandTotalTollPass, 2, '.', ''),
                'grand_total_revenue' => number_format($grandTotalRevenue, 2, '.', ''),
            ],
            'rows' => $rows,
            'footer' => [
                'software_provider' => 'Software Provided by : AutoERP Computer Systems',
            ],
        ];
    }
}
