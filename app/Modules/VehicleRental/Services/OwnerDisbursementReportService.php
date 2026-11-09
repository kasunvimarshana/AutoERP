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
use Modules\VehicleRental\Enums\RentalExpenseAllocationStatus;
use Modules\VehicleRental\Enums\RentalExpenseAllocationType;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\UsageChargeComponent;
use Modules\VehicleRental\Models\OwnerAgreement;
use Modules\VehicleRental\Models\OwnerBaseCharge;
use Modules\VehicleRental\Models\OwnerUsageCharge;
use Modules\VehicleRental\Models\RentalExpense;
use Modules\VehicleRental\Models\RentalExpenseAllocation;

final class OwnerDisbursementReportService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalAuthorization $authorization,
    ) {}

    /**
     * @param  array{start_date: string, end_date: string, supplier_id?: ?int, owner_agreement_id?: ?int, wht_rate?: ?float|int|string, report_type?: ?string}  $params
     * @return array<string, mixed>
     */
    public function generate(AgreementContext $context, array $params): array
    {
        $this->authorization->assertReport($context);

        if (empty($params['start_date']) || empty($params['end_date'])) {
            throw ValidationException::withMessages(['start_date' => ['Specify both start date and end date for the owner disbursement report.']]);
        }

        $startDate = (string) $params['start_date'];
        $endDate = (string) $params['end_date'];

        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->endOfDay();

        if ($end->lessThan($start)) {
            throw ValidationException::withMessages(['end_date' => ['End date must be on or after start date.']]);
        }

        $supplierId = ! empty($params['supplier_id']) ? (int) $params['supplier_id'] : null;
        $agreementId = ! empty($params['owner_agreement_id']) ? (int) $params['owner_agreement_id'] : null;
        $whtRate = isset($params['wht_rate']) && is_numeric($params['wht_rate']) ? (float) $params['wht_rate'] : 10.0;
        $reportType = (string) ($params['report_type'] ?? 'summary');

        $agreements = OwnerAgreement::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['party', 'vehicle'])
            ->when($supplierId !== null, fn (Builder $q) => $q->where('supplier_id', $supplierId))
            ->when($agreementId !== null, fn (Builder $q) => $q->where('id', $agreementId))
            ->whereIn('status', [AgreementStatus::Active->value, AgreementStatus::Closed->value])
            ->where('starts_on', '<=', $endDate)
            ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', $startDate))
            ->orderBy('reference')
            ->get();

        $rows = [];
        $whtLedger = [];
        $grandTotalBasePayable = 0.0;
        $grandTotalMileagePayable = 0.0;
        $grandTotalOvertimePayable = 0.0;
        $grandTotalGrossPayable = 0.0;
        $grandTotalWht = 0.0;
        $grandTotalDeductions = 0.0;
        $grandTotalNetPayable = 0.0;
        $runningBalance = 0.0;

        foreach ($agreements as $index => $agreement) {
            $baseCharges = OwnerBaseCharge::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->where('agreement_id', $agreement->id)
                ->whereNull('voided_at')
                ->where('period_from', '<=', $endDate)
                ->where('period_until', '>=', $startDate)
                ->get();
            $basePayable = (float) $baseCharges->sum('amount');

            $usageCharges = OwnerUsageCharge::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->where('agreement_id', $agreement->id)
                ->whereNull('voided_at')
                ->where('period_from', '<=', $endDate)
                ->where('period_until', '>=', $startDate)
                ->get();

            $mileagePayable = (float) $usageCharges->where('component', 'excess_mileage')->sum('amount');
            $normalOt = (float) $usageCharges->where('component', UsageChargeComponent::NormalOvertime->value)->sum('amount');
            $doubleOt = (float) $usageCharges->where('component', UsageChargeComponent::DoubleOvertime->value)->sum('amount');
            $tripleOt = (float) $usageCharges->where('component', UsageChargeComponent::TripleOvertime->value)->sum('amount');
            $nightOut = (float) $usageCharges->where('component', UsageChargeComponent::NightOut->value)->sum('amount');
            $overtimePayable = $normalOt + $doubleOt + $tripleOt + $nightOut;
            $grossPayable = $basePayable + $mileagePayable + $overtimePayable;

            // Expenses charged to owner (fuel, fines, or maintenance chargebacks)
            $allocatedDeductions = (float) RentalExpenseAllocation::query()
                ->where('target_owner_agreement_id', $agreement->id)
                ->where('allocation_type', RentalExpenseAllocationType::OwnerDeduction->value)
                ->where('status', RentalExpenseAllocationStatus::Confirmed->value)
                ->whereHas('expense', fn (Builder $q) => $q
                    ->where('expense_date', '>=', $startDate)
                    ->where('expense_date', '<=', $endDate)
                    ->whereNotIn('status', [RentalExpenseStatus::Rejected->value, RentalExpenseStatus::Reversed->value])
                )
                ->sum('total_amount');

            $directDeductions = (float) RentalExpense::query()
                ->forTenant($context->tenantId)
                ->where('supplier_id', $agreement->supplier_id)
                ->where('expense_date', '>=', $startDate)
                ->where('expense_date', '<=', $endDate)
                ->whereNotIn('status', [RentalExpenseStatus::Rejected->value, RentalExpenseStatus::Reversed->value])
                ->whereDoesntHave('allocations')
                ->when($agreement->vehicle_id !== null, fn (Builder $q) => $q->where('vehicle_id', $agreement->vehicle_id))
                ->sum('gross_amount');

            $deductions = $allocatedDeductions + $directDeductions;

            $whtAmount = $whtRate > 0 ? round(($grossPayable * $whtRate) / 100.0, 2) : 0.0;
            $netPayable = max(0.0, ($grossPayable - $deductions) - $whtAmount);

            $vehicleNo = $agreement->vehicle?->registration_number
                ?? $agreement->vehicle?->vehicle_number
                ?? 'N/A';

            $grandTotalBasePayable += $basePayable;
            $grandTotalMileagePayable += $mileagePayable;
            $grandTotalOvertimePayable += $overtimePayable;
            $grandTotalGrossPayable += $grossPayable;
            $grandTotalWht += $whtAmount;
            $grandTotalDeductions += $deductions;
            $grandTotalNetPayable += $netPayable;

            $rows[] = [
                'owner_agreement_id' => $agreement->id,
                'agreement_reference' => $agreement->reference,
                'supplier_id' => $agreement->supplier_id,
                'owner_name' => $agreement->party?->name ?? 'N/A',
                'owner_code' => $agreement->party?->code ?? '-',
                'vehicle_number' => $vehicleNo,
                'rental_basis' => $agreement->basis->value,
                'status' => $agreement->status->value,
                'period_start' => $agreement->starts_on,
                'period_end' => $agreement->ends_on ?? 'Open-ended',
                'base_payable' => number_format($basePayable, 2, '.', ''),
                'mileage_payable' => number_format($mileagePayable, 2, '.', ''),
                'overtime_payable' => number_format($overtimePayable, 2, '.', ''),
                'gross_payable' => number_format($grossPayable, 2, '.', ''),
                'wht_rate' => $whtRate,
                'wht_amount' => number_format($whtAmount, 2, '.', ''),
                'deductions' => number_format($deductions, 2, '.', ''),
                'net_payable' => number_format($netPayable, 2, '.', ''),
            ];

            if ($whtAmount > 0) {
                $runningBalance -= $whtAmount;
                $whtRef = 'CDN'.str_pad((string) (7000248 + $index), 7, '0', STR_PAD_LEFT);
                $whtLedger[] = [
                    'date' => $end->format('d/m/Y'),
                    'raw_date' => $endDate,
                    'reference' => $whtRef,
                    'description' => 'WHT '.number_format($whtRate, 0).'% '.($agreement->party?->name ?? 'N/A').($agreement->party?->code ? ' '.$agreement->party->code : '').' '.$vehicleNo,
                    'debit' => '',
                    'credit' => '-'.number_format($whtAmount, 2, '.', ''),
                    'balance' => number_format($runningBalance, 2, '.', ''),
                    'owner_name' => $agreement->party?->name ?? 'N/A',
                    'owner_code' => $agreement->party?->code ?? '-',
                    'vehicle_number' => $vehicleNo,
                    'wht_amount' => number_format($whtAmount, 2, '.', ''),
                ];
            }
        }

        $tenantName = TenantModel::query()->find($context->tenantId)?->name ?? (config('app.name') ?: 'AutoERP');

        return [
            'header' => [
                'company_name' => $tenantName,
                'system_title' => 'INTEGRATED RENT A CAR SYSTEM',
                'report_title' => $reportType === 'wht_ledger' ? 'Ledger Account From '.$start->format('d/m/Y').' To '.$end->format('d/m/Y') : 'VEHICLE OWNER DISBURSEMENT & PAYABLES REPORT',
                'account_code' => ':3305-000 WHT',
                'report_type' => $reportType,
                'wht_rate' => $whtRate,
                'duration' => $start->format('d/m/Y').' To '.$end->format('d/m/Y'),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'report_date' => now()->format('d/m/Y'),
            ],
            'filters' => [
                'supplier_id' => $supplierId,
                'owner_agreement_id' => $agreementId,
                'wht_rate' => $whtRate,
                'report_type' => $reportType,
            ],
            'summary' => [
                'total_owner_agreements' => count($rows),
                'total_base_payable' => number_format($grandTotalBasePayable, 2, '.', ''),
                'total_mileage_payable' => number_format($grandTotalMileagePayable, 2, '.', ''),
                'total_overtime_payable' => number_format($grandTotalOvertimePayable, 2, '.', ''),
                'total_gross_payable' => number_format($grandTotalGrossPayable, 2, '.', ''),
                'total_wht_amount' => number_format($grandTotalWht, 2, '.', ''),
                'total_deductions' => number_format($grandTotalDeductions, 2, '.', ''),
                'total_net_payable' => number_format($grandTotalNetPayable, 2, '.', ''),
            ],
            'rows' => $rows,
            'wht_ledger' => $whtLedger,
            'footer' => [
                'software_provider' => 'Software Provided by : '.(config('app.name') ?: 'AutoERP').' Computer Systems',
            ],
        ];
    }
}
