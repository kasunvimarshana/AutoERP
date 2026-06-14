<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\DTOs\RentalExpenseData;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalExpense;

final class RentalExpenseService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function create(RentalAgreement $agreement, RentalExpenseData $data): RentalExpense
    {
        if ($this->math->compare($data->amount, '0.000000') <= 0) {
            throw new InvalidArgumentException('Rental expense amount must be greater than zero.');
        }
        if ($data->usageLogId !== null
            && ! $agreement->usageLogs()->whereKey($data->usageLogId)->exists()) {
            throw new InvalidArgumentException('Rental expense usage log does not belong to the agreement.');
        }

        return RentalExpense::query()->create([
            'tenant_id' => $agreement->tenant_id,
            'organization_unit_id' => $agreement->organization_unit_id,
            'agreement_id' => $agreement->getKey(),
            'usage_log_id' => $data->usageLogId,
            'expense_type' => $data->expenseType->value,
            'amount' => $this->math->normalize($data->amount),
            'is_billable' => $data->isBillable,
            'receipt_no' => $data->receiptNo,
            'reference_no' => $data->referenceNo,
            'description' => $data->description,
            'attachments' => $data->attachments,
            'status' => $data->status->value,
            'approved_by' => $data->status === RentalExpenseStatus::Approved ? $data->approvedBy : null,
            'approved_at' => $data->status === RentalExpenseStatus::Approved ? now() : null,
        ]);
    }

    public function changeStatus(
        RentalExpense $expense,
        RentalExpenseStatus $status,
        ?int $approvedBy = null,
    ): RentalExpense {
        $allowed = [
            'draft' => ['approved', 'rejected'],
            'approved' => ['charged'],
            'rejected' => ['draft'],
            'charged' => [],
        ];
        $old = $expense->status;
        if ($old === $status) {
            return $expense;
        }
        if (! in_array($status->value, $allowed[$old->value] ?? [], true)) {
            throw new InvalidArgumentException(
                "Invalid rental expense status transition from {$old->value} to {$status->value}.",
            );
        }
        $expense->forceFill([
            'status' => $status->value,
            'approved_by' => $status === RentalExpenseStatus::Approved ? $approvedBy : $expense->approved_by,
            'approved_at' => $status === RentalExpenseStatus::Approved ? now() : $expense->approved_at,
        ])->save();

        return $expense->refresh();
    }
}
