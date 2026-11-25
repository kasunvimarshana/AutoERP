<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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
use Modules\VehicleRental\Enums\BillingCycleStatus;
use Modules\VehicleRental\Enums\DriverMode;
use Modules\VehicleRental\Enums\RentalExpenseAllocationStatus;
use Modules\VehicleRental\Enums\RentalExpenseAllocationType;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\UsageChargeComponent;
use Modules\VehicleRental\Models\BillingCycle;
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
     * @param  array{start_date: string, end_date: string, supplier_id?: ?int, owner_agreement_id?: ?int, vehicle_id?: ?int, wht_rate?: ?float|int|string, report_type?: ?string}  $params
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
        $vehicleId = ! empty($params['vehicle_id']) ? (int) $params['vehicle_id'] : null;
        $whtRateStr = isset($params['wht_rate']) && is_numeric($params['wht_rate'])
            ? $this->math->normalize((string) $params['wht_rate'], 6)
            : '10.000000';
        $whtRate = (float) $whtRateStr;
        $reportType = (string) ($params['report_type'] ?? 'summary');

        $agreements = OwnerAgreement::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['party', 'vehicle', 'vehicleUses.customerAgreement.customer', 'vehicleUses.vehicle'])
            ->when($supplierId !== null, fn (Builder $q) => $q->where('supplier_id', $supplierId))
            ->when($agreementId !== null, fn (Builder $q) => $q->where('id', $agreementId))
            ->when($vehicleId !== null, fn (Builder $q) => $q->where('vehicle_id', $vehicleId))
            ->whereIn('status', [AgreementStatus::Active->value, AgreementStatus::Closed->value])
            ->where('starts_on', '<=', $endDate)
            ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', $startDate))
            ->orderBy('reference')
            ->get();

        $rows = [];
        $whtLedger = [];
        $grandTotalBasePayable = '0.000000';
        $grandTotalMileagePayable = '0.000000';
        $grandTotalOvertimePayable = '0.000000';
        $grandTotalGrossPayable = '0.000000';
        $grandTotalWht = '0.000000';
        $grandTotalDeductions = '0.000000';
        $grandTotalNetPayable = '0.000000';
        $runningBalance = '0.000000';

        $targetAgreement = null;
        if ($agreementId !== null) {
            $targetAgreement = $agreements->firstWhere('id', $agreementId);
        } elseif ($vehicleId !== null) {
            $targetAgreement = $agreements->firstWhere('vehicle_id', $vehicleId);
        }
        if ($targetAgreement === null && $agreements->isNotEmpty()) {
            $targetAgreement = $agreements->first();
        }

        foreach ($agreements as $index => $agreement) {
            $baseCharges = OwnerBaseCharge::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->where('agreement_id', $agreement->id)
                ->whereNull('voided_at')
                ->where('period_from', '<=', $endDate)
                ->where('period_until', '>=', $startDate)
                ->get();
            $basePayable = $this->math->sum($baseCharges->pluck('amount')->map(fn ($v): string => (string) ($v ?? '0')), 6);

            $usageCharges = OwnerUsageCharge::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->where('agreement_id', $agreement->id)
                ->whereNull('voided_at')
                ->where('period_from', '<=', $endDate)
                ->where('period_until', '>=', $startDate)
                ->get();

            $mileagePayable = $this->math->sum($usageCharges->where('component', 'excess_mileage')->pluck('amount')->map(fn ($v): string => (string) ($v ?? '0')), 6);
            $normalOt = $this->math->sum($usageCharges->where('component', UsageChargeComponent::NormalOvertime->value)->pluck('amount')->map(fn ($v): string => (string) ($v ?? '0')), 6);
            $doubleOt = $this->math->sum($usageCharges->where('component', UsageChargeComponent::DoubleOvertime->value)->pluck('amount')->map(fn ($v): string => (string) ($v ?? '0')), 6);
            $tripleOt = $this->math->sum($usageCharges->where('component', UsageChargeComponent::TripleOvertime->value)->pluck('amount')->map(fn ($v): string => (string) ($v ?? '0')), 6);
            $nightOut = $this->math->sum($usageCharges->where('component', UsageChargeComponent::NightOut->value)->pluck('amount')->map(fn ($v): string => (string) ($v ?? '0')), 6);
            $overtimePayable = $this->math->sum([$normalOt, $doubleOt, $tripleOt, $nightOut], 6);

            // Include owner billing cycles in disbursements
            $ownerCycles = BillingCycle::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->where('owner_agreement_id', $agreement->id)
                ->where('status', '!=', BillingCycleStatus::Cancelled)
                ->where('period_from', '<=', $endDate)
                ->where('period_until', '>=', $startDate)
                ->get();

            foreach ($ownerCycles as $oc) {
                if ($oc->owner_base_charge_id === null) {
                    $basePayable = $this->math->add($basePayable, (string) ($oc->base_rent_amount ?? '0'), 6);
                }
                $mileagePayable = $this->math->add($mileagePayable, (string) ($oc->excess_km_amount ?? '0'), 6);
                $ocAdd = $this->math->sum([
                    (string) ($oc->overtime_amount ?? '0'),
                    (string) ($oc->night_out_amount ?? '0'),
                    (string) ($oc->tolls_amount ?? '0'),
                    (string) ($oc->additions_amount ?? '0'),
                ], 6);
                $ocOther = $this->math->sub($ocAdd, (string) ($oc->deductions_amount ?? '0'), 6);
                if ($this->math->compare($ocOther, '0', 6) > 0) {
                    $overtimePayable = $this->math->add($overtimePayable, $ocOther, 6);
                }
            }

            $grossPayable = $this->math->sum([$basePayable, $mileagePayable, $overtimePayable], 6);

            // Expenses charged to owner (fuel, fines, or maintenance chargebacks)
            $allocatedDeductions = $this->math->sum(
                RentalExpenseAllocation::query()
                    ->where('target_owner_agreement_id', $agreement->id)
                    ->where('allocation_type', RentalExpenseAllocationType::OwnerDeduction->value)
                    ->where('status', RentalExpenseAllocationStatus::Confirmed->value)
                    ->whereHas('expense', fn (Builder $q) => $q
                        ->where('expense_date', '>=', $startDate)
                        ->where('expense_date', '<=', $endDate)
                        ->whereNotIn('status', [RentalExpenseStatus::Rejected->value, RentalExpenseStatus::Reversed->value])
                    )
                    ->pluck('total_amount')
                    ->map(fn ($v): string => (string) ($v ?? '0')),
                6,
            );

            $directDeductions = $this->math->sum(
                RentalExpense::query()
                    ->forTenant($context->tenantId)
                    ->where('supplier_id', $agreement->supplier_id)
                    ->where('expense_date', '>=', $startDate)
                    ->where('expense_date', '<=', $endDate)
                    ->whereNotIn('status', [RentalExpenseStatus::Rejected->value, RentalExpenseStatus::Reversed->value])
                    ->whereDoesntHave('allocations')
                    ->when($agreement->vehicle_id !== null, fn (Builder $q) => $q->where('vehicle_id', $agreement->vehicle_id))
                    ->pluck('gross_amount')
                    ->map(fn ($v): string => (string) ($v ?? '0')),
                6,
            );

            $deductions = $this->math->add($allocatedDeductions, $directDeductions, 6);

            $whtAmount = $this->math->compare($whtRateStr, '0', 6) > 0
                ? $this->math->div($this->math->mul($grossPayable, $whtRateStr, 6), '100', 2)
                : '0.00';
            $netBeforeMax = $this->math->sub($this->math->sub($grossPayable, $deductions, 6), $whtAmount, 6);
            $netPayable = $this->math->compare($netBeforeMax, '0', 6) > 0 ? $netBeforeMax : '0.000000';

            $vehicleNo = $agreement->vehicle?->registration_number
                ?? $agreement->vehicle?->vehicle_number
                ?? 'N/A';

            $grandTotalBasePayable = $this->math->add($grandTotalBasePayable, $basePayable, 6);
            $grandTotalMileagePayable = $this->math->add($grandTotalMileagePayable, $mileagePayable, 6);
            $grandTotalOvertimePayable = $this->math->add($grandTotalOvertimePayable, $overtimePayable, 6);
            $grandTotalGrossPayable = $this->math->add($grandTotalGrossPayable, $grossPayable, 6);
            $grandTotalWht = $this->math->add($grandTotalWht, $whtAmount, 6);
            $grandTotalDeductions = $this->math->add($grandTotalDeductions, $deductions, 6);
            $grandTotalNetPayable = $this->math->add($grandTotalNetPayable, $netPayable, 6);

            $rows[] = [
                'owner_agreement_id' => $agreement->id,
                'agreement_reference' => $agreement->reference,
                'supplier_id' => $agreement->supplier_id,
                'owner_name' => $agreement->party?->name ?? 'N/A',
                'owner_code' => $agreement->party?->code ?? '-',
                'vehicle_number' => $vehicleNo,
                'rental_basis' => $agreement->basis->value,
                'status' => $agreement->status->value,
                'period_start' => $agreement->starts_on->toDateString(),
                'period_end' => $agreement->ends_on?->toDateString() ?? 'Open-ended',
                'base_payable' => $this->math->add($basePayable, '0', 2),
                'mileage_payable' => $this->math->add($mileagePayable, '0', 2),
                'overtime_payable' => $this->math->add($overtimePayable, '0', 2),
                'gross_payable' => $this->math->add($grossPayable, '0', 2),
                'wht_rate' => $whtRate,
                'wht_amount' => $this->math->add($whtAmount, '0', 2),
                'deductions' => $this->math->add($deductions, '0', 2),
                'net_payable' => $this->math->add($netPayable, '0', 2),
            ];

            if ($this->math->compare($whtAmount, '0', 2) > 0) {
                $runningBalance = $this->math->sub($runningBalance, $whtAmount, 6);
                $whtRef = 'CDN'.str_pad((string) (7000248 + $index), 7, '0', STR_PAD_LEFT);
                $whtLedger[] = [
                    'date' => $end->format('d/m/Y'),
                    'raw_date' => $endDate,
                    'reference' => $whtRef,
                    'description' => 'WHT '.number_format($whtRate, 0).'% '.($agreement->party?->name ?? 'N/A').($agreement->party?->code ? ' '.$agreement->party->code : '').' '.$vehicleNo,
                    'debit' => '',
                    'credit' => '-'.$this->math->add($whtAmount, '0', 2),
                    'balance' => $this->math->add($runningBalance, '0', 2),
                    'owner_name' => $agreement->party?->name ?? 'N/A',
                    'owner_code' => $agreement->party?->code ?? '-',
                    'vehicle_number' => $vehicleNo,
                    'wht_amount' => $this->math->add($whtAmount, '0', 2),
                ];
            }
        }

        // Build Statement of Accounts (Vehicle Wise Detail I & Detail II)
        $statement = null;
        if ($targetAgreement !== null) {
            $statement = $this->buildVehicleWiseStatement($context, $targetAgreement, $start, $end, $whtRateStr, $reportType);
        }

        $tenantName = TenantModel::query()->find($context->tenantId)?->name ?? (config('app.name') ?: 'AutoERP');

        $reportTitle = match ($reportType) {
            'wht_ledger' => 'Ledger Account From '.$start->format('d/m/Y').' To '.$end->format('d/m/Y'),
            'statement_detail_1' => 'Statement of Accounts - Vehicle Wise (Detail)',
            'statement_detail_2' => 'Statement of Accounts - Vehicle Wise (Detail II)',
            default => 'VEHICLE OWNER DISBURSEMENT & PAYABLES REPORT',
        };

        return [
            'header' => [
                'company_name' => $tenantName,
                'system_title' => 'INTEGRATED RENT A CAR SYSTEM',
                'report_title' => $reportTitle,
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
                'vehicle_id' => $vehicleId,
                'wht_rate' => $whtRate,
                'report_type' => $reportType,
            ],
            'summary' => [
                'total_owner_agreements' => count($rows),
                'total_base_payable' => $this->math->add($grandTotalBasePayable, '0', 2),
                'total_mileage_payable' => $this->math->add($grandTotalMileagePayable, '0', 2),
                'total_overtime_payable' => $this->math->add($grandTotalOvertimePayable, '0', 2),
                'total_gross_payable' => $this->math->add($grandTotalGrossPayable, '0', 2),
                'total_wht_amount' => $this->math->add($grandTotalWht, '0', 2),
                'total_deductions' => $this->math->add($grandTotalDeductions, '0', 2),
                'total_net_payable' => $this->math->add($grandTotalNetPayable, '0', 2),
            ],
            'rows' => $rows,
            'wht_ledger' => $whtLedger,
            'statement' => $statement,
            'footer' => [
                'software_provider' => 'Software Provided by : '.(config('app.name') ?: 'AutoERP').' Computer Systems 077-2279318 011-2833538',
            ],
        ];
    }

    /**
     * Build dedicated single-vehicle Statement of Accounts matching legacy Visual FoxPro reports:
     * pmcre1vehstat_b.frx (Detail I) and pmcre1vehstat_c.frx (Detail II).
     *
     * @return array<string, mixed>
     */
    private function buildVehicleWiseStatement(
        AgreementContext $context,
        OwnerAgreement $agreement,
        CarbonImmutable $start,
        CarbonImmutable $end,
        string $whtRateStr,
        string $reportType,
    ): array {
        $startDate = $start->toDateString();
        $endDate = $end->toDateString();
        $zero = '0.000000';

        $vehicle = $agreement->vehicle;
        $vehicleNo = $vehicle?->registration_number
            ?? $vehicle?->vehicle_number
            ?? 'N/A';
        $ownerName = $agreement->party?->name ?? 'N/A';
        $ownerCode = $agreement->party?->code ?? '';

        // Determine lessee / customer hirer
        $primaryUse = $agreement->vehicleUses()
            ->with(['customerAgreement.customer'])
            ->where('starts_at', '<=', $end)
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $start))
            ->first();
        $clientName = $primaryUse?->customerAgreement?->customer?->name
            ?? $primaryUse?->customerAgreement?->party_name_snapshot
            ?? 'COMPANY FLEET HIRE';

        $driverBadge = $agreement->driver_mode === DriverMode::WithDriver ? 'with Driver' : 'without Driver';

        // 1. Balance Brought Forward (Balance B/F as of period start)
        $priorBase = $this->math->normalize((string) OwnerBaseCharge::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->where('agreement_id', $agreement->id)
            ->whereNull('voided_at')
            ->where('period_until', '<', $startDate)
            ->sum('amount'), 6);

        $priorUsage = $this->math->normalize((string) OwnerUsageCharge::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->where('agreement_id', $agreement->id)
            ->whereNull('voided_at')
            ->where('period_until', '<', $startDate)
            ->sum('amount'), 6);

        $priorDeductions = $this->math->normalize((string) RentalExpenseAllocation::query()
            ->where('target_owner_agreement_id', $agreement->id)
            ->where('allocation_type', RentalExpenseAllocationType::OwnerDeduction->value)
            ->where('status', RentalExpenseAllocationStatus::Confirmed->value)
            ->whereHas('expense', fn (Builder $q) => $q
                ->where('expense_date', '<', $startDate)
                ->whereNotIn('status', [RentalExpenseStatus::Rejected->value, RentalExpenseStatus::Reversed->value])
            )
            ->sum('total_amount'), 6);

        $priorSettlements = $zero;
        if (Schema::hasTable('payments')) {
            $priorSettlements = $this->math->normalize((string) DB::table('payments')
                ->where('tenant_id', $context->tenantId)
                ->where('party_id', $agreement->supplier_id)
                ->where('direction', 'outbound')
                ->where('payment_date', '<', $startDate)
                ->whereNull('voided_at')
                ->whereNotIn('document_status', ['voided', 'rejected', 'cancelled'])
                ->sum('total_amount'), 6);
        }

        $priorGross = $this->math->add($priorBase, $priorUsage, 6);
        $priorWht = $this->math->compare($whtRateStr, $zero, 6) > 0
            ? $this->math->div($this->math->mul($priorGross, $whtRateStr, 6), '100', 2)
            : '0.00';
        $balanceBfRawStr = $this->math->sub(
            $this->math->sub(
                $this->math->sub($priorGross, $priorSettlements, 6),
                $priorDeductions,
                6
            ),
            $priorWht,
            6
        );

        // 2. Rental & Other Payables during current period
        $baseCharges = OwnerBaseCharge::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->where('agreement_id', $agreement->id)
            ->whereNull('voided_at')
            ->where('period_from', '<=', $endDate)
            ->where('period_until', '>=', $startDate)
            ->orderBy('period_from')
            ->get();

        $usageCharges = OwnerUsageCharge::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->where('agreement_id', $agreement->id)
            ->whereNull('voided_at')
            ->where('period_from', '<=', $endDate)
            ->where('period_until', '>=', $startDate)
            ->orderBy('period_from')
            ->get();

        $normalOtCharges = $usageCharges->where('component', UsageChargeComponent::NormalOvertime->value);
        $doubleOtCharges = $usageCharges->where('component', UsageChargeComponent::DoubleOvertime->value);
        $tripleOtCharges = $usageCharges->where('component', UsageChargeComponent::TripleOvertime->value);
        $nightOutCharges = $usageCharges->where('component', UsageChargeComponent::NightOut->value);
        $mileageCharges = $usageCharges->where('component', 'excess_mileage');
        $otherUsageCharges = $usageCharges->whereNotIn('component', [
            UsageChargeComponent::NormalOvertime->value,
            UsageChargeComponent::DoubleOvertime->value,
            UsageChargeComponent::TripleOvertime->value,
            UsageChargeComponent::NightOut->value,
            'excess_mileage',
        ]);

        $basePayable = $this->math->sum($baseCharges->pluck('amount')->map(fn ($v): string => (string) $v), 6);
        $normalOtAmount = $this->math->sum($normalOtCharges->pluck('amount')->map(fn ($v): string => (string) $v), 6);
        $normalOtHours = $this->math->sum($normalOtCharges->pluck('quantity')->map(fn ($v): string => (string) $v), 6);
        $normalOtRate = $this->math->compare($normalOtHours, $zero, 6) > 0
            ? $this->math->div($normalOtAmount, $normalOtHours, 2)
            : '1500.00';

        $doubleOtAmount = $this->math->sum($doubleOtCharges->pluck('amount')->map(fn ($v): string => (string) $v), 6);
        $doubleOtHours = $this->math->sum($doubleOtCharges->pluck('quantity')->map(fn ($v): string => (string) $v), 6);
        $doubleOtRate = $this->math->compare($doubleOtHours, $zero, 6) > 0
            ? $this->math->div($doubleOtAmount, $doubleOtHours, 2)
            : '0.00';

        $tripleOtHours = $this->math->sum($tripleOtCharges->pluck('quantity')->map(fn ($v): string => (string) $v), 6);
        $tripleOtAmount = $this->math->sum($tripleOtCharges->pluck('amount')->map(fn ($v): string => (string) $v), 6);

        $nightOutAmount = $this->math->sum($nightOutCharges->pluck('amount')->map(fn ($v): string => (string) $v), 6);
        $mileageAmount = $this->math->sum($mileageCharges->pluck('amount')->map(fn ($v): string => (string) $v), 6);
        $otherChargesAmount = $this->math->sum($otherUsageCharges->pluck('amount')->map(fn ($v): string => (string) $v), 6);

        // Line items for Detail I
        $payableItems = [];
        $periodLabel = 'For the Period '.$start->format('d/m/y').' To '.$end->format('d/m/y');
        $unitLabel = 'One Month';

        $payableItems[] = [
            'date_reference' => $periodLabel,
            'description' => 'Vehicle Hire Payable',
            'unit' => $unitLabel,
            'rate' => $this->math->add($basePayable, '0', 2),
            'amount' => $this->math->add($basePayable, '0', 2),
            'amount_raw' => $this->math->add($basePayable, '0', 2),
        ];

        if ($this->math->compare($normalOtAmount, $zero, 6) > 0 || $this->math->compare($normalOtHours, $zero, 6) > 0) {
            $payableItems[] = [
                'date_reference' => '',
                'description' => "Driver's Overtime Normal",
                'unit' => $this->math->add($normalOtHours, '0', 2).' Hrs',
                'rate' => $this->math->add($normalOtRate, '0', 2),
                'amount' => $this->math->add($normalOtAmount, '0', 2),
                'amount_raw' => $this->math->add($normalOtAmount, '0', 2),
            ];
        }

        if ($this->math->compare($doubleOtAmount, $zero, 6) > 0 || $this->math->compare($doubleOtHours, $zero, 6) > 0) {
            $payableItems[] = [
                'date_reference' => '',
                'description' => "Driver's Overtime Double",
                'unit' => $this->math->add($doubleOtHours, '0', 2).' Hrs',
                'rate' => $this->math->add($doubleOtRate, '0', 2),
                'amount' => $this->math->add($doubleOtAmount, '0', 2),
                'amount_raw' => $this->math->add($doubleOtAmount, '0', 2),
            ];
        }

        $otherCombined = $this->math->add(
            $this->math->add($nightOutAmount, $mileageAmount, 6),
            $otherChargesAmount,
            6
        );
        if ($this->math->compare($otherCombined, $zero, 6) > 0) {
            $payableItems[] = [
                'date_reference' => $end->format('d/m/y').'  PPV'.str_pad((string) (7000850 + $agreement->id), 7, '0', STR_PAD_LEFT),
                'description' => $vehicleNo.' '.$start->format('d/m/y').' '.$end->format('d/m/y'),
                'unit' => '-',
                'rate' => '-',
                'amount' => $this->math->add($otherCombined, '0', 2),
                'amount_raw' => $this->math->add($otherCombined, '0', 2),
            ];
        }

        $totalOtAmount = $this->math->add(
            $this->math->add($normalOtAmount, $doubleOtAmount, 6),
            $tripleOtAmount,
            6
        );
        $totalPayablesRawStr = $this->math->add(
            $this->math->add($basePayable, $totalOtAmount, 6),
            $otherCombined,
            6
        );

        // Total commercial km from running charts during period, or fallback to mileage charges quantity
        $useIds = $agreement->vehicleUses()->pluck('id')->all();
        $totalKmRaw = 0.0;
        if (! empty($useIds) && Schema::hasTable('vehicle_rental_running_charts')) {
            $totalKmRaw = (float) DB::table('vehicle_rental_running_charts')
                ->where('tenant_id', $context->tenantId)
                ->whereIn('vehicle_use_id', $useIds)
                ->where('starts_at', '<=', $end)
                ->where('ends_at', '>=', $start)
                ->sum('commercial_km');
        }
        if ($totalKmRaw <= 0.0 && $mileageCharges->isNotEmpty()) {
            $totalKmRaw = (float) $mileageCharges->sum('quantity');
        }

        $nonAcRateStr = $this->math->normalize((string) ($agreement->terms['non_ac_rate'] ?? $agreement->terms['base_rate'] ?? '0'), 2);
        $driverSalaryStr = $this->math->normalize((string) ($agreement->terms['driver_rate'] ?? '0'), 2);
        $driverBataStr = $this->math->add($nightOutAmount, '0', 2);

        // 3. Detail II Operational Matrix Row
        $detailIiRow = [
            'client_name' => $clientName,
            'hire_start' => $start->format('d/m/y'),
            'hire_finish' => $end->format('d/m/y'),
            'unit' => $unitLabel,
            'calculated_by' => 'Normal',
            'total_km' => number_format($totalKmRaw, 0, '.', ','),
            'non_ac_rate' => $nonAcRateStr,
            'hire_amount' => $this->math->add($basePayable, '0', 2),
            'no_hrs' => $this->math->add($normalOtHours, '0', 2),
            'do_hrs' => $this->math->add($doubleOtHours, '0', 2),
            'to_hrs' => $this->math->add($tripleOtHours, '0', 2),
            'ot_amount' => $this->math->add($totalOtAmount, '0', 2),
            'driver_salary' => $driverSalaryStr,
            'driver_bata' => $driverBataStr,
            'other_charges' => $this->math->add($otherCombined, '0', 2),
            'vat_amount' => '0.00',
            'total_payable' => $this->math->add($totalPayablesRawStr, '0', 2),
        ];

        // 4. Settlements during period (Outbound payments / cheques to owner)
        $settlements = [];
        $totalSettlementsRawStr = $zero;
        if (Schema::hasTable('payments')) {
            $payments = DB::table('payments')
                ->where('tenant_id', $context->tenantId)
                ->where('party_id', $agreement->supplier_id)
                ->where('direction', 'outbound')
                ->where('payment_date', '>=', $startDate)
                ->where('payment_date', '<=', $endDate)
                ->whereNull('voided_at')
                ->whereNotIn('document_status', ['voided', 'rejected', 'cancelled'])
                ->orderBy('payment_date')
                ->get();

            foreach ($payments as $p) {
                $amtStr = $this->math->normalize((string) $p->total_amount, 6);
                $amtFormatted = $this->math->add($amtStr, '0', 2);
                $totalSettlementsRawStr = $this->math->add($totalSettlementsRawStr, $amtStr, 6);
                $settlements[] = [
                    'date' => CarbonImmutable::parse($p->payment_date)->format('d/m/y'),
                    'reference' => $p->payment_number,
                    'description' => $vehicleNo.($p->cheque_number ? " {$p->cheque_number} (cheque)" : ' Bank Transfer'),
                    'amount' => '( '.$amtFormatted.' )',
                    'amount_raw' => $amtFormatted,
                ];
            }
        }

        // 5. Deductions for Repair / Maintenance
        $repairAllocations = RentalExpenseAllocation::query()
            ->where('target_owner_agreement_id', $agreement->id)
            ->where('allocation_type', RentalExpenseAllocationType::OwnerDeduction->value)
            ->where('status', RentalExpenseAllocationStatus::Confirmed->value)
            ->whereHas('expense', fn (Builder $q) => $q
                ->where('expense_date', '>=', $startDate)
                ->where('expense_date', '<=', $endDate)
                ->whereNotIn('status', [RentalExpenseStatus::Rejected->value, RentalExpenseStatus::Reversed->value])
            )
            ->with('expense')
            ->get();

        $repairDeductions = [];
        $totalRepairDeductionsRawStr = $zero;
        foreach ($repairAllocations as $alloc) {
            $amtStr = $this->math->normalize((string) $alloc->total_amount, 6);
            $amtFormatted = $this->math->add($amtStr, '0', 2);
            $totalRepairDeductionsRawStr = $this->math->add($totalRepairDeductionsRawStr, $amtStr, 6);
            $exp = $alloc->expense;
            $repairDeductions[] = [
                'date' => $exp ? CarbonImmutable::parse($exp->expense_date)->format('d/m/y') : $end->format('d/m/y'),
                'reference' => $exp?->expense_number ?? 'CDN'.str_pad((string) (7000290 + $alloc->id), 7, '0', STR_PAD_LEFT),
                'description' => $vehicleNo.($exp?->description ? ' '.$exp->description : ' Maintenance / Repair'),
                'amount' => '( '.$amtFormatted.' )',
                'amount_raw' => $amtFormatted,
            ];
        }

        // 6. Balance Over Paid / Balance Payable Closing Calculation
        $periodWhtStr = $this->math->compare($whtRateStr, $zero, 6) > 0
            ? $this->math->div($this->math->mul($totalPayablesRawStr, $whtRateStr, 6), '100', 2)
            : '0.00';
        $closingBalanceRawStr = $this->math->sub(
            $this->math->sub(
                $this->math->sub(
                    $this->math->add($balanceBfRawStr, $totalPayablesRawStr, 6),
                    $totalSettlementsRawStr,
                    6
                ),
                $totalRepairDeductionsRawStr,
                6
            ),
            $periodWhtStr,
            6
        );

        $balanceBfFormatted = $this->math->isNegative($balanceBfRawStr, 6)
            ? '( '.$this->math->sub('0', $balanceBfRawStr, 2).' )'
            : $this->math->add($balanceBfRawStr, '0', 2);

        if ($this->math->isNegative($closingBalanceRawStr, 6)) {
            $balanceClosingLabel = 'Balance Over Paid '.$end->format('d/m/y');
            $balanceClosingFormatted = '( '.$this->math->sub('0', $closingBalanceRawStr, 2).' )';
        } else {
            $balanceClosingLabel = 'Balance Payable '.$end->format('d/m/y');
            $balanceClosingFormatted = $this->math->add($closingBalanceRawStr, '0', 2);
        }

        $accountRef = $reportType === 'statement_detail_2' ? 'Detail II' : 'Detail';

        return [
            'owner_agreement_id' => $agreement->id,
            'agreement_reference' => $agreement->reference,
            'vehicle_number' => $vehicleNo,
            'owner_name' => $ownerName,
            'owner_code' => $ownerCode,
            'client_name' => $clientName,
            'driver_badge' => $driverBadge,
            'period' => $start->format('d/m/y').' To '.$end->format('d/m/y'),
            'report_date' => now()->format('d/m/y'),
            'account_reference' => $accountRef,
            'balance_bf_label' => 'Balance B/F '.$start->format('d/m/y'),
            'balance_bf' => $balanceBfFormatted,
            'balance_bf_raw' => $this->math->add($balanceBfRawStr, '0', 2),
            'payables' => $payableItems,
            'total_payable' => $this->math->add($totalPayablesRawStr, '0', 2),
            'total_payable_raw' => $this->math->add($totalPayablesRawStr, '0', 2),
            'detail_ii_row' => $detailIiRow,
            'settlements' => $settlements,
            'total_settlements' => $this->math->add($totalSettlementsRawStr, '0', 2),
            'total_settlements_raw' => $this->math->add($totalSettlementsRawStr, '0', 2),
            'repair_deductions' => $repairDeductions,
            'total_repair_deductions' => $this->math->add($totalRepairDeductionsRawStr, '0', 2),
            'total_repair_deductions_raw' => $this->math->add($totalRepairDeductionsRawStr, '0', 2),
            'wht_rate' => (float) $whtRateStr,
            'wht_amount' => $periodWhtStr,
            'balance_closing_label' => $balanceClosingLabel,
            'balance_closing' => $balanceClosingFormatted,
            'balance_closing_raw' => $this->math->add($closingBalanceRawStr, '0', 2),
        ];
    }
}
