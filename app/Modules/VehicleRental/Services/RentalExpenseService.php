<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalExpenseAllocationType;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Models\RentalExpense;

final class RentalExpenseService
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'draft' => ['submitted', 'reversed'],
        'submitted' => ['approved', 'rejected', 'reversed'],
        'approved' => ['allocated', 'reversed'],
        'rejected' => ['draft', 'reversed'],
        'allocated' => ['reversed'],
        'reversed' => [],
    ];

    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalNumberService $numbers,
        private readonly RentalReferenceValidator $references,
        private readonly RentalStatusHistoryService $history,
    ) {}

    public function create(array $data, int $tenantId, ?int $organizationUnitId, ?int $userId): RentalExpense
    {
        return DB::transaction(function () use ($data, $tenantId, $organizationUnitId, $userId): RentalExpense {
            $this->validateExpenseReferences($data, $tenantId, $organizationUnitId);

            $net = $this->math->normalize((string) $data['net_amount']);
            $tax = $this->math->normalize((string) ($data['tax_amount'] ?? '0'));
            $gross = $this->math->add($net, $tax);
            $fingerprint = hash('sha256', implode('|', [
                $tenantId, $organizationUnitId ?? '', $data['vehicle_id'], $data['expense_type'], $data['expense_date'],
                $data['reference_number'] ?? '', $gross,
            ]));

            $expense = RentalExpense::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'expense_number' => $data['expense_number'] ?? $this->numbers->next($tenantId, $organizationUnitId, 'vehicle_rental_expense', 'REX-'),
                'agreement_id' => $data['agreement_id'] ?? null,
                'vehicle_allocation_id' => $data['vehicle_allocation_id'] ?? null,
                'usage_log_id' => $data['usage_log_id'] ?? null,
                'vehicle_id' => $data['vehicle_id'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'employee_id' => $data['employee_id'] ?? null,
                'expense_type' => $data['expense_type'],
                'expense_date' => $data['expense_date'],
                'currency_id' => $data['currency_id'],
                'net_amount' => $net,
                'tax_group_id' => $data['tax_group_id'] ?? null,
                'tax_amount' => $tax,
                'gross_amount' => $gross,
                'reference_number' => $data['reference_number'] ?? null,
                'description' => $data['description'] ?? null,
                'source_document_type' => $data['source_document_type'] ?? null,
                'source_document_id' => $data['source_document_id'] ?? null,
                'status' => RentalExpenseStatus::Draft->value,
                'fingerprint' => $fingerprint,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach (array_values($data['allocations'] ?? []) as $index => $allocation) {
                $this->createAllocation($expense, $allocation, $index + 1, $userId);
            }
            $this->assertAllocationTotal($expense);
            $this->history->record($expense, null, RentalExpenseStatus::Draft->value, $userId);

            return $expense->load($this->relations());
        });
    }

    public function transition(
        RentalExpense $expense,
        RentalExpenseStatus $to,
        int $expectedVersion,
        ?int $userId = null,
        ?string $reason = null,
    ): RentalExpense
    {
        return DB::transaction(function () use ($expense, $to, $expectedVersion, $userId, $reason): RentalExpense {
            $expense = RentalExpense::query()->with('allocations')->lockForUpdate()->findOrFail($expense->getKey());
            $this->assertExpectedVersion($expense, $expectedVersion);
            $from = $expense->status;
            if ($from === $to) {
                return $expense->load($this->relations());
            }
            if (! in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true)) {
                throw new InvalidArgumentException("Invalid expense transition from {$from->value} to {$to->value}.");
            }
            if ($to === RentalExpenseStatus::Approved) {
                $this->assertAllocationTotal($expense, true);
                $expense->allocations()->update(['status' => 'approved', 'updated_by' => $userId, 'updated_at' => now()]);
            }
            if ($to === RentalExpenseStatus::Reversed) {
                $expense->allocations()->where('status', '!=', 'reversed')->update(['status' => 'reversed', 'updated_by' => $userId, 'updated_at' => now()]);
            }

            $expense->status = $to;
            $expense->submitted_by = $to === RentalExpenseStatus::Submitted ? $userId : $expense->submitted_by;
            $expense->submitted_at = $to === RentalExpenseStatus::Submitted ? now() : $expense->submitted_at;
            $expense->approved_by = $to === RentalExpenseStatus::Approved ? $userId : $expense->approved_by;
            $expense->approved_at = $to === RentalExpenseStatus::Approved ? now() : $expense->approved_at;
            $expense->rejected_by = $to === RentalExpenseStatus::Rejected ? $userId : $expense->rejected_by;
            $expense->rejected_at = $to === RentalExpenseStatus::Rejected ? now() : $expense->rejected_at;
            $expense->reversed_by = $to === RentalExpenseStatus::Reversed ? $userId : $expense->reversed_by;
            $expense->reversed_at = $to === RentalExpenseStatus::Reversed ? now() : $expense->reversed_at;
            $expense->row_version = $expectedVersion + 1;
            $expense->updated_by = $userId;
            $expense->metadata = array_merge($expense->metadata ?? [], $reason === null ? [] : ['transition_reason' => $reason]);
            $expense->save();
            $this->history->record($expense, $from->value, $to->value, $userId, $reason);

            return $expense->refresh()->load($this->relations());
        });
    }

    public function paginate(int $tenantId, ?int $organizationUnitId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = RentalExpense::query()->forContext($tenantId, $organizationUnitId)->with($this->relations());
        foreach (['agreement_id', 'vehicle_allocation_id', 'usage_log_id', 'vehicle_id', 'expense_type', 'status'] as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $query->where($key, $filters[$key]);
            }
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('expense_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('expense_date', '<=', $filters['date_to']);
        }

        return $query->latest('expense_date')->latest('id')->paginate($perPage);
    }

    public function relations(): array
    {
        return [
            'agreement.customer', 'agreement.supplier', 'vehicleAllocation', 'usageLog', 'vehicle.make', 'vehicle.model',
            'supplier', 'employee', 'currency', 'taxGroup', 'allocations.targetAgreement', 'allocations.customer',
            'allocations.supplier', 'allocations.employee', 'attachments',
        ];
    }

    private function createAllocation(RentalExpense $expense, array $data, int $sequence, ?int $userId): void
    {
        $type = RentalExpenseAllocationType::from((string) $data['allocation_type']);
        $net = $this->math->normalize((string) $data['net_amount']);
        $tax = $this->math->normalize((string) ($data['tax_amount'] ?? '0'));
        $withholding = $this->math->normalize((string) ($data['withholding_amount'] ?? '0'));
        $markup = $this->math->normalize((string) ($data['markup_amount'] ?? '0'));
        $total = $this->math->sub($this->math->add($this->math->add($net, $tax), $markup), $withholding);
        $this->assertAllocationParty($type, $data);
        $this->validateAllocationReferences($expense, $type, $data);
        $fingerprint = hash('sha256', implode('|', [$expense->tenant_id, $expense->getKey(), $sequence, $type->value, $total]));

        $expense->allocations()->create([
            'tenant_id' => $expense->tenant_id,
            'organization_unit_id' => $expense->organization_unit_id,
            'sequence' => $data['sequence'] ?? $sequence,
            'allocation_type' => $type->value,
            'target_agreement_id' => $data['target_agreement_id'] ?? null,
            'target_vehicle_allocation_id' => $data['target_vehicle_allocation_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'employee_id' => $data['employee_id'] ?? null,
            'net_amount' => $net,
            'tax_group_id' => $data['tax_group_id'] ?? null,
            'tax_amount' => $tax,
            'withholding_amount' => $withholding,
            'markup_amount' => $markup,
            'total_amount' => $total,
            'status' => 'draft',
            'fingerprint' => $fingerprint,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }


    private function validateExpenseReferences(array $data, int $tenantId, ?int $organizationUnitId): void
    {
        $vehicle = $this->references->vehicle((int) $data['vehicle_id'], $tenantId, $organizationUnitId);
        $this->references->currency((int) $data['currency_id']);
        $this->references->taxGroup(
            isset($data['tax_group_id']) ? (int) $data['tax_group_id'] : null,
            $tenantId,
            $organizationUnitId,
        );
        if (! empty($data['supplier_id'])) {
            $this->references->supplier((int) $data['supplier_id'], $tenantId, $organizationUnitId);
        }
        if (! empty($data['employee_id'])) {
            $this->references->employee((int) $data['employee_id'], $tenantId, $organizationUnitId);
        }

        $agreement = ! empty($data['agreement_id'])
            ? $this->references->agreement((int) $data['agreement_id'], $tenantId, $organizationUnitId)
            : null;
        $allocation = ! empty($data['vehicle_allocation_id'])
            ? $this->references->allocation((int) $data['vehicle_allocation_id'], $tenantId, $organizationUnitId)
            : null;
        $usage = ! empty($data['usage_log_id'])
            ? $this->references->usageLog((int) $data['usage_log_id'], $tenantId, $organizationUnitId)
            : null;

        if ($allocation !== null && (int) $allocation->vehicle_id !== (int) $vehicle->getKey()) {
            throw new InvalidArgumentException('Expense allocation vehicle must match the expense vehicle.');
        }
        if ($agreement !== null && $allocation !== null && (int) $allocation->agreement_id !== (int) $agreement->getKey()) {
            throw new InvalidArgumentException('Expense agreement must match its vehicle allocation.');
        }
        if ($usage !== null && ((int) $usage->vehicle_id !== (int) $vehicle->getKey()
            || ($allocation !== null && (int) $usage->vehicle_allocation_id !== (int) $allocation->getKey()))) {
            throw new InvalidArgumentException('Expense usage log must match the selected vehicle and allocation.');
        }
    }

    private function validateAllocationReferences(
        RentalExpense $expense,
        RentalExpenseAllocationType $type,
        array $data,
    ): void {
        $tenantId = (int) $expense->tenant_id;
        $organizationUnitId = $expense->organization_unit_id;
        $agreement = ! empty($data['target_agreement_id'])
            ? $this->references->agreement((int) $data['target_agreement_id'], $tenantId, $organizationUnitId)
            : null;
        $allocation = ! empty($data['target_vehicle_allocation_id'])
            ? $this->references->allocation((int) $data['target_vehicle_allocation_id'], $tenantId, $organizationUnitId)
            : null;

        $this->references->taxGroup(
            isset($data['tax_group_id']) ? (int) $data['tax_group_id'] : null,
            $tenantId,
            $organizationUnitId,
        );

        if ($allocation !== null && ((int) $allocation->vehicle_id !== (int) $expense->vehicle_id
            || ($agreement !== null && (int) $allocation->agreement_id !== (int) $agreement->getKey()))) {
            throw new InvalidArgumentException('Expense target allocation must match the selected vehicle and agreement.');
        }

        if ($type === RentalExpenseAllocationType::CustomerRecovery) {
            $customer = $this->references->customer((int) $data['customer_id'], $tenantId, $organizationUnitId);
            if ($agreement?->agreement_kind !== RentalAgreementKind::CustomerRental
                || (int) $agreement->customer_id !== (int) $customer->getKey()) {
                throw new InvalidArgumentException('Customer recovery must target that customer rental agreement.');
            }
        }

        if ($type === RentalExpenseAllocationType::OwnerDeduction) {
            $supplier = $this->references->supplier((int) $data['supplier_id'], $tenantId, $organizationUnitId);
            if ($agreement?->agreement_kind !== RentalAgreementKind::OwnerSupply
                || (int) $agreement->supplier_id !== (int) $supplier->getKey()) {
                throw new InvalidArgumentException('Owner deduction must target that vehicle owner agreement.');
            }
        }

        if ($type === RentalExpenseAllocationType::EmployeeReimbursement) {
            $this->references->employee((int) $data['employee_id'], $tenantId, $organizationUnitId);
        }
    }

    private function assertAllocationParty(RentalExpenseAllocationType $type, array $data): void
    {
        $valid = match ($type) {
            RentalExpenseAllocationType::CompanyCost => true,
            RentalExpenseAllocationType::CustomerRecovery => ! empty($data['customer_id']) && ! empty($data['target_agreement_id']),
            RentalExpenseAllocationType::OwnerDeduction => ! empty($data['supplier_id']) && ! empty($data['target_agreement_id']),
            RentalExpenseAllocationType::EmployeeReimbursement => ! empty($data['employee_id']),
        };
        if (! $valid) {
            throw new InvalidArgumentException('Expense allocation is missing its required target party or agreement.');
        }
    }

    private function assertAllocationTotal(RentalExpense $expense, bool $requireFullAllocation = false): void
    {
        $allocated = $this->math->sum($expense->allocations()->pluck('net_amount')->map(fn ($value) => (string) $value)->all());
        $comparison = $this->math->compare($allocated, (string) $expense->net_amount);
        if ($comparison > 0) {
            throw new InvalidArgumentException('Expense allocation net amount cannot exceed the source expense net amount.');
        }
        if ($requireFullAllocation && $comparison !== 0) {
            throw new InvalidArgumentException('Approved expense allocations must equal the source expense net amount.');
        }
    }

    private function assertExpectedVersion(RentalExpense $expense, int $expectedVersion): void
    {
        if ((int) $expense->row_version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'expected_version' => ['The rental expense changed after it was loaded. Reload and review the latest version.'],
            ]);
        }
    }
}
