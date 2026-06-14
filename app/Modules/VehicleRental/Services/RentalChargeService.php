<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Collection;
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

final class RentalChargeService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly TaxCalculationService $taxes,
    ) {}

    /**
     * @param Collection<int, RentalChargeCalculation> $calculations
     * @return Collection<int, RentalCharge>
     */
    public function createFromCalculations(
        RentalAgreement $agreement,
        Collection $calculations,
    ): Collection {
        if ($calculations->isEmpty()) {
            throw new InvalidArgumentException('No rental charge calculations were produced.');
        }
        $agreement->loadMissing('rateSnapshot');
        $lines = [];
        foreach ($calculations->values() as $index => $calculation) {
            $lines[] = new TaxCalculationLineData(
                lineNumber: $index + 1,
                quantity: (string) $calculation->quantity,
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
                : $this->math->sub($lineTax->taxAmount, $lineTax->withholdingAmount);
            $total = $this->math->add((string) $calculation->amount, $taxAmount);

            $charges->push(RentalCharge::query()->create([
                'tenant_id' => $agreement->tenant_id,
                'organization_unit_id' => $agreement->organization_unit_id,
                'agreement_id' => $agreement->getKey(),
                'charge_calculation_id' => $calculation->getKey(),
                'charge_type' => $calculation->calculation_type->value,
                'description' => $calculation->description ?? str($calculation->calculation_type->value)->replace('_', ' ')->title(),
                'quantity' => $calculation->quantity,
                'rate' => $calculation->rate,
                'amount' => $calculation->amount,
                'discount_amount' => '0.000000',
                'tax_amount' => $taxAmount,
                'total_amount' => $total,
                'invoice_status' => RentalChargeInvoiceStatus::NotInvoiced->value,
                'status' => RentalChargeStatus::Draft->value,
            ]));
        }

        return $charges;
    }

    public function approve(RentalCharge $charge): RentalCharge
    {
        if ($charge->status !== RentalChargeStatus::Draft) {
            throw new InvalidArgumentException('Only draft rental charges can be approved.');
        }
        $charge->status = RentalChargeStatus::Approved;
        $charge->save();

        return $charge->refresh();
    }

    /**
     * @return Collection<int, RentalCharge>
     */
    public function approveAgreementCharges(RentalAgreement $agreement): Collection
    {
        $agreement->charges()
            ->where('status', RentalChargeStatus::Draft->value)
            ->update(['status' => RentalChargeStatus::Approved->value, 'updated_at' => now()]);

        return $agreement->charges()->orderBy('id')->get();
    }

    public function cancel(RentalCharge $charge): RentalCharge
    {
        if ($charge->invoice_status !== RentalChargeInvoiceStatus::NotInvoiced) {
            throw new InvalidArgumentException('Invoiced rental charges cannot be cancelled.');
        }
        $charge->status = RentalChargeStatus::Cancelled;
        $charge->save();

        return $charge->refresh();
    }
}
