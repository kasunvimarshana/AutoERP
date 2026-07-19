<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\VehicleRental\Enums\VehicleFinanceInstallmentStatus;
use Modules\VehicleRental\Models\VehicleFinanceInstallment;
use Modules\VehicleRental\Models\VehicleFinanceStatusHistory;

final class VehicleFinanceInstallmentStatusRefreshService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function refresh(int $tenantId, ?int $organizationUnitId, ?string $asAt = null): int
    {
        $date = CarbonImmutable::parse($asAt ?? now())->toDateString();
        $updated = 0;

        VehicleFinanceInstallment::query()
            ->forContext($tenantId, $organizationUnitId)
            ->whereNotIn('status', [VehicleFinanceInstallmentStatus::Waived->value, VehicleFinanceInstallmentStatus::Reversed->value])
            ->select('id')
            ->chunkById(200, function ($installments) use ($date, &$updated): void {
                foreach ($installments as $installment) {
                    if ($this->refreshOne((int) $installment->getKey(), $date)) {
                        $updated++;
                    }
                }
            });

        return $updated;
    }

    private function refreshOne(int $installmentId, string $date): bool
    {
        return DB::transaction(function () use ($installmentId, $date): bool {
            $installment = VehicleFinanceInstallment::query()
                ->with('financeAgreement')
                ->lockForUpdate()
                ->findOrFail($installmentId);
            $old = $installment->status instanceof VehicleFinanceInstallmentStatus
                ? $installment->status
                : VehicleFinanceInstallmentStatus::from((string) $installment->status);

            if (in_array($old, [VehicleFinanceInstallmentStatus::Waived, VehicleFinanceInstallmentStatus::Reversed], true)) {
                return false;
            }

            if ($installment->invoice_id !== null) {
                $invoice = Invoice::query()
                    ->where('tenant_id', $installment->tenant_id)
                    ->with(['balance' => fn ($query) => $query->lockForUpdate()])
                    ->lockForUpdate()
                    ->find($installment->invoice_id);

                if ($invoice !== null && ! in_array($invoice->status, [InvoiceStatus::Cancelled, InvoiceStatus::Void], true)) {
                    $balance = (string) ($invoice->balance?->balance_due ?? $invoice->balance_due ?? $installment->total_due);
                    $paid = $this->math->sub((string) $installment->total_due, $balance);
                    $installment->paid_amount = $this->math->isNegative($paid) ? '0.000000' : $paid;
                    $installment->balance_due = $this->math->isNegative($balance) ? '0.000000' : $balance;
                } else {
                    $installment->invoice_id = null;
                    $installment->paid_amount = '0.000000';
                    $installment->balance_due = (string) $installment->total_due;
                }
            }

            $new = match (true) {
                $this->math->isZero((string) $installment->balance_due) => VehicleFinanceInstallmentStatus::Paid,
                $installment->due_date->toDateString() < $date => VehicleFinanceInstallmentStatus::Overdue,
                $installment->due_date->toDateString() === $date => VehicleFinanceInstallmentStatus::Due,
                $this->math->compare((string) $installment->paid_amount, '0') > 0 => VehicleFinanceInstallmentStatus::PartiallyPaid,
                default => VehicleFinanceInstallmentStatus::Scheduled,
            };
            $dirtyFinancials = $installment->isDirty(['invoice_id', 'paid_amount', 'balance_due']);
            if ($old !== $new || $dirtyFinancials) {
                $installment->status = $new;
                $installment->row_version = (int) $installment->row_version + 1;
                $installment->save();
            }
            if ($old !== $new) {
                VehicleFinanceStatusHistory::query()->create([
                    'tenant_id' => $installment->tenant_id,
                    'organization_unit_id' => $installment->organization_unit_id,
                    'finance_agreement_id' => $installment->finance_agreement_id,
                    'installment_id' => $installment->getKey(),
                    'old_status' => $old->value,
                    'new_status' => $new->value,
                    'changed_by' => null,
                    'changed_at' => now(),
                ]);
            }

            return $old !== $new;
        });
    }
}
