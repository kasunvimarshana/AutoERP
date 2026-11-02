<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\InvoiceAdjustment;
use Modules\Invoice\Models\InvoiceSource;
use Modules\Tax\DTOs\TaxCalculationData;
use Modules\Tax\DTOs\TaxCalculationLineData;
use Modules\Tax\Services\TaxCalculationService;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalBillingPeriodStatus;
use Modules\VehicleRental\Enums\RentalCalculationLineStatus;
use Modules\VehicleRental\Enums\RentalCalculationSourceStatus;
use Modules\VehicleRental\Enums\RentalCalculationStatus;
use Modules\VehicleRental\Enums\RentalDocumentStatus;
use Modules\VehicleRental\Enums\RentalExcessKmMethod;
use Modules\VehicleRental\Enums\RentalExpenseAllocationType;
use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Enums\RentalRateComponentCode;
use Modules\VehicleRental\Enums\RentalRateUnit;
use Modules\VehicleRental\Enums\RentalUsageFactStatus;
use Modules\VehicleRental\Enums\RentalUsageStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAgreementRateComponent;
use Modules\VehicleRental\Models\RentalAgreementRateVersion;
use Modules\VehicleRental\Models\RentalBillingPeriod;
use Modules\VehicleRental\Models\RentalCalculationLine;
use Modules\VehicleRental\Models\RentalCalculationRun;
use Modules\VehicleRental\Models\RentalCalculationSource;
use Modules\VehicleRental\Models\RentalExpenseAllocation;
use Modules\VehicleRental\Models\RentalUsageContext;

final class RentalCalculationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly TaxCalculationService $taxes,
        private readonly RentalRateVersionService $rates,
        private readonly RentalStatusHistoryService $history,
        private readonly RentalCalculationSourceService $sources,
    ) {}

    public function calculate(
        RentalAgreement $agreement,
        RentalFinancialSide $side,
        string $periodStart,
        string $periodEnd,
        ?int $userId,
    ): RentalCalculationRun {
        return DB::transaction(function () use ($agreement, $side, $periodStart, $periodEnd, $userId): RentalCalculationRun {
            $agreement = RentalAgreement::query()->lockForUpdate()->findOrFail($agreement->getKey());
            $this->assertSide($agreement, $side);
            $start = CarbonImmutable::parse($periodStart);
            $end = CarbonImmutable::parse($periodEnd);
            if (! $end->greaterThan($start)) {
                throw new InvalidArgumentException('Billing period end must be after its start.');
            }
            if ($start->lessThan(CarbonImmutable::parse($agreement->starts_at))
                || $end->greaterThan(CarbonImmutable::parse($agreement->ends_at)->endOfDay())) {
                throw new InvalidArgumentException('Billing period must be inside the agreement period.');
            }

            $contexts = RentalUsageContext::query()
                ->forContext((int) $agreement->tenant_id, $agreement->organization_unit_id)
                ->where('agreement_id', $agreement->getKey())
                ->where('financial_side', $side->value)
                ->whereHas('usageLog', fn (Builder $query) => $query
                    ->where('status', RentalUsageStatus::Approved->value)
                    ->whereDate('usage_date', '>=', $start->toDateString())
                    ->whereDate('usage_date', '<=', $end->toDateString()))
                ->whereHas('usageFact', fn (Builder $query) => $query
                    ->where('status', RentalUsageFactStatus::Approved->value))
                ->with([
                    'usageLog.events',
                    'usageFact',
                    'allocation.vehicle.category',
                    'rateVersion.components',
                ])
                ->orderBy('usage_log_id')
                ->lockForUpdate()
                ->get();

            if ($contexts->isEmpty()) {
                throw new InvalidArgumentException('No approved commercial usage facts are available for this period and financial side.');
            }
            $rateVersionIds = $contexts->pluck('rate_version_id')->unique()->values();
            if ($rateVersionIds->count() !== 1) {
                throw new InvalidArgumentException('Billing period crosses multiple rate versions. Split the period at each rate effective date.');
            }
            /** @var RentalAgreementRateVersion $rate */
            $rate = $contexts->first()->rateVersion;
            $period = $this->period($agreement, $side, $rate, $start, $end, $userId);
            if ($period->runs()->where('calculation_status', RentalCalculationStatus::Approved->value)->exists()) {
                throw new InvalidArgumentException('Approved calculation already exists for this billing period. Reverse it before recalculation.');
            }
            $this->sources->assertUsageContextsAvailable(
                $contexts->pluck('id')->map(static fn ($id): int => (int) $id)->values(),
            );

            $version = ((int) $period->runs()->max('run_version')) + 1;
            $fingerprint = hash('sha256', implode('|', [
                $agreement->tenant_id,
                $period->getKey(),
                $version,
                $rate->getKey(),
                $contexts->pluck('context_fingerprint')->implode(','),
                $contexts->pluck('usageFact.row_version')->implode(','),
            ]));
            $run = RentalCalculationRun::query()->create([
                'tenant_id' => $agreement->tenant_id,
                'organization_unit_id' => $agreement->organization_unit_id,
                'billing_period_id' => $period->getKey(),
                'run_version' => $version,
                'currency_id' => $rate->currency_id,
                'calculation_status' => RentalCalculationStatus::Draft->value,
                'document_status' => RentalDocumentStatus::NotGenerated->value,
                'fingerprint' => $fingerprint,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $drafts = $this->buildLines(
                $agreement,
                $side,
                $rate,
                $period,
                $contexts,
                $start,
                $end,
            );
            if ($drafts === []) {
                throw new InvalidArgumentException('Rate version does not produce any billable or payable calculation lines.');
            }
            $drafts = $this->applyTax($agreement, $side, $rate, $start, $drafts);
            $expenseAllocationIds = collect($drafts)
                ->pluck('expense_allocation_id')
                ->filter()
                ->map(static fn ($id): int => (int) $id)
                ->unique()
                ->values();
            $this->sources->record($run, $contexts, $expenseAllocationIds, $userId);

            foreach ($drafts as $index => $line) {
                $line['line_number'] = $index + 1;
                $line['tenant_id'] = $agreement->tenant_id;
                $line['organization_unit_id'] = $agreement->organization_unit_id;
                $line['calculation_run_id'] = $run->getKey();
                $line['fingerprint'] = hash('sha256', implode('|', [
                    $agreement->tenant_id,
                    $run->getKey(),
                    $index + 1,
                    $line['source_type'],
                    $line['source_id'],
                    $line['component_code'],
                ]));
                $line['status'] = RentalCalculationLineStatus::Draft->value;
                $line['created_by'] = $userId;
                $line['updated_by'] = $userId;
                RentalCalculationLine::query()->create($line);
            }

            $this->syncTotals($run);
            $run->calculation_status = RentalCalculationStatus::Calculated;
            $run->calculated_by = $userId;
            $run->calculated_at = now();
            $run->save();
            $this->history->record(
                $run,
                RentalCalculationStatus::Draft->value,
                RentalCalculationStatus::Calculated->value,
                $userId,
            );

            return $run->refresh()->load($this->relations());
        });
    }

    public function transition(
        RentalCalculationRun $run,
        RentalCalculationStatus $to,
        ?int $userId = null,
        ?string $reason = null,
    ): RentalCalculationRun {
        return DB::transaction(function () use ($run, $to, $userId, $reason): RentalCalculationRun {
            $run = RentalCalculationRun::query()
                ->with(['billingPeriod', 'lines', 'sources'])
                ->lockForUpdate()
                ->findOrFail($run->getKey());
            $from = $run->calculation_status;
            $allowed = match ($from) {
                RentalCalculationStatus::Calculated => [RentalCalculationStatus::Submitted, RentalCalculationStatus::Reversed],
                RentalCalculationStatus::Submitted => [RentalCalculationStatus::Approved, RentalCalculationStatus::Reversed],
                RentalCalculationStatus::Approved => [RentalCalculationStatus::Reversed],
                default => [],
            };
            if ($from === $to) {
                return $run->load($this->relations());
            }
            if (! in_array($to, $allowed, true)) {
                throw new InvalidArgumentException("Invalid calculation transition from {$from->value} to {$to->value}.");
            }
            if ($to === RentalCalculationStatus::Approved && $run->lines->isEmpty()) {
                throw new InvalidArgumentException('Calculation run requires lines before approval.');
            }
            if ($to === RentalCalculationStatus::Approved) {
                $this->sources->assertAvailableForApproval($run);
            }
            if ($to === RentalCalculationStatus::Reversed && $this->hasActiveFinancialDocument($run)) {
                throw new InvalidArgumentException('Reverse the generated invoice/payable through the Invoice module before reversing this calculation.');
            }

            $run->calculation_status = $to;
            $run->submitted_by = $to === RentalCalculationStatus::Submitted ? $userId : $run->submitted_by;
            $run->submitted_at = $to === RentalCalculationStatus::Submitted ? now() : $run->submitted_at;
            $run->approved_by = $to === RentalCalculationStatus::Approved ? $userId : $run->approved_by;
            $run->approved_at = $to === RentalCalculationStatus::Approved ? now() : $run->approved_at;
            $run->reversed_by = $to === RentalCalculationStatus::Reversed ? $userId : $run->reversed_by;
            $run->reversed_at = $to === RentalCalculationStatus::Reversed ? now() : $run->reversed_at;
            $run->metadata = array_merge(
                $run->metadata ?? [],
                $reason === null ? [] : ['transition_reason' => $reason],
            );
            $run->updated_by = $userId;
            $run->save();
            $run->lines()->update([
                'status' => $to === RentalCalculationStatus::Approved
                    ? RentalCalculationLineStatus::Approved->value
                    : ($to === RentalCalculationStatus::Reversed
                        ? RentalCalculationLineStatus::Reversed->value
                        : RentalCalculationLineStatus::Draft->value),
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
            $this->sources->transition($run, $to, $userId);

            $expenseAllocationIds = $run->sources()
                ->whereNotNull('expense_allocation_id')
                ->pluck('expense_allocation_id')
                ->map(static fn ($id): int => (int) $id)
                ->unique()
                ->values();
            if ($to === RentalCalculationStatus::Approved) {
                $this->syncExpenseAllocationStatuses(
                    (int) $run->tenant_id,
                    $expenseAllocationIds,
                    $userId,
                );
                $run->billingPeriod->forceFill([
                    'status' => RentalBillingPeriodStatus::Finalized->value,
                    'is_final' => true,
                    'closed_by' => $userId,
                    'closed_at' => now(),
                    'updated_by' => $userId,
                ])->save();
            } elseif ($to === RentalCalculationStatus::Reversed) {
                $run->document_status = RentalDocumentStatus::NotGenerated;
                $run->save();
                $this->syncExpenseAllocationStatuses(
                    (int) $run->tenant_id,
                    $expenseAllocationIds,
                    $userId,
                );
                $run->billingPeriod->forceFill([
                    'status' => RentalBillingPeriodStatus::Reopened->value,
                    'is_final' => false,
                    'closed_by' => null,
                    'closed_at' => null,
                    'updated_by' => $userId,
                ])->save();
            }
            $this->history->record($run, $from->value, $to->value, $userId, $reason);

            return $run->refresh()->load($this->relations());
        });
    }

    public function paginate(
        int $tenantId,
        ?int $organizationUnitId,
        array $filters,
        int $perPage,
    ): LengthAwarePaginator {
        $query = RentalCalculationRun::query()
            ->forContext($tenantId, $organizationUnitId)
            ->with($this->relations());
        foreach (['calculation_status', 'document_status'] as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $query->where($key, $filters[$key]);
            }
        }
        if (! empty($filters['agreement_id'])) {
            $query->whereHas(
                'billingPeriod',
                fn (Builder $period) => $period->where('agreement_id', $filters['agreement_id']),
            );
        }
        if (! empty($filters['financial_side'])) {
            $query->whereHas(
                'billingPeriod',
                fn (Builder $period) => $period->where('financial_side', $filters['financial_side']),
            );
        }

        return $query->latest('id')->paginate($perPage);
    }

    public function relations(): array
    {
        return [
            'billingPeriod.agreement.customer',
            'billingPeriod.agreement.supplier',
            'billingPeriod.rateVersion',
            'currency',
            'sources.usageContext.usageLog.vehicle',
            'sources.usageContext.usageFact',
            'sources.expenseAllocation.expense',
            'lines.usageContext.usageLog.vehicle',
            'lines.usageContext.usageFact',
            'lines.expenseAllocation.expense',
            'lines.taxGroup',
        ];
    }

    private function period(
        RentalAgreement $agreement,
        RentalFinancialSide $side,
        RentalAgreementRateVersion $rate,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?int $userId,
    ): RentalBillingPeriod {
        $fingerprint = hash('sha256', implode('|', [
            $agreement->tenant_id,
            $agreement->getKey(),
            $side->value,
            $rate->getKey(),
            $start->toIso8601String(),
            $end->toIso8601String(),
        ]));
        $sequence = ((int) $agreement->billingPeriods()
            ->where('financial_side', $side->value)
            ->max('period_sequence')) + 1;

        return RentalBillingPeriod::query()->firstOrCreate(
            ['tenant_id' => $agreement->tenant_id, 'fingerprint' => $fingerprint],
            [
                'organization_unit_id' => $agreement->organization_unit_id,
                'agreement_id' => $agreement->getKey(),
                'financial_side' => $side->value,
                'rate_version_id' => $rate->getKey(),
                'period_start' => $start,
                'period_end' => $end,
                'billing_cycle_key' => $start->format('YmdHis').'-'.$end->format('YmdHis'),
                'period_sequence' => $sequence,
                'status' => RentalBillingPeriodStatus::Open->value,
                'is_final' => false,
                'created_by' => $userId,
                'updated_by' => $userId,
            ],
        );
    }

    /** @return list<array<string, mixed>> */
    private function buildLines(
        RentalAgreement $agreement,
        RentalFinancialSide $side,
        RentalAgreementRateVersion $rate,
        RentalBillingPeriod $period,
        Collection $contexts,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): array {
        $components = $this->applicableComponents($rate, $contexts);
        $lines = [];
        foreach ($components as $component) {
            if ($component->component_code === RentalRateComponentCode::WithholdingTax) {
                continue;
            }
            if ($component->component_code === RentalRateComponentCode::ExcessKm
                && $rate->excess_km_method === RentalExcessKmMethod::PerUsageLog) {
                foreach ($contexts as $context) {
                    $measured = (string) $context->usageFact->commercial_distance_km;
                    $allowed = (string) ($component->included_quantity ?: $rate->included_km);
                    $quantity = $this->positiveDifference($measured, $allowed);
                    if (! $this->math->isZero($quantity)) {
                        $lines[] = $this->lineFromComponent(
                            $component,
                            $context,
                            $quantity,
                            $measured,
                            $allowed,
                            'usage_context',
                            (int) $context->getKey(),
                        );
                    }
                }
                continue;
            }
            if ($component->component_code === RentalRateComponentCode::ExcessKm
                && $rate->excess_km_method === RentalExcessKmMethod::PerHire) {
                foreach ($contexts->groupBy('vehicle_allocation_id') as $allocationContexts) {
                    $measured = $allocationContexts->reduce(
                        fn (string $sum, RentalUsageContext $context) => $this->math->add(
                            $sum,
                            (string) $context->usageFact->commercial_distance_km,
                        ),
                        '0.000000',
                    );
                    $allowed = (string) ($component->included_quantity ?: $rate->included_km);
                    $quantity = $this->positiveDifference($measured, $allowed);
                    if (! $this->math->isZero($quantity)) {
                        $context = $allocationContexts->first();
                        $lines[] = $this->lineFromComponent(
                            $component,
                            $context,
                            $quantity,
                            $measured,
                            $allowed,
                            'rental_vehicle_allocation',
                            (int) $context->vehicle_allocation_id,
                        );
                    }
                }
                continue;
            }

            $quantity = $this->quantityFor($component, $contexts, $start, $end, $rate);
            if ($this->math->isZero($quantity)
                && $component->component_code !== RentalRateComponentCode::BaseRental) {
                continue;
            }
            $source = $contexts->first();
            $measured = $this->measuredFor($component, $contexts, $start, $end);
            $allowed = $component->component_code === RentalRateComponentCode::ExcessKm
                ? (string) ($component->included_quantity ?: $rate->included_km)
                : (string) $component->included_quantity;
            $lines[] = $this->lineFromComponent(
                $component,
                $source,
                $quantity,
                $measured,
                $allowed,
                'billing_period',
                (int) $period->getKey(),
            );
        }

        $allocationType = $side === RentalFinancialSide::Revenue
            ? RentalExpenseAllocationType::CustomerRecovery
            : RentalExpenseAllocationType::OwnerDeduction;
        $expenseAllocations = RentalExpenseAllocation::query()
            ->forContext((int) $agreement->tenant_id, $agreement->organization_unit_id)
            ->where('allocation_type', $allocationType->value)
            ->where('target_agreement_id', $agreement->getKey())
            ->where('status', 'approved')
            ->whereHas('expense', fn (Builder $query) => $query
                ->whereDate('expense_date', '>=', $start->toDateString())
                ->whereDate('expense_date', '<=', $end->toDateString()))
            ->with('expense')
            ->get();
        foreach ($expenseAllocations as $allocation) {
            $baseAmount = $this->math->add(
                (string) $allocation->net_amount,
                (string) $allocation->markup_amount,
            );
            $negative = $side === RentalFinancialSide::Cost;
            $net = $negative ? $this->math->sub('0', $baseAmount) : $baseAmount;
            $taxAmount = $negative
                ? $this->math->sub('0', (string) $allocation->tax_amount)
                : '0.000000';
            $withholdingAmount = $negative
                ? (string) $allocation->withholding_amount
                : '0.000000';
            $totalAmount = $this->math->sub(
                $this->math->add($net, $taxAmount),
                $withholdingAmount,
            );
            $lines[] = [
                'usage_context_id' => null,
                'expense_allocation_id' => $allocation->getKey(),
                'custody_event_item_id' => null,
                'source_type' => 'rental_expense_allocation',
                'source_id' => $allocation->getKey(),
                'component_code' => $allocation->expense->expense_type === 'repair'
                    ? RentalRateComponentCode::Repair->value
                    : RentalRateComponentCode::OtherRecovery->value,
                'description' => ($negative ? 'Owner deduction: ' : 'Customer recovery: ')
                    .($allocation->expense->description ?: $allocation->expense->expense_number),
                'measured_quantity' => '1.000000',
                'allowed_quantity' => '0.000000',
                'chargeable_quantity' => '1.000000',
                'unit' => RentalRateUnit::Fixed->value,
                'rate' => $net,
                'multiplier' => '1.000000',
                'net_amount' => $net,
                'discount_amount' => '0.000000',
                'tax_group_id' => $allocation->tax_group_id,
                'tax_amount' => $taxAmount,
                'withholding_amount' => $withholdingAmount,
                'total_amount' => $totalAmount,
                'applied_rule' => $allocationType->value,
                'rule_snapshot' => [
                    'expense_id' => $allocation->expense_id,
                    'allocation_type' => $allocationType->value,
                ],
            ];
        }

        return $lines;
    }

    /** @return Collection<int, RentalAgreementRateComponent> */
    private function applicableComponents(
        RentalAgreementRateVersion $rate,
        Collection $contexts,
    ): Collection {
        $categoryIds = $contexts
            ->map(fn (RentalUsageContext $context) => $context->allocation?->vehicle?->vehicle_category_id
                ?? $context->allocation?->vehicle?->category_id)
            ->filter()
            ->unique()
            ->values();
        $active = $rate->components->where('status', 'active');
        if ($categoryIds->count() > 1
            && $active->contains(fn (RentalAgreementRateComponent $component) => $component->vehicle_category_id !== null)) {
            throw new InvalidArgumentException('Billing period contains multiple vehicle categories with category-specific rates. Split the period by allocation/category.');
        }

        $categoryId = $categoryIds->first();

        return $active
            ->groupBy(fn (RentalAgreementRateComponent $component) => $component->component_code->value)
            ->map(function (Collection $group) use ($categoryId): ?RentalAgreementRateComponent {
                if ($categoryId !== null) {
                    $specific = $group->first(
                        fn (RentalAgreementRateComponent $component) => (int) $component->vehicle_category_id === (int) $categoryId,
                    );
                    if ($specific !== null) {
                        return $specific;
                    }
                }

                return $group->first(
                    fn (RentalAgreementRateComponent $component) => $component->vehicle_category_id === null,
                );
            })
            ->filter()
            ->sortBy('calculation_order')
            ->values();
    }

    private function lineFromComponent(
        RentalAgreementRateComponent $component,
        RentalUsageContext $context,
        string $quantity,
        string $measured,
        string $allowed,
        string $sourceType,
        int $sourceId,
    ): array {
        $amount = $this->math->mul(
            $this->math->mul($quantity, (string) $component->rate),
            (string) $component->multiplier,
        );
        if ($component->minimum_amount !== null
            && $this->math->compare($amount, (string) $component->minimum_amount) < 0) {
            $amount = (string) $component->minimum_amount;
        }
        if ($component->maximum_amount !== null
            && $this->math->compare($amount, (string) $component->maximum_amount) > 0) {
            $amount = (string) $component->maximum_amount;
        }

        $ruleSnapshot = [
            'rate_component_id' => $component->getKey(),
            'rate_version_id' => $component->rate_version_id,
            'source_scope' => $sourceType,
            'unit' => $component->unit->value,
            'rate' => (string) $component->rate,
            'multiplier' => (string) $component->multiplier,
        ];
        if ($sourceType === 'usage_context') {
            $ruleSnapshot['usage_fact_id'] = $context->usageFact->getKey();
            $ruleSnapshot['usage_fact_version'] = (int) $context->usageFact->row_version;
        }

        return [
            'usage_context_id' => $sourceType === 'usage_context' ? $context->getKey() : null,
            'expense_allocation_id' => null,
            'custody_event_item_id' => null,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'component_code' => $component->component_code->value,
            'description' => ucwords(str_replace('_', ' ', $component->component_code->value)),
            'measured_quantity' => $measured,
            'allowed_quantity' => $allowed,
            'chargeable_quantity' => $quantity,
            'unit' => $component->unit->value,
            'rate' => (string) $component->rate,
            'multiplier' => (string) $component->multiplier,
            'net_amount' => $amount,
            'discount_amount' => '0.000000',
            'tax_group_id' => $component->tax_group_override_id,
            'tax_amount' => '0.000000',
            'withholding_amount' => '0.000000',
            'total_amount' => $amount,
            'applied_rule' => $component->component_code->value,
            'rule_snapshot' => $ruleSnapshot,
        ];
    }

    private function quantityFor(
        RentalAgreementRateComponent $component,
        Collection $contexts,
        CarbonImmutable $start,
        CarbonImmutable $end,
        RentalAgreementRateVersion $rate,
    ): string {
        return match ($component->component_code) {
            RentalRateComponentCode::BaseRental => $this->baseQuantity($component->unit, $contexts, $start, $end),
            RentalRateComponentCode::ExcessKm => $this->positiveDifference(
                $this->sumFactField($contexts, 'commercial_distance_km'),
                (string) ($component->included_quantity ?: $rate->included_km),
            ),
            RentalRateComponentCode::DriverSalary => $this->quantityByUnit($component->unit, $contexts, 'working_minutes'),
            RentalRateComponentCode::NormalOvertime => $this->quantityByUnit($component->unit, $contexts, 'normal_overtime_minutes'),
            RentalRateComponentCode::DoubleOvertime => $this->quantityByUnit($component->unit, $contexts, 'double_overtime_minutes'),
            RentalRateComponentCode::TripleOvertime => $this->quantityByUnit($component->unit, $contexts, 'triple_overtime_minutes'),
            RentalRateComponentCode::NightOut => $this->sumFactField($contexts, 'night_out_count'),
            RentalRateComponentCode::Parking => $this->eventQuantity($contexts, 'parking'),
            RentalRateComponentCode::Toll => $this->eventQuantity($contexts, 'toll'),
            RentalRateComponentCode::Waiting => $this->eventQuantity($contexts, 'waiting'),
            RentalRateComponentCode::Outstation => $this->eventQuantity($contexts, 'outstation'),
            RentalRateComponentCode::Fuel => $this->eventQuantity($contexts, 'fuel'),
            RentalRateComponentCode::Damage => $this->eventQuantity($contexts, 'damage'),
            RentalRateComponentCode::Repair => $this->eventQuantity($contexts, 'repair'),
            RentalRateComponentCode::OtherRecovery => $this->eventQuantity($contexts, 'other'),
            RentalRateComponentCode::WithholdingTax => '0.000000',
        };
    }

    private function measuredFor(
        RentalAgreementRateComponent $component,
        Collection $contexts,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): string {
        if ($component->component_code === RentalRateComponentCode::ExcessKm) {
            return $this->sumFactField($contexts, 'commercial_distance_km');
        }

        return $this->quantityFor(
            $component,
            $contexts,
            $start,
            $end,
            $contexts->first()->rateVersion,
        );
    }

    private function baseQuantity(
        RentalRateUnit $unit,
        Collection $contexts,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): string {
        return match ($unit) {
            RentalRateUnit::Fixed, RentalRateUnit::Month => '1.000000',
            RentalRateUnit::Day => $this->math->normalize((string) ($start->startOfDay()->diffInDays($end->startOfDay()) + 1)),
            RentalRateUnit::Week => $this->math->div(
                $this->math->normalize((string) ($start->startOfDay()->diffInDays($end->startOfDay()) + 1)),
                '7',
            ),
            RentalRateUnit::Hour => $this->math->div($this->sumFactField($contexts, 'working_minutes'), '60'),
            RentalRateUnit::Minute => $this->sumFactField($contexts, 'working_minutes'),
            RentalRateUnit::Trip, RentalRateUnit::Count => $this->math->normalize((string) $contexts->count()),
            RentalRateUnit::Kilometre => $this->sumFactField($contexts, 'commercial_distance_km'),
            RentalRateUnit::Litre => '0.000000',
        };
    }

    private function quantityByUnit(
        RentalRateUnit $unit,
        Collection $contexts,
        string $minutesField,
    ): string {
        $minutes = $this->sumFactField($contexts, $minutesField);

        return $unit === RentalRateUnit::Hour ? $this->math->div($minutes, '60') : $minutes;
    }

    private function sumFactField(Collection $contexts, string $field): string
    {
        return $contexts->reduce(
            fn (string $sum, RentalUsageContext $context) => $this->math->add(
                $sum,
                (string) $context->usageFact->{$field},
            ),
            '0.000000',
        );
    }

    private function eventQuantity(Collection $contexts, string $eventType): string
    {
        $total = '0.000000';
        foreach ($contexts as $context) {
            foreach ($context->usageLog->events->filter(
                fn ($event): bool => $event->event_type->value === $eventType
                    && $event->applicability->appliesTo($context->financial_side),
            ) as $event) {
                $total = $this->math->add($total, (string) $event->quantity);
            }
        }

        return $total;
    }

    private function positiveDifference(string $measured, string $allowed): string
    {
        $difference = $this->math->sub($measured, $allowed);

        return $this->math->isNegative($difference) ? '0.000000' : $difference;
    }

    /** @param list<array<string, mixed>> $lines @return list<array<string, mixed>> */
    private function applyTax(
        RentalAgreement $agreement,
        RentalFinancialSide $side,
        RentalAgreementRateVersion $rate,
        CarbonImmutable $documentDate,
        array $lines,
    ): array {
        $positiveIndexes = [];
        $taxLines = [];
        foreach ($lines as $index => $line) {
            if ($this->math->compare((string) $line['net_amount'], '0') <= 0) {
                continue;
            }
            $positiveIndexes[] = $index;
            $taxLines[] = new TaxCalculationLineData(
                lineNumber: count($taxLines) + 1,
                quantity: '1.000000',
                unitPrice: (string) $line['net_amount'],
                taxGroupId: $line['tax_group_id'] ?? $rate->tax_group_id,
                taxableAmount: (string) $line['net_amount'],
            );
        }
        if ($taxLines === []) {
            return $lines;
        }

        $base = [
            'tenantId' => (int) $agreement->tenant_id,
            'documentType' => 'rental',
            'documentDate' => $documentDate->toDateString(),
            'organizationUnitId' => $agreement->organization_unit_id,
            'customerId' => $side === RentalFinancialSide::Revenue ? $agreement->customer_id : null,
            'supplierId' => $side === RentalFinancialSide::Cost ? $agreement->supplier_id : null,
        ];
        $result = $this->taxes->calculate(new TaxCalculationData(
            ...$base,
            documentTaxGroupId: $rate->tax_group_id,
            lines: $taxLines,
        ));
        foreach ($result->lineResults as $position => $taxResult) {
            $index = $positiveIndexes[$position];
            $lines[$index]['tax_amount'] = $taxResult->taxAmount;
            $lines[$index]['withholding_amount'] = $taxResult->withholdingAmount;
            $lines[$index]['total_amount'] = $this->math->sub(
                $this->math->add((string) $lines[$index]['net_amount'], $taxResult->taxAmount),
                $taxResult->withholdingAmount,
            );
            $lines[$index]['tax_group_id'] ??= $rate->tax_group_id;
        }

        if ($side === RentalFinancialSide::Cost && $rate->withholding_tax_group_id !== null) {
            $withholdingResult = $this->taxes->calculate(new TaxCalculationData(
                ...$base,
                documentTaxGroupId: $rate->withholding_tax_group_id,
                lines: array_map(fn (TaxCalculationLineData $line) => new TaxCalculationLineData(
                    lineNumber: $line->lineNumber,
                    quantity: $line->quantity,
                    unitPrice: $line->unitPrice,
                    taxGroupId: $rate->withholding_tax_group_id,
                    taxableAmount: $line->taxableAmount,
                ), $taxLines),
            ));
            foreach ($withholdingResult->lineResults as $position => $taxResult) {
                $index = $positiveIndexes[$position];
                $lines[$index]['withholding_amount'] = $this->math->add(
                    (string) $lines[$index]['withholding_amount'],
                    $taxResult->withholdingAmount,
                );
                $lines[$index]['total_amount'] = $this->math->sub(
                    $this->math->add(
                        (string) $lines[$index]['net_amount'],
                        (string) $lines[$index]['tax_amount'],
                    ),
                    (string) $lines[$index]['withholding_amount'],
                );
            }
        }

        return $lines;
    }

    private function syncTotals(RentalCalculationRun $run): void
    {
        $run->net_total = $this->math->sum(
            $run->lines()->pluck('net_amount')->map(fn ($value) => (string) $value)->all(),
        );
        $run->discount_total = $this->math->sum(
            $run->lines()->pluck('discount_amount')->map(fn ($value) => (string) $value)->all(),
        );
        $run->tax_total = $this->math->sum(
            $run->lines()->pluck('tax_amount')->map(fn ($value) => (string) $value)->all(),
        );
        $run->withholding_total = $this->math->sum(
            $run->lines()->pluck('withholding_amount')->map(fn ($value) => (string) $value)->all(),
        );
        $run->grand_total = $this->math->sum(
            $run->lines()->pluck('total_amount')->map(fn ($value) => (string) $value)->all(),
        );
        $run->save();
    }

    private function syncExpenseAllocationStatuses(
        int $tenantId,
        Collection $allocationIds,
        ?int $userId,
    ): void {
        foreach ($allocationIds as $allocationId) {
            $consumed = RentalCalculationSource::query()
                ->where('expense_allocation_id', $allocationId)
                ->where('status', RentalCalculationSourceStatus::Approved->value)
                ->whereHas('run', fn (Builder $query) => $query
                    ->where('calculation_status', RentalCalculationStatus::Approved->value))
                ->exists();

            DB::table('rental_expense_allocations')
                ->where('tenant_id', $tenantId)
                ->where('id', $allocationId)
                ->whereIn('status', ['approved', 'consumed'])
                ->update([
                    'status' => $consumed ? 'consumed' : 'approved',
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);
        }

        $expenseIds = DB::table('rental_expense_allocations')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $allocationIds)
            ->pluck('expense_id')
            ->unique();
        foreach ($expenseIds as $expenseId) {
            $hasApproved = DB::table('rental_expense_allocations')
                ->where('tenant_id', $tenantId)
                ->where('expense_id', $expenseId)
                ->where('status', 'approved')
                ->exists();
            $hasConsumed = DB::table('rental_expense_allocations')
                ->where('tenant_id', $tenantId)
                ->where('expense_id', $expenseId)
                ->where('status', 'consumed')
                ->exists();
            DB::table('rental_expenses')
                ->where('tenant_id', $tenantId)
                ->where('id', $expenseId)
                ->whereIn('status', ['approved', 'allocated'])
                ->update([
                    'status' => $hasConsumed && ! $hasApproved ? 'allocated' : 'approved',
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);
        }
    }

    private function hasActiveFinancialDocument(RentalCalculationRun $run): bool
    {
        $inactiveStatuses = [InvoiceStatus::Cancelled->value, InvoiceStatus::Void->value];
        $hasSource = InvoiceSource::query()
            ->where('tenant_id', $run->tenant_id)
            ->where('source_type', 'rental_calculation_run')
            ->where('source_id', $run->getKey())
            ->whereHas('invoice', fn (Builder $query) => $query->whereNotIn('status', $inactiveStatuses))
            ->exists();
        if ($hasSource) {
            return true;
        }

        return InvoiceAdjustment::query()
            ->where('tenant_id', $run->tenant_id)
            ->where('source_type', 'rental_calculation_line')
            ->whereIn('source_id', $run->lines()->select('id'))
            ->whereHas('invoice', fn (Builder $query) => $query->whereNotIn('status', $inactiveStatuses))
            ->exists();
    }

    private function assertSide(RentalAgreement $agreement, RentalFinancialSide $side): void
    {
        if ($side === RentalFinancialSide::Revenue
            && $agreement->agreement_kind !== RentalAgreementKind::CustomerRental) {
            throw new InvalidArgumentException('Revenue calculations require a customer rental agreement.');
        }
        if ($side === RentalFinancialSide::Cost
            && $agreement->agreement_kind !== RentalAgreementKind::OwnerSupply) {
            throw new InvalidArgumentException('Cost calculations require an owner supply agreement.');
        }
    }
}
