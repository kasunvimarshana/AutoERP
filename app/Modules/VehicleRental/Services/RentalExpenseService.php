<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\DTOs\RentalExpenseData;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalExpenseFinancialTreatment;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalExpense;
use Modules\VehicleRental\Models\RentalStatusHistory;
use Modules\VehicleRental\Models\RentalUsageLog;

final class RentalExpenseService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function create(RentalAgreement $agreement, RentalExpenseData $data): RentalExpense
    {
        if ($this->math->compare($data->amount, '0.000000') <= 0) {
            throw new InvalidArgumentException('Rental expense amount must be greater than zero.');
        }
        $this->validateTreatment($agreement, $data->financialTreatment);
        if ($data->usageLogId !== null) {
            $usageLog = RentalUsageLog::query()
                ->whereKey($data->usageLogId)
                ->whereHas('contexts', fn ($query) => $query->where('agreement_id', $agreement->getKey()))
                ->first();
            if ($usageLog === null) {
                throw new InvalidArgumentException('Rental expense usage log does not belong to the agreement context.');
            }
        }

        return DB::transaction(function () use ($agreement, $data): RentalExpense {
            $expense = RentalExpense::query()->create([
                'tenant_id' => $agreement->tenant_id,
                'organization_unit_id' => $agreement->organization_unit_id,
                'agreement_id' => $agreement->getKey(),
                'usage_log_id' => $data->usageLogId,
                'expense_type' => $data->expenseType->value,
                'expense_date' => $data->expenseDate,
                'currency_id' => $data->currencyId ?? $agreement->currency_id,
                'amount' => $this->math->normalize($data->amount),
                'tax_group_id' => $data->taxGroupId,
                'tax_amount' => '0.000000',
                'financial_treatment' => $data->financialTreatment->value,
                'is_billable' => $data->financialTreatment === RentalExpenseFinancialTreatment::CustomerBillable,
                'is_recoverable' => $data->financialTreatment === RentalExpenseFinancialTreatment::SupplierRecoverable,
                'is_reimbursable' => $data->financialTreatment === RentalExpenseFinancialTreatment::EmployeeReimbursable,
                'responsible_party_type' => $this->responsiblePartyType($agreement, $data->financialTreatment),
                'responsible_party_id' => $this->responsiblePartyId($agreement, $data->financialTreatment),
                'receipt_no' => $data->receiptNo,
                'reference_no' => $data->referenceNo,
                'description' => $data->description,
                'attachments' => $data->attachments,
                'status' => RentalExpenseStatus::Draft->value,
                'charge_generation_status' => 'not_generated',
                'created_by' => $data->createdBy,
                'updated_by' => $data->createdBy,
            ]);
            $this->recordStatus($expense, null, RentalExpenseStatus::Draft, $data->createdBy);

            return $expense;
        });
    }

    public function changeStatus(
        RentalExpense $expense,
        RentalExpenseStatus $status,
        ?int $approvedBy = null,
        ?string $reason = null,
    ): RentalExpense {
        $allowed = [
            'draft' => ['approved', 'rejected'],
            'approved' => ['charged'],
            'rejected' => ['draft'],
            'charged' => [],
        ];

        return DB::transaction(function () use ($expense, $status, $approvedBy, $reason, $allowed): RentalExpense {
            $expense = RentalExpense::query()->lockForUpdate()->findOrFail($expense->getKey());
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
                'approved_by' => $status === RentalExpenseStatus::Approved
                    ? $approvedBy
                    : $expense->approved_by,
                'approved_at' => $status === RentalExpenseStatus::Approved
                    ? now()
                    : $expense->approved_at,
                'updated_by' => $approvedBy,
            ])->save();
            $this->recordStatus($expense, $old, $status, $approvedBy, $reason);

            return $expense->refresh();
        });
    }

    private function validateTreatment(
        RentalAgreement $agreement,
        RentalExpenseFinancialTreatment $treatment,
    ): void {
        if ($treatment === RentalExpenseFinancialTreatment::CustomerBillable
            && $agreement->direction !== RentalAgreementDirection::Outbound) {
            throw new InvalidArgumentException('Customer-billable expenses require an outbound agreement context.');
        }
        if ($treatment === RentalExpenseFinancialTreatment::OwnerPayable
            && $agreement->direction !== RentalAgreementDirection::Inbound) {
            throw new InvalidArgumentException('Owner-payable expenses require an inbound agreement context.');
        }
    }

    private function responsiblePartyType(
        RentalAgreement $agreement,
        RentalExpenseFinancialTreatment $treatment,
    ): ?string {
        return match ($treatment) {
            RentalExpenseFinancialTreatment::CustomerBillable => 'customer',
            RentalExpenseFinancialTreatment::OwnerPayable,
            RentalExpenseFinancialTreatment::SupplierRecoverable => 'supplier',
            default => null,
        };
    }

    private function responsiblePartyId(
        RentalAgreement $agreement,
        RentalExpenseFinancialTreatment $treatment,
    ): ?int {
        return match ($treatment) {
            RentalExpenseFinancialTreatment::CustomerBillable,
            RentalExpenseFinancialTreatment::OwnerPayable,
            RentalExpenseFinancialTreatment::SupplierRecoverable => (int) $agreement->party_id,
            default => null,
        };
    }

    private function recordStatus(
        RentalExpense $expense,
        ?RentalExpenseStatus $old,
        RentalExpenseStatus $new,
        ?int $changedBy,
        ?string $reason = null,
    ): void {
        RentalStatusHistory::query()->create([
            'tenant_id' => $expense->tenant_id,
            'organization_unit_id' => $expense->organization_unit_id,
            'agreement_id' => $expense->agreement_id,
            'expense_id' => $expense->getKey(),
            'entity_type' => 'expense',
            'old_status' => $old?->value,
            'new_status' => $new->value,
            'reason' => $reason,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }
}
