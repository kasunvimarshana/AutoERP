<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Customer\Models\Customer;
use Modules\Hr\Models\HrEmployee;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Supplier\Models\Supplier;
use Modules\Tax\Models\TaxGroup;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Enums\RentalExpenseAllocationType;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalExpenseType;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\OwnerAgreement;
use Modules\VehicleRental\Models\RentalExpense;
use Modules\VehicleRental\Models\RentalExpenseAllocation;
use Modules\VehicleRental\Models\RunningChart;
use Modules\VehicleRental\Models\VehicleUse;

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
    ) {}

    public function create(array $data, int $tenantId, ?int $organizationUnitId, ?int $userId): RentalExpense
    {
        return DB::transaction(function () use ($data, $tenantId, $organizationUnitId, $userId): RentalExpense {
            $this->validateReferences($data, $tenantId);

            $net = $this->math->normalize((string) $data['net_amount']);
            $tax = $this->math->normalize((string) ($data['tax_amount'] ?? '0'));
            $gross = $this->math->add($net, $tax);

            $vehicle = Vehicle::query()->forTenant($tenantId)->findOrFail((int) $data['vehicle_id']);
            $currency = CurrencyModel::query()->findOrFail((int) $data['currency_id']);

            $fingerprint = hash('sha256', implode('|', [
                $tenantId,
                $organizationUnitId ?? '',
                $data['vehicle_id'],
                $data['expense_type'],
                $data['expense_date'],
                $data['reference_number'] ?? '',
                $gross,
            ]));

            if (RentalExpense::query()->forTenant($tenantId)->where('fingerprint', $fingerprint)->exists()) {
                throw new InvalidArgumentException('A matching rental expense already exists for this vehicle, date and reference.');
            }

            $expenseNumber = $data['expense_number'] ?? 'REX-'.strtoupper(bin2hex(random_bytes(4)));

            $expense = RentalExpense::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId ?? $vehicle->organization_unit_id,
                'row_version' => 1,
                'expense_number' => $expenseNumber,
                'customer_agreement_id' => $data['customer_agreement_id'] ?? null,
                'vehicle_use_id' => $data['vehicle_use_id'] ?? null,
                'running_chart_id' => $data['running_chart_id'] ?? null,
                'vehicle_id' => $vehicle->id,
                'vehicle_label_snapshot' => $vehicle->registration_number ?? $vehicle->vehicle_number,
                'supplier_id' => $data['supplier_id'] ?? null,
                'employee_id' => $data['employee_id'] ?? null,
                'expense_type' => $data['expense_type'] instanceof RentalExpenseType ? $data['expense_type'] : RentalExpenseType::from((string) $data['expense_type']),
                'expense_date' => $data['expense_date'],
                'currency_id' => $currency->id,
                'currency_code_snapshot' => $currency->code,
                'net_amount' => $net,
                'tax_group_id' => $data['tax_group_id'] ?? null,
                'tax_amount' => $tax,
                'gross_amount' => $gross,
                'reference_number' => $data['reference_number'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => RentalExpenseStatus::Draft,
                'fingerprint' => $fingerprint,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            return $expense->load($this->relations());
        });
    }

    public function transition(
        RentalExpense $expense,
        RentalExpenseStatus $to,
        int $expectedVersion,
        ?int $userId = null,
        array $allocations = [],
    ): RentalExpense {
        return DB::transaction(function () use ($expense, $to, $expectedVersion, $userId, $allocations): RentalExpense {
            $expense = RentalExpense::query()->lockForUpdate()->findOrFail($expense->getKey());
            $this->assertVersion($expense, $expectedVersion);

            $from = $expense->status;
            if ($from === $to) {
                return $expense->load($this->relations());
            }

            if (! in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true)) {
                throw new InvalidArgumentException("Invalid expense transition from {$from->value} to {$to->value}.");
            }

            if ($to === RentalExpenseStatus::Allocated) {
                $this->createAllocations($expense, $allocations, $userId);
            }

            $expense->status = $to;
            if ($to === RentalExpenseStatus::Submitted) {
                $expense->submitted_by = $userId;
                $expense->submitted_at = now();
            } elseif ($to === RentalExpenseStatus::Approved) {
                $expense->approved_by = $userId;
                $expense->approved_at = now();
            } elseif ($to === RentalExpenseStatus::Rejected) {
                $expense->rejected_by = $userId;
                $expense->rejected_at = now();
            } elseif ($to === RentalExpenseStatus::Reversed) {
                $expense->reversed_by = $userId;
                $expense->reversed_at = now();
            }
            $expense->row_version = $expectedVersion + 1;
            $expense->updated_by = $userId;
            $expense->save();

            return $expense->refresh()->load($this->relations());
        });
    }

    public function paginate(int $tenantId, ?int $organizationUnitId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = RentalExpense::query()->forContext($tenantId, $organizationUnitId)->with($this->relations());

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(fn (Builder $scope) => $scope
                ->where('expense_number', 'like', "%{$search}%")
                ->orWhere('reference_number', 'like', "%{$search}%")
                ->orWhere('vehicle_label_snapshot', 'like', "%{$search}%")
                ->orWhereHas('vehicle', fn (Builder $v) => $v->where('vehicle_number', 'like', "%{$search}%")->orWhere('registration_number', 'like', "%{$search}%")));
        }

        foreach (['expense_type', 'status', 'vehicle_id', 'customer_agreement_id'] as $key) {
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
        return ['vehicle.make', 'vehicle.model', 'customerAgreement', 'vehicleUse', 'runningChart', 'supplier', 'employee', 'currency', 'taxGroup', 'allocations'];
    }

    private function createAllocations(RentalExpense $expense, array $allocations, ?int $userId): void
    {
        if (empty($allocations)) {
            throw new InvalidArgumentException('Allocating an expense requires at least one allocation split.');
        }

        $allocatedTotal = '0.000000';
        $sequence = 1;

        foreach ($allocations as $row) {
            $net = (string) $row['net_amount'];
            $tax = (string) ($row['tax_amount'] ?? '0.000000');
            $markup = (string) ($row['markup_amount'] ?? '0.000000');
            $withholding = (string) ($row['withholding_amount'] ?? '0.000000');
            $total = $this->math->sub($this->math->add($this->math->add($net, $tax), $markup), $withholding);

            $expense->allocations()->create([
                'tenant_id' => $expense->tenant_id,
                'organization_unit_id' => $expense->organization_unit_id,
                'row_version' => 1,
                'sequence' => $sequence++,
                'allocation_type' => $row['allocation_type'] instanceof RentalExpenseAllocationType ? $row['allocation_type'] : RentalExpenseAllocationType::from((string) $row['allocation_type']),
                'target_customer_agreement_id' => $row['target_customer_agreement_id'] ?? null,
                'target_owner_agreement_id' => $row['target_owner_agreement_id'] ?? null,
                'target_vehicle_use_id' => $row['target_vehicle_use_id'] ?? null,
                'customer_id' => $row['customer_id'] ?? null,
                'supplier_id' => $row['supplier_id'] ?? null,
                'employee_id' => $row['employee_id'] ?? null,
                'net_amount' => $net,
                'tax_group_id' => $row['tax_group_id'] ?? null,
                'tax_amount' => $tax,
                'withholding_amount' => $withholding,
                'markup_amount' => $markup,
                'total_amount' => $total,
                'status' => 'confirmed',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $allocatedTotal = $this->math->add($allocatedTotal, $total);
        }

        if ($this->math->compare($allocatedTotal, (string) $expense->gross_amount) !== 0) {
            throw new InvalidArgumentException('Total allocated amount must match the expense gross amount.');
        }
    }

    private function validateReferences(array $data, int $tenantId): void
    {
        Vehicle::query()->forTenant($tenantId)->findOrFail((int) $data['vehicle_id']);
        CurrencyModel::query()->findOrFail((int) $data['currency_id']);

        if (! empty($data['customer_agreement_id'])) {
            CustomerAgreement::query()->forTenant($tenantId)->findOrFail((int) $data['customer_agreement_id']);
        }
        if (! empty($data['vehicle_use_id'])) {
            VehicleUse::query()->forTenant($tenantId)->findOrFail((int) $data['vehicle_use_id']);
        }
        if (! empty($data['running_chart_id'])) {
            RunningChart::query()->forTenant($tenantId)->findOrFail((int) $data['running_chart_id']);
        }
        if (! empty($data['supplier_id'])) {
            Supplier::query()->forTenant($tenantId)->findOrFail((int) $data['supplier_id']);
        }
        if (! empty($data['employee_id'])) {
            HrEmployee::query()->forTenant($tenantId)->findOrFail((int) $data['employee_id']);
        }
        if (! empty($data['tax_group_id'])) {
            TaxGroup::query()->forTenant($tenantId)->findOrFail((int) $data['tax_group_id']);
        }
    }

    private function assertVersion(RentalExpense $expense, int $expectedVersion): void
    {
        if ((int) $expense->row_version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'expected_version' => ['The rental expense changed after it was loaded. Reload and review the latest version.'],
            ]);
        }
    }
}
