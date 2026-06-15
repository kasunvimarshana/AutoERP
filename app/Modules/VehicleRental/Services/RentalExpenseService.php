<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Configuration\Models\CurrencyModel;
use Modules\Core\Services\DecimalMath;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Models\HrEmployee;
use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Models\Supplier;
use Modules\Tax\Models\TaxGroup;
use Modules\VehicleRental\DTOs\RentalExpenseData;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
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

        return DB::transaction(function () use ($agreement, $data): RentalExpense {
            $agreement = RentalAgreement::query()
                ->forContext((int) $agreement->tenant_id, $agreement->organization_unit_id)
                ->lockForUpdate()
                ->findOrFail($agreement->getKey());
            if (! in_array($agreement->status, [
                RentalAgreementStatus::Active,
                RentalAgreementStatus::Returned,
            ], true)) {
                throw new InvalidArgumentException(
                    'Rental expenses require an active or returned agreement.',
                );
            }
            $this->validateTreatment($agreement, $data);
            $this->validateConfiguration($agreement, $data);
            if ($data->usageLogId !== null) {
                $this->usageLogForAgreement($agreement, $data->usageLogId);
            }
            $fingerprint = hash('sha256', implode('|', [
                (string) $agreement->tenant_id,
                (string) $agreement->getKey(),
                (string) ($data->usageLogId ?? 'agreement'),
                $data->expenseType->value,
                $data->expenseDate,
                $this->math->normalize($data->amount),
                $data->financialTreatment->value,
                trim((string) $data->receiptNo),
                trim((string) $data->referenceNo),
            ]));
            $expense = RentalExpense::query()->firstOrCreate([
                'tenant_id' => $agreement->tenant_id,
                'fingerprint' => $fingerprint,
            ], [
                'tenant_id' => $agreement->tenant_id,
                'organization_unit_id' => $agreement->organization_unit_id,
                'agreement_id' => $agreement->getKey(),
                'usage_log_id' => $data->usageLogId,
                'expense_type' => $data->expenseType->value,
                'expense_date' => $data->expenseDate,
                'currency_id' => $data->currencyId ?? $agreement->currency_id,
                'amount' => $this->math->normalize($data->amount),
                ...$this->taxAttributes($data),
                'financial_treatment' => $data->financialTreatment->value,
                'is_billable' => $data->financialTreatment === RentalExpenseFinancialTreatment::CustomerBillable,
                'is_recoverable' => $data->financialTreatment === RentalExpenseFinancialTreatment::SupplierRecoverable,
                'is_reimbursable' => $data->financialTreatment === RentalExpenseFinancialTreatment::EmployeeReimbursable,
                'responsible_party_type' => $this->responsiblePartyType($agreement, $data->financialTreatment),
                'responsible_party_id' => $this->responsiblePartyId($agreement, $data),
                'receipt_no' => $data->receiptNo,
                'reference_no' => $data->referenceNo,
                'description' => $data->description,
                'attachments' => $data->attachments,
                'status' => RentalExpenseStatus::Draft->value,
                'charge_generation_status' => 'not_generated',
                'fingerprint' => $fingerprint,
                'created_by' => $data->createdBy,
                'updated_by' => $data->createdBy,
            ]);
            if ($expense->wasRecentlyCreated) {
                $this->recordStatus($expense, null, RentalExpenseStatus::Draft, $data->createdBy);
            }

            return $expense;
        });
    }

    public function update(RentalExpense $expense, RentalExpenseData $data): RentalExpense
    {
        if ($this->math->compare($data->amount, '0.000000') <= 0) {
            throw new InvalidArgumentException('Rental expense amount must be greater than zero.');
        }

        return DB::transaction(function () use ($expense, $data): RentalExpense {
            $expense = RentalExpense::query()->lockForUpdate()->findOrFail($expense->getKey());
            if (! in_array($expense->status, [RentalExpenseStatus::Draft, RentalExpenseStatus::Rejected], true)) {
                throw new InvalidArgumentException('Only draft or rejected rental expenses can be edited.');
            }
            $agreement = RentalAgreement::query()
                ->forContext((int) $expense->tenant_id, $expense->organization_unit_id)
                ->lockForUpdate()
                ->findOrFail($expense->agreement_id);
            $this->validateTreatment($agreement, $data);
            $this->validateConfiguration($agreement, $data);
            if ($data->usageLogId !== null) {
                $this->usageLogForAgreement($agreement, $data->usageLogId);
            }

            $old = $expense->status;
            $expense->forceFill([
                'usage_log_id' => $data->usageLogId,
                'expense_type' => $data->expenseType->value,
                'expense_date' => $data->expenseDate,
                'currency_id' => $data->currencyId ?? $agreement->currency_id,
                'amount' => $this->math->normalize($data->amount),
                ...$this->taxAttributes($data),
                'financial_treatment' => $data->financialTreatment->value,
                'is_billable' => $data->financialTreatment === RentalExpenseFinancialTreatment::CustomerBillable,
                'is_recoverable' => $data->financialTreatment === RentalExpenseFinancialTreatment::SupplierRecoverable,
                'is_reimbursable' => $data->financialTreatment === RentalExpenseFinancialTreatment::EmployeeReimbursable,
                'responsible_party_type' => $this->responsiblePartyType($agreement, $data->financialTreatment),
                'responsible_party_id' => $this->responsiblePartyId($agreement, $data),
                'receipt_no' => $data->receiptNo,
                'reference_no' => $data->referenceNo,
                'description' => $data->description,
                'attachments' => $data->attachments,
                'status' => RentalExpenseStatus::Draft->value,
                'charge_generation_status' => 'not_generated',
                'submitted_by' => null,
                'submitted_at' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'updated_by' => $data->createdBy,
            ])->save();
            if ($old !== RentalExpenseStatus::Draft) {
                $this->recordStatus($expense, $old, RentalExpenseStatus::Draft, $data->createdBy, 'Expense corrected.');
            }

            return $expense->refresh();
        });
    }

    public function delete(RentalExpense $expense): void
    {
        DB::transaction(function () use ($expense): void {
            $expense = RentalExpense::query()->lockForUpdate()->findOrFail($expense->getKey());
            if (! in_array($expense->status, [RentalExpenseStatus::Draft, RentalExpenseStatus::Rejected], true)) {
                throw new InvalidArgumentException('Only draft or rejected rental expenses can be deleted.');
            }
            $expense->delete();
        });
    }

    public function changeStatus(
        RentalExpense $expense,
        RentalExpenseStatus $status,
        ?int $approvedBy = null,
        ?string $reason = null,
    ): RentalExpense {
        $allowed = [
            'draft' => ['submitted'],
            'submitted' => ['approved', 'rejected'],
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
                'submitted_by' => $status === RentalExpenseStatus::Submitted
                    ? $approvedBy
                    : ($status === RentalExpenseStatus::Draft ? null : $expense->submitted_by),
                'submitted_at' => $status === RentalExpenseStatus::Submitted
                    ? now()
                    : ($status === RentalExpenseStatus::Draft ? null : $expense->submitted_at),
                'approved_by' => $status === RentalExpenseStatus::Approved
                    ? $approvedBy
                    : ($status === RentalExpenseStatus::Draft ? null : $expense->approved_by),
                'approved_at' => $status === RentalExpenseStatus::Approved
                    ? now()
                    : ($status === RentalExpenseStatus::Draft ? null : $expense->approved_at),
                'rejected_by' => $status === RentalExpenseStatus::Rejected
                    ? $approvedBy
                    : ($status === RentalExpenseStatus::Draft ? null : $expense->rejected_by),
                'rejected_at' => $status === RentalExpenseStatus::Rejected
                    ? now()
                    : ($status === RentalExpenseStatus::Draft ? null : $expense->rejected_at),
                'updated_by' => $approvedBy,
            ])->save();
            $this->recordStatus($expense, $old, $status, $approvedBy, $reason);

            return $expense->refresh();
        });
    }

    private function validateTreatment(
        RentalAgreement $agreement,
        RentalExpenseData $data,
    ): void {
        $treatment = $data->financialTreatment;
        if ($treatment === RentalExpenseFinancialTreatment::CustomerBillable
            && $agreement->direction !== RentalAgreementDirection::Outbound) {
            throw new InvalidArgumentException('Customer-billable expenses require an outbound agreement context.');
        }
        if ($treatment === RentalExpenseFinancialTreatment::OwnerPayable
            && $agreement->direction !== RentalAgreementDirection::Inbound) {
            throw new InvalidArgumentException('Owner-payable expenses require an inbound agreement context.');
        }
        if ($treatment === RentalExpenseFinancialTreatment::EmployeeReimbursable) {
            if ($data->responsiblePartyId === null) {
                throw new InvalidArgumentException(
                    'Employee-reimbursable expenses require a responsible employee.',
                );
            }
            $employee = HrEmployee::query()
                ->where('tenant_id', $agreement->tenant_id)
                ->where(function ($query) use ($agreement): void {
                    $query->whereNull('organization_unit_id');
                    if ($agreement->organization_unit_id !== null) {
                        $query->orWhere('organization_unit_id', $agreement->organization_unit_id);
                    }
                })
                ->findOrFail($data->responsiblePartyId);
            if ($employee->status !== EmployeeStatus::Active) {
                throw new InvalidArgumentException(
                    'Employee-reimbursable expenses require an active employee.',
                );
            }
        } elseif ($treatment === RentalExpenseFinancialTreatment::SupplierRecoverable) {
            if ($data->responsiblePartyId === null) {
                throw new InvalidArgumentException(
                    'Supplier-recoverable expenses require a responsible supplier.',
                );
            }
            $supplier = Supplier::query()
                ->where('tenant_id', $agreement->tenant_id)
                ->where(function ($query) use ($agreement): void {
                    $query->whereNull('organization_unit_id');
                    if ($agreement->organization_unit_id !== null) {
                        $query->orWhere('organization_unit_id', $agreement->organization_unit_id);
                    }
                })
                ->findOrFail($data->responsiblePartyId);
            if ($supplier->status !== SupplierStatus::Active) {
                throw new InvalidArgumentException(
                    'Supplier-recoverable expenses require an active supplier.',
                );
            }
        } elseif ($data->responsiblePartyId !== null) {
            throw new InvalidArgumentException(
                'A responsible party can only be supplied for employee reimbursement or supplier recovery.',
            );
        }
    }

    private function validateConfiguration(RentalAgreement $agreement, RentalExpenseData $data): void
    {
        if ($data->currencyId !== null) {
            CurrencyModel::query()
                ->where('is_active', true)
                ->findOrFail($data->currencyId);
        }
        foreach (array_filter([
            $data->taxGroupId,
            $data->originalTaxGroupId,
            $data->recoveryTaxGroupId,
        ]) as $taxGroupId) {
            TaxGroup::query()
                ->where('tenant_id', $agreement->tenant_id)
                ->where(function ($query) use ($agreement): void {
                    $query->whereNull('organization_unit_id');
                    if ($agreement->organization_unit_id !== null) {
                        $query->orWhere('organization_unit_id', $agreement->organization_unit_id);
                    }
                })
                ->where('active', true)
                ->findOrFail($taxGroupId);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function taxAttributes(RentalExpenseData $data): array
    {
        $originalNet = $this->math->normalize($data->originalNetAmount ?? $data->amount);
        $originalTax = $this->math->normalize($data->originalTaxAmount);
        $originalWithholding = $this->math->normalize($data->originalWithholdingAmount);
        $originalGross = $this->math->normalize($data->originalGrossAmount ?? $data->amount);
        $markup = $this->math->normalize($data->markupAmount);
        $recoveryBase = $this->math->normalize($data->recoveryBaseAmount ?? $this->math->add($data->amount, $markup));

        return [
            'tax_group_id' => $data->originalTaxGroupId ?? $data->taxGroupId,
            'tax_amount' => $originalTax,
            'withholding_amount' => $originalWithholding,
            'original_net_amount' => $originalNet,
            'original_tax_group_id' => $data->originalTaxGroupId ?? $data->taxGroupId,
            'original_tax_amount' => $originalTax,
            'original_gross_amount' => $originalGross,
            'original_withholding_amount' => $originalWithholding,
            'recoverable_input_tax_amount' => $this->math->normalize($data->recoverableInputTaxAmount),
            'recovery_base_amount' => $recoveryBase,
            'recovery_tax_group_id' => $data->recoveryTaxGroupId,
            'recovery_tax_amount' => $this->math->normalize($data->recoveryTaxAmount),
            'recovery_withholding_amount' => $this->math->normalize($data->recoveryWithholdingAmount),
            'markup_amount' => $markup,
        ];
    }

    private function usageLogForAgreement(RentalAgreement $agreement, int $usageLogId): RentalUsageLog
    {
        $usageLog = RentalUsageLog::query()
            ->where('tenant_id', $agreement->tenant_id)
            ->where('organization_unit_id', $agreement->organization_unit_id)
            ->whereKey($usageLogId)
            ->whereHas('contexts', fn ($query) => $query
                ->where('tenant_id', $agreement->tenant_id)
                ->where('organization_unit_id', $agreement->organization_unit_id)
                ->where('agreement_id', $agreement->getKey()))
            ->lockForUpdate()
            ->first();
        if ($usageLog === null) {
            throw new InvalidArgumentException(
                'Rental expense usage log does not belong to the agreement context.',
            );
        }

        return $usageLog;
    }

    private function responsiblePartyType(
        RentalAgreement $agreement,
        RentalExpenseFinancialTreatment $treatment,
    ): ?string {
        return match ($treatment) {
            RentalExpenseFinancialTreatment::CustomerBillable => 'customer',
            RentalExpenseFinancialTreatment::OwnerPayable,
            RentalExpenseFinancialTreatment::SupplierRecoverable => 'supplier',
            RentalExpenseFinancialTreatment::EmployeeReimbursable => 'employee',
            default => null,
        };
    }

    private function responsiblePartyId(
        RentalAgreement $agreement,
        RentalExpenseData $data,
    ): ?int {
        return match ($data->financialTreatment) {
            RentalExpenseFinancialTreatment::CustomerBillable,
            RentalExpenseFinancialTreatment::OwnerPayable => (int) $agreement->party_id,
            RentalExpenseFinancialTreatment::SupplierRecoverable,
            RentalExpenseFinancialTreatment::EmployeeReimbursable => $data->responsiblePartyId,
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
            'subject_id' => $expense->getKey(),
            'old_status' => $old?->value,
            'new_status' => $new->value,
            'reason' => $reason,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }
}
