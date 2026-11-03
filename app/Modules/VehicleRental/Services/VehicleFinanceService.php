<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Supplier\Models\Supplier;
use Modules\Tax\Models\TaxGroup;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Enums\VehicleFinanceAgreementStatus;
use Modules\VehicleRental\Enums\VehicleFinanceInstallmentStatus;
use Modules\VehicleRental\Models\VehicleFinanceAgreement;
use Modules\VehicleRental\Models\VehicleFinanceInstallment;
use Modules\VehicleRental\Models\VehicleFinanceStatusHistory;

final class VehicleFinanceService
{
    public function __construct(
        private readonly DecimalMath $math,
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

            $supplier = Supplier::query()->forTenant($tenantId)->findOrFail((int) $data['supplier_id']);
            $vehicle = Vehicle::query()->forTenant($tenantId)->findOrFail((int) $data['vehicle_id']);
            $currency = CurrencyModel::query()->findOrFail((int) $data['currency_id']);
            if (! empty($data['tax_group_id'])) {
                TaxGroup::query()->forTenant($tenantId)->findOrFail((int) $data['tax_group_id']);
            }

            $agreementNumber = $data['agreement_number'] ?? 'VFA-'.strtoupper(bin2hex(random_bytes(4)));

            $agreement = VehicleFinanceAgreement::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId ?? $supplier->organization_unit_id,
                'row_version' => 1,
                'agreement_number' => $agreementNumber,
                'supplier_id' => $supplier->id,
                'supplier_name_snapshot' => $supplier->name,
                'vehicle_id' => $vehicle->id,
                'vehicle_label_snapshot' => $vehicle->registration_number ?? $vehicle->vehicle_number,
                'agreement_date' => $data['agreement_date'],
                'starts_at' => $startsAt,
                'matures_at' => $maturesAt,
                'currency_id' => $currency->id,
                'currency_code_snapshot' => $currency->code,
                'principal_amount' => $data['principal_amount'],
                'initial_deposit_amount' => $data['initial_deposit_amount'] ?? '0.000000',
                'residual_value' => $data['residual_value'] ?? '0.000000',
                'interest_method' => $data['interest_method'] ?? 'flat',
                'annual_interest_rate' => $data['annual_interest_rate'] ?? '0.000000',
                'installment_frequency' => $data['installment_frequency'] ?? 'monthly',
                'installment_count' => $count,
                'payment_term_days' => $data['payment_term_days'] ?? 0,
                'tax_group_id' => $data['tax_group_id'] ?? null,
                'status' => VehicleFinanceAgreementStatus::Draft,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->generateSchedule($agreement, $userId, $data['schedule'] ?? null);
            $this->recordHistory($agreement, null, VehicleFinanceAgreementStatus::Draft->value, $userId);

            return $agreement->load($this->relations());
        });
    }

    public function cancel(VehicleFinanceAgreement $agreement, int $expectedVersion, ?int $userId, ?string $reason = null): VehicleFinanceAgreement
    {
        return DB::transaction(function () use ($agreement, $expectedVersion, $userId, $reason): VehicleFinanceAgreement {
            $agreement = VehicleFinanceAgreement::query()->lockForUpdate()->findOrFail($agreement->getKey());
            $this->assertAgreementVersion($agreement, $expectedVersion);

            if ($agreement->status !== VehicleFinanceAgreementStatus::Draft) {
                throw new InvalidArgumentException('Only draft finance agreements can be cancelled.');
            }

            $agreement->status = VehicleFinanceAgreementStatus::Cancelled;
            $agreement->row_version = $expectedVersion + 1;
            $agreement->updated_by = $userId;
            $agreement->save();

            $this->recordHistory($agreement, VehicleFinanceAgreementStatus::Draft->value, VehicleFinanceAgreementStatus::Cancelled->value, $userId);

            return $agreement->refresh()->load($this->relations());
        });
    }

    public function activate(VehicleFinanceAgreement $agreement, int $expectedVersion, ?int $userId): VehicleFinanceAgreement
    {
        return DB::transaction(function () use ($agreement, $expectedVersion, $userId): VehicleFinanceAgreement {
            $agreement = VehicleFinanceAgreement::query()->lockForUpdate()->findOrFail($agreement->getKey());
            $this->assertAgreementVersion($agreement, $expectedVersion);

            if ($agreement->status !== VehicleFinanceAgreementStatus::Draft) {
                throw new InvalidArgumentException('Only draft finance agreements can be activated.');
            }

            $agreement->status = VehicleFinanceAgreementStatus::Active;
            $agreement->approved_by = $userId;
            $agreement->approved_at = now();
            $agreement->row_version = $expectedVersion + 1;
            $agreement->updated_by = $userId;
            $agreement->save();

            $this->recordHistory($agreement, VehicleFinanceAgreementStatus::Draft->value, VehicleFinanceAgreementStatus::Active->value, $userId);

            return $agreement->refresh()->load($this->relations());
        });
    }

    public function createPayableInvoice(
        VehicleFinanceInstallment $installment,
        string $invoiceDate,
        int $expectedVersion,
        ?int $userId,
        InvoiceStatus $status = InvoiceStatus::Draft,
    ): Invoice {
        return DB::transaction(function () use ($installment, $invoiceDate, $expectedVersion, $userId, $status): Invoice {
            $installment = VehicleFinanceInstallment::query()->lockForUpdate()->findOrFail($installment->getKey());
            $this->assertInstallmentVersion($installment, $expectedVersion);

            if ($installment->invoice_id !== null) {
                throw new InvalidArgumentException('The vehicle finance installment already has an invoice.');
            }

            $agreement = $installment->financeAgreement;
            if ($agreement->status !== VehicleFinanceAgreementStatus::Active) {
                throw new InvalidArgumentException('Invoices can only be generated for active finance agreements.');
            }

            $components = [
                'Principal due' => (string) $installment->principal_due,
                'Interest due' => (string) $installment->interest_due,
                'Fee due' => (string) $installment->fee_due,
            ];

            $lines = [];
            $sourceLines = [];
            $number = 1;
            foreach ($components as $description => $amount) {
                if ($this->math->isZero($amount)) {
                    continue;
                }
                $sourceLineId = ((int) $installment->getKey() * 10) + $number;
                $lines[] = new InvoiceLineData(
                    lineNumber: $number,
                    description: $description.' - installment '.$installment->installment_number,
                    quantity: '1.000000',
                    unitPrice: $amount,
                    lineType: InvoiceLineType::Charge,
                    taxAmount: $description === 'Fee due' ? (string) $installment->tax_due : '0.000000',
                    lineTotal: $this->math->add($amount, $description === 'Fee due' ? (string) $installment->tax_due : '0.000000'),
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
                    sourceLineTotal: $this->math->add($amount, $description === 'Fee due' ? (string) $installment->tax_due : '0.000000'),
                    organizationUnitId: $installment->organization_unit_id,
                );
                $number++;
            }

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

    public function refreshDueStatuses(int $tenantId, ?int $organizationUnitId, ?string $asAt = null): int
    {
        $date = CarbonImmutable::parse($asAt ?? now())->toDateString();
        $updated = 0;

        VehicleFinanceInstallment::query()
            ->forContext($tenantId, $organizationUnitId)
            ->with(['invoice.balance', 'financeAgreement'])
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
                    $dirty = $installment->isDirty(['invoice_id', 'paid_amount', 'balance_due']);
                    if ($old !== $new || $dirty) {
                        $installment->status = $new;
                        $installment->row_version = (int) $installment->row_version + 1;
                        $installment->save();
                        $this->recordHistory($installment->financeAgreement, $old instanceof VehicleFinanceInstallmentStatus ? $old->value : (string) $old, $new->value, null, (int) $installment->getKey());
                        $updated++;
                    }
                }
            });

        return $updated;
    }

    public function paginate(int $tenantId, ?int $organizationUnitId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = VehicleFinanceAgreement::query()->forContext($tenantId, $organizationUnitId)->with($this->relations());

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(fn (Builder $scope) => $scope
                ->where('agreement_number', 'like', "%{$search}%")
                ->orWhere('supplier_name_snapshot', 'like', "%{$search}%")
                ->orWhere('vehicle_label_snapshot', 'like', "%{$search}%")
                ->orWhereHas('supplier', fn (Builder $s) => $s->where('name', 'like', "%{$search}%"))
                ->orWhereHas('vehicle', fn (Builder $v) => $v->where('vehicle_number', 'like', "%{$search}%")->orWhere('registration_number', 'like', "%{$search}%")));
        }

        foreach (['supplier_id', 'vehicle_id', 'status'] as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $query->where($key, $filters[$key]);
            }
        }

        return $query->latest('agreement_date')->latest('id')->paginate($perPage);
    }

    public function relations(): array
    {
        return ['supplier', 'vehicle.make', 'vehicle.model', 'currency', 'taxGroup', 'installments.invoice'];
    }

    private function generateSchedule(VehicleFinanceAgreement $agreement, ?int $userId, ?array $customSchedule = null): void
    {
        if (! empty($customSchedule)) {
            foreach ($customSchedule as $index => $row) {
                $principal = (string) ($row['principal_due'] ?? '0.000000');
                $interest = (string) ($row['interest_due'] ?? '0.000000');
                $fee = (string) ($row['fee_due'] ?? '0.000000');
                $tax = (string) ($row['tax_due'] ?? '0.000000');
                $total = $this->math->sum([$principal, $interest, $fee, $tax]);

                $agreement->installments()->create([
                    'tenant_id' => $agreement->tenant_id,
                    'organization_unit_id' => $agreement->organization_unit_id,
                    'installment_number' => $row['installment_number'] ?? ($index + 1),
                    'due_date' => $row['due_date'],
                    'principal_due' => $principal,
                    'interest_due' => $interest,
                    'fee_due' => $fee,
                    'tax_due' => $tax,
                    'total_due' => $total,
                    'paid_amount' => '0.000000',
                    'balance_due' => $total,
                    'status' => VehicleFinanceInstallmentStatus::Scheduled,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }

            return;
        }

        $count = (int) $agreement->installment_count;
        $principalAfterDeposit = $this->math->sub((string) $agreement->principal_amount, (string) $agreement->initial_deposit_amount);
        $principalPer = $this->math->div($principalAfterDeposit, (string) $count);
        $years = $this->math->div((string) CarbonImmutable::parse($agreement->starts_at)->diffInDays(CarbonImmutable::parse($agreement->matures_at)), '365');
        $totalInterest = $this->math->div(
            $this->math->mul($this->math->mul($principalAfterDeposit, (string) $agreement->annual_interest_rate), $years),
            '100',
        );
        $interestPer = $this->math->div($totalInterest, (string) $count);
        $allocatedPrincipal = '0.000000';
        $allocatedInterest = '0.000000';

        for ($number = 1; $number <= $count; $number++) {
            $principal = $number === $count ? $this->math->sub($principalAfterDeposit, $allocatedPrincipal) : $principalPer;
            $interest = $number === $count ? $this->math->sub($totalInterest, $allocatedInterest) : $interestPer;
            $dueDate = $this->installmentDueDate(CarbonImmutable::parse($agreement->starts_at), $number, (string) $agreement->installment_frequency);
            $total = $this->math->add($principal, $interest);

            $agreement->installments()->create([
                'tenant_id' => $agreement->tenant_id,
                'organization_unit_id' => $agreement->organization_unit_id,
                'installment_number' => $number,
                'due_date' => $dueDate->toDateString(),
                'principal_due' => $principal,
                'interest_due' => $interest,
                'fee_due' => '0.000000',
                'tax_due' => '0.000000',
                'total_due' => $total,
                'paid_amount' => '0.000000',
                'balance_due' => $total,
                'status' => VehicleFinanceInstallmentStatus::Scheduled,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $allocatedPrincipal = $this->math->add($allocatedPrincipal, $principal);
            $allocatedInterest = $this->math->add($allocatedInterest, $interest);
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

    private function assertAgreementVersion(VehicleFinanceAgreement $agreement, int $expectedVersion): void
    {
        if ((int) $agreement->row_version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'expected_version' => ['The vehicle finance agreement changed after it was loaded. Reload and review the latest version.'],
            ]);
        }
    }

    private function assertInstallmentVersion(VehicleFinanceInstallment $installment, int $expectedVersion): void
    {
        if ((int) $installment->row_version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'expected_version' => ['The vehicle finance installment changed after it was loaded. Reload and review the latest version.'],
            ]);
        }
    }

    private function recordHistory(
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
