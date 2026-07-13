<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Models\Item;
use Modules\VehicleService\DTOs\VehicleServiceLineData;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Enums\VehicleServiceLineStatus;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;

final class VehicleServiceLineCalculationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly VehicleServiceCommissionService $commissions,
        private readonly VehicleServiceLineRuleService $rules,
    ) {}

    /** @return array<string, mixed> */
    public function attributes(VehicleServiceLineData $data, ?Item $item): array
    {
        $subtotal = $this->math->mul($data->quantity, $data->unitPrice);
        $discount = $this->adjustment(
            $data->discountCalculationType,
            $data->discountRate,
            $data->discountAmount,
            $subtotal,
        );
        $tax = $this->adjustment(
            $data->taxCalculationType,
            $data->taxRate,
            $data->taxAmount,
            $subtotal,
        );
        $charge = $this->adjustment(
            $data->chargeCalculationType,
            $data->chargeRate,
            $data->chargeAmount,
            $subtotal,
        );
        $lineTotal = $this->math->add(
            $this->math->sub($subtotal, $discount),
            $this->math->add($tax, $charge),
        );
        if ($this->math->isNegative($lineTotal)) {
            throw new InvalidArgumentException('Line total cannot be negative.');
        }

        $defaults = $this->rules->flags($data->lineSourceType, $item);
        $customerSupplied = $data->isCustomerSupplied;
        $external = $data->lineSourceType === VehicleServiceLineSourceType::ExternalItem;
        $comboChild = $data->lineSourceType === VehicleServiceLineSourceType::ComboChild;

        return [
            'parent_line_id' => $data->parentLineId,
            'line_source_type' => $data->lineSourceType->value,
            'item_id' => $data->itemId,
            'item_variant_id' => $data->itemVariantId,
            'uom_id' => $data->uomId ?? $item?->base_uom_id,
            'description' => trim($data->description),
            'quantity' => $this->math->normalize($data->quantity),
            'unit_cost' => $this->math->normalize($data->unitCost),
            'unit_price' => $this->math->normalize($data->unitPrice),
            'discount_calculation_type' => $data->discountCalculationType,
            'discount_rate' => $this->math->normalize($data->discountRate),
            'discount_amount' => $discount,
            'tax_calculation_type' => $data->taxCalculationType,
            'tax_rate' => $this->math->normalize($data->taxRate),
            'tax_amount' => $tax,
            'charge_calculation_type' => $data->chargeCalculationType,
            'charge_rate' => $this->math->normalize($data->chargeRate),
            'charge_amount' => $charge,
            'line_total' => $lineTotal,
            'is_inventory_tracked' => $defaults['inventory'] && ! $customerSupplied && ! $external,
            'is_customer_supplied' => $customerSupplied,
            'is_external' => $external,
            'is_billable' => ! $customerSupplied && ! $comboChild && ($data->isBillable ?? true),
            'is_employee_assignable' => $defaults['employee'],
        ];
    }

    public function recalculateJob(VehicleServiceJob $job): VehicleServiceJob
    {
        $job->load('lines');
        $billable = $job->lines->where('is_billable', true)
            ->where('status', '!=', VehicleServiceLineStatus::Cancelled);
        $subtotal = '0.000000';
        $discount = '0.000000';
        $tax = '0.000000';
        $charge = '0.000000';
        $grand = '0.000000';

        foreach ($billable as $line) {
            $subtotal = $this->math->add(
                $subtotal,
                $this->math->mul((string) $line->quantity, (string) $line->unit_price),
            );
            $discount = $this->math->add($discount, (string) $line->discount_amount);
            $tax = $this->math->add($tax, (string) $line->tax_amount);
            $charge = $this->math->add($charge, (string) $line->charge_amount);
            $grand = $this->math->add($grand, (string) $line->line_total);
        }

        $job->forceFill([
            'subtotal' => $subtotal,
            'discount_total' => $discount,
            'tax_total' => $tax,
            'charge_total' => $charge,
            'grand_total' => $grand,
            'supervisor_commission_amount' => $this->commissions->calculate(
                $job->supervisor_commission_type,
                (string) $job->supervisor_commission_value,
                $grand,
            ),
        ])->save();

        return $job->refresh();
    }

    public function recalculateAssignments(VehicleServiceJobLine $line): void
    {
        foreach ($line->employeeAssignments as $assignment) {
            $assignment->commission_amount = $this->commissions->calculate(
                $assignment->commission_type,
                (string) $assignment->commission_value,
                (string) $line->line_total,
            );
            $assignment->save();
        }
    }

    private function adjustment(?string $type, string $rate, string $amount, string $base): string
    {
        if ($type === 'percentage') {
            if ($this->math->compare($rate, '100.000000') > 0) {
                throw new InvalidArgumentException('Line percentage cannot exceed 100.');
            }

            return $this->math->div($this->math->mul($base, $rate), '100.000000');
        }

        return $this->math->normalize($amount);
    }
}
