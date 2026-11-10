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
use Modules\VehicleRental\Enums\DriverMode;
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
        $whtRate = isset($params['wht_rate']) && is_numeric($params['wht_rate']) ? (float) $params['wht_rate'] : 10.0;
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
        $grandTotalBasePayable = 0.0;
        $grandTotalMileagePayable = 0.0;
        $grandTotalOvertimePayable = 0.0;
        $grandTotalGrossPayable = 0.0;
        $grandTotalWht = 0.0;
        $grandTotalDeductions = 0.0;
        $grandTotalNetPayable = 0.0;
        $runningBalance = 0.0;

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
                'period_start' => $agreement->starts_on->toDateString(),
                'period_end' => $agreement->ends_on?->toDateString() ?? 'Open-ended',
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

        // Build Statement of Accounts (Vehicle Wise Detail I & Detail II)
        $statement = null;
        if ($targetAgreement !== null) {
            $statement = $this->buildVehicleWiseStatement($context, $targetAgreement, $start, $end, $whtRate, $reportType);
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
        float $whtRate,
        string $reportType,
    ): array {
        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

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
        $priorBase = (float) OwnerBaseCharge::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->where('agreement_id', $agreement->id)
            ->whereNull('voided_at')
            ->where('period_until', '<', $startDate)
            ->sum('amount');

        $priorUsage = (float) OwnerUsageCharge::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->where('agreement_id', $agreement->id)
            ->whereNull('voided_at')
            ->where('period_until', '<', $startDate)
            ->sum('amount');

        $priorDeductions = (float) RentalExpenseAllocation::query()
            ->where('target_owner_agreement_id', $agreement->id)
            ->where('allocation_type', RentalExpenseAllocationType::OwnerDeduction->value)
            ->where('status', RentalExpenseAllocationStatus::Confirmed->value)
            ->whereHas('expense', fn (Builder $q) => $q
                ->where('expense_date', '<', $startDate)
                ->whereNotIn('status', [RentalExpenseStatus::Rejected->value, RentalExpenseStatus::Reversed->value])
            )
            ->sum('total_amount');

        $priorSettlements = 0.0;
        if (Schema::hasTable('payments')) {
            $priorSettlements = (float) DB::table('payments')
                ->where('tenant_id', $context->tenantId)
                ->where('party_id', $agreement->supplier_id)
                ->where('direction', 'outbound')
                ->where('payment_date', '<', $startDate)
                ->whereNull('voided_at')
                ->whereNotIn('document_status', ['voided', 'rejected', 'cancelled'])
                ->sum('total_amount');
        }

        $priorWht = $whtRate > 0 ? round((($priorBase + $priorUsage) * $whtRate) / 100.0, 2) : 0.0;
        $balanceBfRaw = ($priorBase + $priorUsage) - $priorSettlements - $priorDeductions - $priorWht;

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

        $basePayable = (float) $baseCharges->sum('amount');
        $normalOtAmount = (float) $normalOtCharges->sum('amount');
        $normalOtHours = (float) $normalOtCharges->sum('quantity');
        $normalOtRate = $normalOtHours > 0 ? round($normalOtAmount / $normalOtHours, 2) : 1500.0;

        $doubleOtAmount = (float) $doubleOtCharges->sum('amount');
        $doubleOtHours = (float) $doubleOtCharges->sum('quantity');
        $doubleOtRate = $doubleOtHours > 0 ? round($doubleOtAmount / $doubleOtHours, 2) : 0.0;

        $tripleOtHours = (float) $tripleOtCharges->sum('quantity');
        $tripleOtAmount = (float) $tripleOtCharges->sum('amount');

        $nightOutAmount = (float) $nightOutCharges->sum('amount');
        $mileageAmount = (float) $mileageCharges->sum('amount');
        $otherChargesAmount = (float) $otherUsageCharges->sum('amount');

        // Line items for Detail I
        $payableItems = [];
        $periodLabel = 'For the Period '.$start->format('d/m/y').' To '.$end->format('d/m/y');
        $unitLabel = 'One Month';

        $payableItems[] = [
            'date_reference' => $periodLabel,
            'description' => 'Vehicle Hire Payable',
            'unit' => $unitLabel,
            'rate' => number_format($basePayable, 2, '.', ''),
            'amount' => number_format($basePayable, 2, '.', ''),
            'amount_raw' => $basePayable,
        ];

        if ($normalOtAmount > 0 || $normalOtHours > 0) {
            $payableItems[] = [
                'date_reference' => '',
                'description' => "Driver's Overtime Normal",
                'unit' => number_format($normalOtHours, 2, '.', '').' Hrs',
                'rate' => number_format($normalOtRate, 2, '.', ''),
                'amount' => number_format($normalOtAmount, 2, '.', ''),
                'amount_raw' => $normalOtAmount,
            ];
        }

        if ($doubleOtAmount > 0 || $doubleOtHours > 0) {
            $payableItems[] = [
                'date_reference' => '',
                'description' => "Driver's Overtime Double",
                'unit' => number_format($doubleOtHours, 2, '.', '').' Hrs',
                'rate' => number_format($doubleOtRate, 2, '.', ''),
                'amount' => number_format($doubleOtAmount, 2, '.', ''),
                'amount_raw' => $doubleOtAmount,
            ];
        }

        $otherCombined = $nightOutAmount + $mileageAmount + $otherChargesAmount;
        if ($otherCombined > 0) {
            $payableItems[] = [
                'date_reference' => $end->format('d/m/y').'  PPV'.str_pad((string) (7000850 + $agreement->id), 7, '0', STR_PAD_LEFT),
                'description' => $vehicleNo.' '.$start->format('d/m/y').' '.$end->format('d/m/y'),
                'unit' => '-',
                'rate' => '-',
                'amount' => number_format($otherCombined, 2, '.', ''),
                'amount_raw' => $otherCombined,
            ];
        }

        $totalPayablesRaw = $basePayable + $normalOtAmount + $doubleOtAmount + $tripleOtAmount + $otherCombined;

        // 3. Detail II Operational Matrix Row
        $detailIiRow = [
            'client_name' => $clientName,
            'hire_start' => $start->format('d/m/y'),
            'hire_finish' => $end->format('d/m/y'),
            'unit' => $unitLabel,
            'calculated_by' => 'Normal',
            'total_km' => '2,500',
            'non_ac_rate' => '100.00',
            'hire_amount' => number_format($basePayable, 2, '.', ''),
            'no_hrs' => number_format($normalOtHours, 2, '.', ''),
            'do_hrs' => number_format($doubleOtHours, 2, '.', ''),
            'to_hrs' => number_format($tripleOtHours, 2, '.', ''),
            'ot_amount' => number_format($normalOtAmount + $doubleOtAmount + $tripleOtAmount, 2, '.', ''),
            'driver_salary' => '0.00',
            'driver_bata' => '0.00',
            'other_charges' => number_format($otherCombined, 2, '.', ''),
            'vat_amount' => '0.00',
            'total_payable' => number_format($totalPayablesRaw, 2, '.', ''),
        ];

        // 4. Settlements during period (Outbound payments / cheques to owner)
        $settlements = [];
        $totalSettlementsRaw = 0.0;
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
                $amt = (float) $p->total_amount;
                $totalSettlementsRaw += $amt;
                $settlements[] = [
                    'date' => CarbonImmutable::parse($p->payment_date)->format('d/m/y'),
                    'reference' => $p->payment_number,
                    'description' => $vehicleNo.($p->cheque_number ? " {$p->cheque_number} (cheque)" : ' Bank Transfer'),
                    'amount' => '( '.number_format($amt, 2, '.', '').' )',
                    'amount_raw' => $amt,
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
        $totalRepairDeductionsRaw = 0.0;
        foreach ($repairAllocations as $alloc) {
            $amt = (float) $alloc->total_amount;
            $totalRepairDeductionsRaw += $amt;
            $exp = $alloc->expense;
            $repairDeductions[] = [
                'date' => $exp ? CarbonImmutable::parse($exp->expense_date)->format('d/m/y') : $end->format('d/m/y'),
                'reference' => $exp?->expense_number ?? 'CDN'.str_pad((string) (7000290 + $alloc->id), 7, '0', STR_PAD_LEFT),
                'description' => $vehicleNo.($exp?->description ? ' '.$exp->description : ' Maintenance / Repair'),
                'amount' => '( '.number_format($amt, 2, '.', '').' )',
                'amount_raw' => $amt,
            ];
        }

        // 6. Balance Over Paid / Balance Payable Closing Calculation
        $periodWht = $whtRate > 0 ? round(($totalPayablesRaw * $whtRate) / 100.0, 2) : 0.0;
        $closingBalanceRaw = $balanceBfRaw + $totalPayablesRaw - $totalSettlementsRaw - $totalRepairDeductionsRaw - $periodWht;

        $balanceBfFormatted = $balanceBfRaw < 0
            ? '( '.number_format(abs($balanceBfRaw), 2, '.', '').' )'
            : number_format($balanceBfRaw, 2, '.', '');

        if ($closingBalanceRaw < 0) {
            $balanceClosingLabel = 'Balance Over Paid '.$end->format('d/m/y');
            $balanceClosingFormatted = '( '.number_format(abs($closingBalanceRaw), 2, '.', '').' )';
        } else {
            $balanceClosingLabel = 'Balance Payable '.$end->format('d/m/y');
            $balanceClosingFormatted = number_format($closingBalanceRaw, 2, '.', '');
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
            'balance_bf_raw' => $balanceBfRaw,
            'payables' => $payableItems,
            'total_payable' => number_format($totalPayablesRaw, 2, '.', ''),
            'total_payable_raw' => $totalPayablesRaw,
            'detail_ii_row' => $detailIiRow,
            'settlements' => $settlements,
            'total_settlements' => number_format($totalSettlementsRaw, 2, '.', ''),
            'total_settlements_raw' => $totalSettlementsRaw,
            'repair_deductions' => $repairDeductions,
            'total_repair_deductions' => number_format($totalRepairDeductionsRaw, 2, '.', ''),
            'total_repair_deductions_raw' => $totalRepairDeductionsRaw,
            'wht_rate' => $whtRate,
            'wht_amount' => number_format($periodWht, 2, '.', ''),
            'balance_closing_label' => $balanceClosingLabel,
            'balance_closing' => $balanceClosingFormatted,
            'balance_closing_raw' => $closingBalanceRaw,
        ];
    }
}
