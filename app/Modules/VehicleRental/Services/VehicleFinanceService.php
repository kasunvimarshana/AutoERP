<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\InvoiceSourceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\VehicleRental\Enums\VehicleFinanceAgreementStatus;
use Modules\VehicleRental\Enums\VehicleFinanceInstallmentStatus;
use Modules\VehicleRental\Models\VehicleFinanceAgreement;
use Modules\VehicleRental\Models\VehicleFinanceInstallment;
use Modules\VehicleRental\Models\VehicleFinanceStatusHistory;

final class VehicleFinanceService
{
    private const INTEREST_METHOD_CUSTOM = 'custom';
    private const INTEREST_METHOD_REDUCING_BALANCE = 'reducing_balance';
    private const DAYS_PER_YEAR = '365';
    private const PERCENT_DIVISOR = '100';
    private const INSTALLMENT_PERIODS_PER_YEAR = [
        'weekly' => '52',
        'monthly' => '12',
        'quarterly' => '4',
        'yearly' => '1',
    ];

    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalNumberService $numbers,
        private readonly RentalReferenceValidator $references,
        private readonly InvoiceCreationService $invoices,
    ) {}

    public function create(array $data, int $tenantId, ?int $organizationUnitId, ?int $userId): VehicleFinanceAgreement
    {
        return DB::transaction(function () use ($data, $tenantId, $organizationUnitId, $userId): VehicleFinanceAgreement {
            $startsAt = CarbonImmutable::parse((string) $data['starts_at']);
            $maturesAt = CarbonImmutable::parse((string) $data['matures_at']);
            if (! $maturesAt->greaterThan($startsAt)) {
                throw new InvalidArgumentException('Vehicle finance maturity must be after its start.');
            }
            $count = (int) $data['installment_count'];
            if ($count < 1) {
                throw new InvalidArgumentException('Vehicle finance requires at least one installment.');
            }
            if ($this->math->compare((string) ($data['initial_deposit_amount'] ?? '0'), (string) $data['principal_amount']) > 0) {
                throw new InvalidArgumentException('Initial finance deposit cannot exceed the principal amount.');
            }
            $this->references->supplier((int) $data['supplier_id'], $tenantId, $organizationUnitId);
            $this->references->vehicle((int) $data['vehicle_id'], $tenantId, $organizationUnitId);
            $this->references->currency((int) $data['currency_id']);
            $this->references->taxGroup(
                isset($data['tax_group_id']) ? (int) $data['tax_group_id'] : null,
                $tenantId,
                $organizationUnitId,
            );
            $this->validateCustomSchedule($data, $startsAt, $maturesAt);

            $agreement = VehicleFinanceAgreement::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'agreement_number' => $data['agreement_number'] ?? $this->numbers->next($tenantId, $organizationUnitId, 'vehicle_finance_agreement', 'VFA-'),
                'supplier_id' => $data['supplier_id'],
                'vehicle_id' => $data['vehicle_id'],
                'agreement_date' => $data['agreement_date'],
                'starts_at' => $startsAt,
                'matures_at' => $maturesAt,
                'currency_id' => $data['currency_id'],
                'principal_amount' => $data['principal_amount'],
                'initial_deposit_amount' => $data['initial_deposit_amount'] ?? '0.000000',
                'residual_value' => $data['residual_value'] ?? '0.000000',
                'interest_method' => $data['interest_method'] ?? 'flat',
                'annual_interest_rate' => $data['annual_interest_rate'] ?? '0.000000',
                'installment_frequency' => $data['installment_frequency'] ?? 'monthly',
                'installment_count' => $count,
                'payment_term_days' => $data['payment_term_days'] ?? 0,
                'tax_group_id' => $data['tax_group_id'] ?? null,
                'status' => VehicleFinanceAgreementStatus::Draft->value,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            $this->generateSchedule($agreement, $userId, $data['schedule'] ?? null);
            $this->history($agreement, null, VehicleFinanceAgreementStatus::Draft->value, $userId);

            return $agreement->load($this->relations());
        });
    }

    public function activate(VehicleFinanceAgreement $agreement, int $expectedVersion, ?int $userId): VehicleFinanceAgreement
    {
        return DB::transaction(function () use ($agreement, $expectedVersion, $userId): VehicleFinanceAgreement {
            $agreement = VehicleFinanceAgreement::query()->with('installments')->lockForUpdate()->findOrFail($agreement->getKey());
            $this->assertAgreementExpectedVersion($agreement, $expectedVersion);
            if ($agreement->status !== VehicleFinanceAgreementStatus::Draft) {
                throw new InvalidArgumentException('Only a draft vehicle finance agreement can be activated.');
            }
            if ($agreement->installments->count() !== (int) $agreement->installment_count) {
                throw new InvalidArgumentException('Installment schedule is incomplete.');
            }
            $agreement->status = VehicleFinanceAgreementStatus::Active;
            $agreement->approved_by = $userId;
            $agreement->approved_at = now();
            $agreement->row_version = $expectedVersion + 1;
            $agreement->updated_by = $userId;
            $agreement->save();
            $this->history($agreement, VehicleFinanceAgreementStatus::Draft->value, VehicleFinanceAgreementStatus::Active->value, $userId);

            return $agreement->refresh()->load($this->relations());
        });
    }

    public function refreshDueStatuses(int $tenantId, ?int $organizationUnitId, ?string $asAt = null): int
    {
        $date = CarbonImmutable::parse($asAt ?? now())->toDateString();
        $updated = 0;
        VehicleFinanceInstallment::query()
            ->forContext($tenantId, $organizationUnitId)
            ->with('invoice.balance')
            ->whereNotIn('status', [VehicleFinanceInstallmentStatus::Waived->value, VehicleFinanceInstallmentStatus::Reversed->value])
            ->chunkById(200, function ($installments) use ($date, &$updated): void {
                foreach ($installments as $installment) {
                    if ($installment->invoice !== null && ! in_array($installment->invoice->status, [InvoiceStatus::Cancelled, InvoiceStatus::Void], true)) {
                        $balance = (string) ($installment->invoice->balance?->balance_due ?? $installment->invoice->balance_due ?? $installment->total_due);
                        $paid = $this->math->sub((string) $installment->total_due, $balance);
                        $installment->paid_amount = $this->math->isNegative($paid) ? '0.000000' : $paid;
                        $installment->balance_due = $this->math->isNegative($balance) ? '0.000000' : $balance;
                    } elseif ($installment->invoice !== null) {
                        $installment->invoice_id = null;
                        $installment->paid_amount = '0.000000';
                        $installment->balance_due = (string) $installment->total_due;
                    }
                    $new = match (true) {
                        $this->math->isZero((string) $installment->balance_due) => VehicleFinanceInstallmentStatus::Paid,
                        $installment->due_date->toDateString() < $date => VehicleFinanceInstallmentStatus::Overdue,
                        $installment->due_date->toDateString() === $date => VehicleFinanceInstallmentStatus::Due,
                        $this->math->compare((string) $installment->paid_amount, '0') > 0 => VehicleFinanceInstallmentStatus::PartiallyPaid,
                        default => VehicleFinanceInstallmentStatus::Scheduled,
                    };
                    $old = $installment->status;
                    $dirtyFinancials = $installment->isDirty(['invoice_id', 'paid_amount', 'balance_due']);
                    if ($old !== $new || $dirtyFinancials) {
                        $installment->status = $new;
                        $installment->row_version = (int) $installment->row_version + 1;
                        $installment->save();
                    }
                    if ($old !== $new) {
                        $this->history($installment->financeAgreement, $old->value, $new->value, null, (int) $installment->getKey());
                        $updated++;
                    }
                }
            });

        return $updated;
    }

    public function createInstallmentPayable(
        VehicleFinanceInstallment $installment,
        int $expectedVersion,
        string $invoiceDate,
        InvoiceStatus $status,
        ?int $userId,
    ): Invoice
    {
        return DB::transaction(function () use ($installment, $expectedVersion, $invoiceDate, $status, $userId): Invoice {
            $installment = VehicleFinanceInstallment::query()->with('financeAgreement')->lockForUpdate()->findOrFail($installment->getKey());
            $this->assertInstallmentExpectedVersion($installment, $expectedVersion);
            if ($installment->invoice_id !== null) {
                $existing = Invoice::query()->findOrFail($installment->invoice_id);
                if (! in_array($existing->status, [InvoiceStatus::Cancelled, InvoiceStatus::Void], true)) {
                    return $existing;
                }
                $installment->invoice_id = null;
            }
            if ($installment->financeAgreement->status !== VehicleFinanceAgreementStatus::Active) {
                throw new InvalidArgumentException('Vehicle finance agreement must be active.');
            }

            $lines = [];
            $sourceLines = [];
            $components = [
                'Principal' => (string) $installment->principal_due,
                'Interest' => (string) $installment->interest_due,
                'Finance fee' => (string) $installment->fee_due,
            ];
            $number = 1;
            foreach ($components as $description => $amount) {
                if ($this->math->compare($amount, '0') <= 0) {
                    continue;
                }
                $sourceLineId = ((int) $installment->getKey() * 10) + $number;
                $lines[] = new InvoiceLineData(
                    lineNumber: $number,
                    description: $description.' - installment '.$installment->installment_number,
                    quantity: '1.000000',
                    unitPrice: $amount,
                    lineType: InvoiceLineType::Charge,
                    taxAmount: $description === 'Finance fee' ? (string) $installment->tax_due : '0.000000',
                    lineTotal: $this->math->add($amount, $description === 'Finance fee' ? (string) $installment->tax_due : '0.000000'),
                    sourceLineType: 'vehicle_finance_installment_component',
                    sourceLineId: $sourceLineId,
                );
                $sourceLines[] = new InvoiceSourceLineData(
                    tenantId: (int) $installment->tenant_id,
                    sourceType: 'vehicle_finance_installment',
                    sourceId: (int) $installment->getKey(),
                    sourceLineType: 'vehicle_finance_installment_component',
                    sourceLineId: $sourceLineId,
                    sourceQuantity: '1.000000',
                    invoicedQuantity: '1.000000',
                    sourceUnitPrice: $amount,
                    sourceLineTotal: $this->math->add($amount, $description === 'Finance fee' ? (string) $installment->tax_due : '0.000000'),
                    organizationUnitId: $installment->organization_unit_id,
                );
                $number++;
            }

            $agreement = $installment->financeAgreement;
            $invoice = $this->invoices->create(new CreateInvoiceData(
                tenantId: (int) $installment->tenant_id,
                invoiceType: InvoiceType::Rental,
                direction: InvoiceDirection::Inbound,
                invoiceDate: $invoiceDate,
                organizationUnitId: $installment->organization_unit_id,
                partyType: 'supplier',
                partyId: (int) $agreement->supplier_id,
                dueDate: $installment->due_date->toDateString(),
                currencyId: (int) $agreement->currency_id,
                status: $status,
                notes: 'Vehicle finance installment '.$installment->installment_number.' for '.$agreement->agreement_number,
                createdBy: $userId,
                lines: $lines,
                sources: [new InvoiceSourceData(
                    tenantId: (int) $installment->tenant_id,
                    sourceType: 'vehicle_finance_installment',
                    sourceId: (int) $installment->getKey(),
                    organizationUnitId: $installment->organization_unit_id,
                    sourceDocumentNumber: $agreement->agreement_number.'-'.$installment->installment_number,
                    sourceDocumentDate: $installment->due_date->toDateString(),
                    sourceSubtotal: $this->math->add($this->math->add((string) $installment->principal_due, (string) $installment->interest_due), (string) $installment->fee_due),
                    sourceGrandTotal: (string) $installment->total_due,
                )],
                sourceLines: $sourceLines,
            ));
            $installment->invoice_id = $invoice->getKey();
            $installment->row_version = $expectedVersion + 1;
            $installment->save();

            return $invoice;
        });
    }

    public function paginate(int $tenantId, ?int $organizationUnitId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = VehicleFinanceAgreement::query()->forContext($tenantId, $organizationUnitId)->with($this->relations());
        foreach (['supplier_id', 'vehicle_id', 'status'] as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $query->where($key, $filters[$key]);
            }
        }
        if (! empty($filters['covers_start_at'])) {
            $query->where('starts_at', '<=', $filters['covers_start_at']);
        }
        if (! empty($filters['covers_end_at'])) {
            $query->where('matures_at', '>=', $filters['covers_end_at']);
        }

        return $query->latest('agreement_date')->latest('id')->paginate($perPage);
    }

    public function relations(): array
    {
        return ['supplier', 'vehicle.make', 'vehicle.model', 'currency', 'taxGroup', 'installments.invoice'];
    }


    private function validateCustomSchedule(array $data, CarbonImmutable $startsAt, CarbonImmutable $maturesAt): void
    {
        $schedule = $data['schedule'] ?? null;
        $interestMethod = (string) ($data['interest_method'] ?? 'flat');
        if ($interestMethod === self::INTEREST_METHOD_CUSTOM && ($schedule === null || $schedule === [])) {
            throw new InvalidArgumentException('Custom finance schedules require explicit installment rows.');
        }
        if ($interestMethod !== self::INTEREST_METHOD_CUSTOM && $schedule !== null && $schedule !== []) {
            throw new InvalidArgumentException('Explicit installment rows require the custom interest method.');
        }
        if ($schedule === null || $schedule === []) {
            return;
        }
        if (count($schedule) !== (int) $data['installment_count']) {
            throw new InvalidArgumentException('Custom installment schedule count must match installment_count.');
        }

        $numbers = [];
        $principalTotal = '0.000000';
        foreach (array_values($schedule) as $index => $row) {
            $number = (int) ($row['installment_number'] ?? ($index + 1));
            if (isset($numbers[$number])) {
                throw new InvalidArgumentException('Custom installment numbers must be unique.');
            }
            $numbers[$number] = true;
            $dueDate = CarbonImmutable::parse((string) $row['due_date']);
            if ($dueDate->lessThan($startsAt) || $dueDate->greaterThan($maturesAt)) {
                throw new InvalidArgumentException('Installment due dates must stay inside the finance agreement period.');
            }
            $principalTotal = $this->math->add($principalTotal, (string) ($row['principal_due'] ?? '0'));
        }

        $financedPrincipal = $this->math->sub(
            (string) $data['principal_amount'],
            (string) ($data['initial_deposit_amount'] ?? '0'),
        );
        if ($this->math->compare($principalTotal, $financedPrincipal) !== 0) {
            throw new InvalidArgumentException('Custom installment principal total must equal principal less initial deposit.');
        }
    }

    private function generateSchedule(VehicleFinanceAgreement $agreement, ?int $userId, ?array $customSchedule): void
    {
        if ($customSchedule !== null && $customSchedule !== []) {
            foreach (array_values($customSchedule) as $index => $row) {
                $this->createInstallment($agreement, $index + 1, $row, $userId);
            }
            return;
        }

        if ((string) $agreement->interest_method === self::INTEREST_METHOD_REDUCING_BALANCE) {
            $this->generateReducingBalanceSchedule($agreement, $userId);

            return;
        }

        $this->generateFlatSchedule($agreement, $userId);
    }

    private function generateFlatSchedule(VehicleFinanceAgreement $agreement, ?int $userId): void
    {
        $count = (int) $agreement->installment_count;
        $principalAfterDeposit = $this->math->sub((string) $agreement->principal_amount, (string) $agreement->initial_deposit_amount);
        $principalPer = $this->math->div($principalAfterDeposit, (string) $count);
        $years = $this->math->div((string) CarbonImmutable::parse($agreement->starts_at)->diffInDays(CarbonImmutable::parse($agreement->matures_at)), self::DAYS_PER_YEAR);
        $totalInterest = $this->math->div(
            $this->math->mul($this->math->mul($principalAfterDeposit, (string) $agreement->annual_interest_rate), $years),
            self::PERCENT_DIVISOR,
        );
        $interestPer = $this->math->div($totalInterest, (string) $count);
        $allocatedPrincipal = '0.000000';
        $allocatedInterest = '0.000000';
        for ($number = 1; $number <= $count; $number++) {
            $principal = $number === $count ? $this->math->sub($principalAfterDeposit, $allocatedPrincipal) : $principalPer;
            $interest = $number === $count ? $this->math->sub($totalInterest, $allocatedInterest) : $interestPer;
            $dueDate = $this->installmentDueDate(CarbonImmutable::parse($agreement->starts_at), $number, (string) $agreement->installment_frequency);
            $this->createInstallment($agreement, $number, [
                'due_date' => $dueDate->toDateString(),
                'principal_due' => $principal,
                'interest_due' => $interest,
                'fee_due' => '0.000000',
                'tax_due' => '0.000000',
            ], $userId);
            $allocatedPrincipal = $this->math->add($allocatedPrincipal, $principal);
            $allocatedInterest = $this->math->add($allocatedInterest, $interest);
        }
    }

    private function generateReducingBalanceSchedule(VehicleFinanceAgreement $agreement, ?int $userId): void
    {
        $count = (int) $agreement->installment_count;
        $principalAfterDeposit = $this->math->sub((string) $agreement->principal_amount, (string) $agreement->initial_deposit_amount);
        $principalPer = $this->math->div($principalAfterDeposit, (string) $count);
        $periodsPerYear = self::INSTALLMENT_PERIODS_PER_YEAR[(string) $agreement->installment_frequency];
        $periodRate = $this->math->div(
            $this->math->div((string) $agreement->annual_interest_rate, self::PERCENT_DIVISOR),
            $periodsPerYear,
        );
        $allocatedPrincipal = '0.000000';
        $outstandingPrincipal = $principalAfterDeposit;

        for ($number = 1; $number <= $count; $number++) {
            $principal = $number === $count
                ? $this->math->sub($principalAfterDeposit, $allocatedPrincipal)
                : $principalPer;
            $interest = $this->math->mul($outstandingPrincipal, $periodRate);
            $dueDate = $this->installmentDueDate(CarbonImmutable::parse($agreement->starts_at), $number, (string) $agreement->installment_frequency);
            $this->createInstallment($agreement, $number, [
                'due_date' => $dueDate->toDateString(),
                'principal_due' => $principal,
                'interest_due' => $interest,
                'fee_due' => '0.000000',
                'tax_due' => '0.000000',
            ], $userId);
            $allocatedPrincipal = $this->math->add($allocatedPrincipal, $principal);
            $outstandingPrincipal = $this->math->sub($outstandingPrincipal, $principal);
        }
    }

    private function createInstallment(VehicleFinanceAgreement $agreement, int $number, array $row, ?int $userId): void
    {
        $total = $this->math->sum([
            (string) ($row['principal_due'] ?? '0'),
            (string) ($row['interest_due'] ?? '0'),
            (string) ($row['fee_due'] ?? '0'),
            (string) ($row['tax_due'] ?? '0'),
        ]);
        $agreement->installments()->create([
            'tenant_id' => $agreement->tenant_id,
            'organization_unit_id' => $agreement->organization_unit_id,
            'installment_number' => $row['installment_number'] ?? $number,
            'due_date' => $row['due_date'],
            'principal_due' => $row['principal_due'] ?? '0.000000',
            'interest_due' => $row['interest_due'] ?? '0.000000',
            'fee_due' => $row['fee_due'] ?? '0.000000',
            'tax_due' => $row['tax_due'] ?? '0.000000',
            'total_due' => $total,
            'paid_amount' => '0.000000',
            'balance_due' => $total,
            'status' => VehicleFinanceInstallmentStatus::Scheduled->value,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    private function assertAgreementExpectedVersion(VehicleFinanceAgreement $agreement, int $expectedVersion): void
    {
        if ((int) $agreement->row_version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'expected_version' => ['The vehicle finance agreement changed after it was loaded. Reload and review the latest version.'],
            ]);
        }
    }

    private function assertInstallmentExpectedVersion(VehicleFinanceInstallment $installment, int $expectedVersion): void
    {
        if ((int) $installment->row_version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'expected_version' => ['The vehicle finance installment changed after it was loaded. Reload and review the latest version.'],
            ]);
        }
    }

    private function installmentDueDate(CarbonImmutable $start, int $number, string $frequency): CarbonImmutable
    {
        return match ($frequency) {
            'weekly' => $start->addWeeks($number),
            'quarterly' => $start->addMonths($number * 3),
            'yearly' => $start->addYears($number),
            default => $start->addMonths($number),
        };
    }

    private function history(
        VehicleFinanceAgreement $agreement,
        ?string $oldStatus,
        string $newStatus,
        ?int $userId,
        ?int $installmentId = null,
    ): void {
        VehicleFinanceStatusHistory::query()->create([
            'tenant_id' => $agreement->tenant_id,
            'organization_unit_id' => $agreement->organization_unit_id,
            'finance_agreement_id' => $agreement->getKey(),
            'installment_id' => $installmentId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $userId,
            'changed_at' => now(),
        ]);
    }
}
