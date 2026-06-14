<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Tax\DTOs\TaxCalculationData;
use Modules\Tax\DTOs\TaxCalculationLineData;
use Modules\Tax\Services\TaxCalculationService;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalChargeInvoiceStatus;
use Modules\VehicleRental\Enums\RentalChargeStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalCharge;
use Modules\VehicleRental\Models\RentalChargeCalculation;
use Modules\VehicleRental\Models\RentalStatusHistory;

final class RentalChargeService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly TaxCalculationService $taxes,
    ) {}

    /**
     * @param  Collection<int, RentalChargeCalculation>  $calculations
     * @return Collection<int, RentalCharge>
     */
    public function createFromCalculations(
        RentalAgreement $agreement,
        Collection $calculations,
    ): Collection {
        return $this->buildFromCalculations($agreement, $calculations, true);
    }

    /**
     * @param  Collection<int, RentalChargeCalculation>  $calculations
     * @return Collection<int, RentalCharge>
     */
    public function previewFromCalculations(
        RentalAgreement $agreement,
        Collection $calculations,
    ): Collection {
        return $this->buildFromCalculations($agreement, $calculations, false);
    }

    /**
     * @param  Collection<int, RentalChargeCalculation>  $calculations
     * @return Collection<int, RentalCharge>
     */
    private function buildFromCalculations(
        RentalAgreement $agreement,
        Collection $calculations,
        bool $persist,
    ): Collection {
        if ($calculations->isEmpty()) {
            throw new InvalidArgumentException('No rental charge calculations were produced.');
        }
        $agreement->loadMissing('rateSnapshot');
        $lines = [];
        foreach ($calculations->values() as $index => $calculation) {
            $lines[] = new TaxCalculationLineData(
                lineNumber: $index + 1,
                quantity: (string) $calculation->chargeable_quantity,
                unitPrice: (string) $calculation->rate,
                taxableAmount: (string) $calculation->amount,
                taxGroupId: $agreement->rateSnapshot?->tax_profile_id,
            );
        }
        $tax = $this->taxes->calculate(new TaxCalculationData(
            tenantId: (int) $agreement->tenant_id,
            documentType: $agreement->direction === RentalAgreementDirection::Outbound
                ? 'rental_outbound'
                : 'rental_inbound',
            documentDate: $agreement->agreement_date->toDateString(),
            organizationUnitId: $agreement->organization_unit_id,
            customerId: $agreement->direction === RentalAgreementDirection::Outbound
                ? (int) $agreement->party_id
                : null,
            supplierId: $agreement->direction === RentalAgreementDirection::Inbound
                ? (int) $agreement->party_id
                : null,
            documentTaxGroupId: $agreement->rateSnapshot?->tax_profile_id,
            lines: $lines,
        ));

        $charges = collect();
        foreach ($calculations->values() as $index => $calculation) {
            $lineTax = $tax->lineResults[$index] ?? null;
            $taxAmount = $lineTax === null
                ? '0.000000'
                : (string) $lineTax->taxAmount;
            $withholdingAmount = $lineTax === null ? '0.000000' : (string) $lineTax->withholdingAmount;
            $total = $this->math->sub(
                $this->math->add((string) $calculation->amount, $taxAmount),
                $withholdingAmount,
            );

            $attributes = [
                'tenant_id' => $agreement->tenant_id,
                'organization_unit_id' => $agreement->organization_unit_id,
                'agreement_id' => $agreement->getKey(),
                'charge_calculation_id' => $persist ? $calculation->getKey() : null,
                'financial_side' => $calculation->financial_side->value,
                'charge_type' => $calculation->calculation_type->value,
                'description' => $calculation->description ?? str($calculation->calculation_type->value)->replace('_', ' ')->title(),
                'quantity' => $calculation->chargeable_quantity,
                'rate' => $calculation->rate,
                'amount' => $calculation->amount,
                'discount_amount' => '0.000000',
                'tax_amount' => $taxAmount,
                'withholding_amount' => $withholdingAmount,
                'tax_group_id' => $agreement->rateSnapshot?->tax_profile_id,
                'total_amount' => $total,
                'invoice_status' => RentalChargeInvoiceStatus::NotInvoiced->value,
                'status' => RentalChargeStatus::Draft->value,
            ];
            $charge = new RentalCharge;
            $charge->forceFill($attributes);
            if ($persist) {
                $charge->save();
            } else {
                $charge->setAttribute('id', -($index + 1));
            }
            $charges->push($charge);
        }

        return $charges;
    }

    public function approve(
        RentalCharge $charge,
        ?int $changedBy = null,
        ?string $reason = null,
    ): RentalCharge {
        return DB::transaction(function () use ($charge, $changedBy, $reason): RentalCharge {
            $charge = RentalCharge::query()->lockForUpdate()->findOrFail($charge->getKey());
            if ($charge->status !== RentalChargeStatus::Draft) {
                throw new InvalidArgumentException('Only draft rental charges can be approved.');
            }
            $old = $charge->status;
            $charge->status = RentalChargeStatus::Approved;
            $charge->save();
            $this->recordStatus($charge, $old, RentalChargeStatus::Approved, $changedBy, $reason);

            return $charge->refresh();
        });
    }

    /**
     * @return Collection<int, RentalCharge>
     */
    public function approveAgreementCharges(
        RentalAgreement $agreement,
        ?int $changedBy = null,
        ?string $reason = null,
    ): Collection {
        return DB::transaction(function () use ($agreement, $changedBy, $reason): Collection {
            RentalAgreement::query()->lockForUpdate()->findOrFail($agreement->getKey());
            $charges = $agreement->charges()
                ->where('status', RentalChargeStatus::Draft->value)
                ->lockForUpdate()
                ->get();
            foreach ($charges as $charge) {
                $old = $charge->status;
                $charge->status = RentalChargeStatus::Approved;
                $charge->save();
                $this->recordStatus($charge, $old, RentalChargeStatus::Approved, $changedBy, $reason);
            }

            return $agreement->charges()->orderBy('id')->get();
        });
    }

    public function cancel(
        RentalCharge $charge,
        ?int $changedBy = null,
        ?string $reason = null,
    ): RentalCharge {
        return DB::transaction(function () use ($charge, $changedBy, $reason): RentalCharge {
            $charge = RentalCharge::query()->lockForUpdate()->findOrFail($charge->getKey());
            if ($charge->invoice_status !== RentalChargeInvoiceStatus::NotInvoiced) {
                throw new InvalidArgumentException('Invoiced rental charges cannot be cancelled.');
            }
            $old = $charge->status;
            $charge->status = RentalChargeStatus::Cancelled;
            $charge->save();
            $this->recordStatus($charge, $old, RentalChargeStatus::Cancelled, $changedBy, $reason);

            return $charge->refresh();
        });
    }

    private function recordStatus(
        RentalCharge $charge,
        RentalChargeStatus $old,
        RentalChargeStatus $new,
        ?int $changedBy,
        ?string $reason,
    ): void {
        RentalStatusHistory::query()->create([
            'tenant_id' => $charge->tenant_id,
            'organization_unit_id' => $charge->organization_unit_id,
            'agreement_id' => $charge->agreement_id,
            'charge_id' => $charge->getKey(),
            'entity_type' => 'charge',
            'old_status' => $old->value,
            'new_status' => $new->value,
            'reason' => $reason,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }
}
