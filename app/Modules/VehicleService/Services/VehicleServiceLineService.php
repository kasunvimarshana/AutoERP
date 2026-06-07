<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Enums\ItemType;
use Modules\Item\Models\Item;
use Modules\VehicleService\DTOs\VehicleServiceLineData;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Enums\VehicleServiceLineStatus;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;

final class VehicleServiceLineService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly VehicleServiceValidationService $validator,
        private readonly VehicleServiceCommissionService $commissions,
    ) {}

    public function create(VehicleServiceJob $job, VehicleServiceLineData $data): VehicleServiceJobLine
    {
        $this->validator->assertMutable($job);

        return DB::transaction(function () use ($job, $data): VehicleServiceJobLine {
            $line = $this->persist($job, $data);
            if ($data->lineSourceType === VehicleServiceLineSourceType::ComboParent && $data->expandCombo) {
                $this->expandCombo($job, $line);
            }
            $this->recalculateJob($job);

            return $line->refresh()->load(['item', 'variant', 'uom', 'children.item', 'children.uom']);
        });
    }

    public function update(VehicleServiceJob $job, VehicleServiceJobLine $line, VehicleServiceLineData $data): VehicleServiceJobLine
    {
        $this->assertBelongsToJob($job, $line);
        $this->validator->assertMutable($job);
        if ($line->inventory_movement_id !== null) {
            throw new InvalidArgumentException('Issued inventory lines cannot be edited.');
        }

        return DB::transaction(function () use ($job, $line, $data): VehicleServiceJobLine {
            $item = $this->validator->validateLine($job, $data);
            $attributes = $this->attributes($data, $item);
            if ($line->employeeAssignments()->exists() && ! $attributes['is_employee_assignable']) {
                throw new InvalidArgumentException('Remove employee assignments before changing this to a non-assignable line.');
            }
            $line->fill($attributes);
            $line->save();
            $this->recalculateAssignments($line);
            $this->recalculateJob($job);

            return $line->refresh()->load(['item', 'variant', 'uom', 'children.item', 'children.uom']);
        });
    }

    public function delete(VehicleServiceJob $job, VehicleServiceJobLine $line): void
    {
        $this->assertBelongsToJob($job, $line);
        $this->validator->assertMutable($job);
        if ($line->inventory_movement_id !== null || $line->children()->whereNotNull('inventory_movement_id')->exists()) {
            throw new InvalidArgumentException('Issued inventory lines cannot be deleted.');
        }

        DB::transaction(function () use ($job, $line): void {
            $line->delete();
            $this->renumber($job);
            $this->recalculateJob($job);
        });
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
            $subtotal = $this->math->add($subtotal, $this->math->mul((string) $line->quantity, (string) $line->unit_price));
            $discount = $this->math->add($discount, (string) $line->discount_amount);
            $tax = $this->math->add($tax, (string) $line->tax_amount);
            $charge = $this->math->add($charge, (string) $line->charge_amount);
            $grand = $this->math->add($grand, (string) $line->line_total);
        }

        $job->fill([
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

    public function isInventoryIssueLine(VehicleServiceJobLine $line): bool
    {
        $line->loadMissing('item');

        return $line->is_inventory_tracked
            && ! $line->is_customer_supplied
            && ! $line->is_external
            && ($line->line_source_type === VehicleServiceLineSourceType::InventoryItem
                || ($line->line_source_type === VehicleServiceLineSourceType::ComboChild && (bool) $line->item?->is_stockable));
    }

    private function persist(VehicleServiceJob $job, VehicleServiceLineData $data): VehicleServiceJobLine
    {
        $item = $this->validator->validateLine($job, $data);

        return VehicleServiceJobLine::query()->create(array_merge($this->attributes($data, $item), [
            'tenant_id' => $job->tenant_id,
            'organization_unit_id' => $job->organization_unit_id,
            'vehicle_service_job_id' => $job->getKey(),
            'line_number' => ((int) $job->lines()->max('line_number')) + 1,
            'status' => VehicleServiceLineStatus::Pending->value,
        ]));
    }

    /** @return array<string, mixed> */
    private function attributes(VehicleServiceLineData $data, ?Item $item): array
    {
        $subtotal = $this->math->mul($data->quantity, $data->unitPrice);
        $discount = $this->adjustment($data->discountCalculationType, $data->discountRate, $data->discountAmount, $subtotal);
        $tax = $this->adjustment($data->taxCalculationType, $data->taxRate, $data->taxAmount, $subtotal);
        $charge = $this->adjustment($data->chargeCalculationType, $data->chargeRate, $data->chargeAmount, $subtotal);
        $lineTotal = $this->math->add($this->math->sub($subtotal, $discount), $this->math->add($tax, $charge));
        if ($this->math->isNegative($lineTotal)) {
            throw new InvalidArgumentException('Line total cannot be negative.');
        }

        $defaults = $this->flags($data->lineSourceType, $item);

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
            'is_inventory_tracked' => $data->isInventoryTracked ?? $defaults['inventory'],
            'is_customer_supplied' => $data->isCustomerSupplied,
            'is_external' => $data->isExternal ?? $defaults['external'],
            'is_billable' => $data->isBillable ?? ! $data->isCustomerSupplied,
            'is_employee_assignable' => $data->isEmployeeAssignable ?? $defaults['employee'],
        ];
    }

    private function expandCombo(VehicleServiceJob $job, VehicleServiceJobLine $parent): void
    {
        $parent->load('item.bundleLines.childItem');
        foreach ($parent->item?->bundleLines ?? [] as $bundleLine) {
            $child = $bundleLine->childItem;
            if ($child === null) {
                continue;
            }
            $quantity = $this->math->mul((string) $parent->quantity, (string) $bundleLine->quantity);
            $this->persist($job, new VehicleServiceLineData(
                lineSourceType: VehicleServiceLineSourceType::ComboChild,
                description: (string) $child->name,
                quantity: $quantity,
                unitPrice: '0.000000',
                parentLineId: (int) $parent->getKey(),
                itemId: (int) $child->getKey(),
                itemVariantId: $bundleLine->child_variant_id,
                uomId: $bundleLine->uom_id ?? $child->base_uom_id,
                isBillable: false,
                expandCombo: false,
            ));
        }
    }

    /** @return array{inventory: bool, external: bool, employee: bool} */
    private function flags(VehicleServiceLineSourceType $source, ?Item $item): array
    {
        return match ($source) {
            VehicleServiceLineSourceType::InventoryItem => ['inventory' => true, 'external' => false, 'employee' => false],
            VehicleServiceLineSourceType::ExternalItem => ['inventory' => false, 'external' => true, 'employee' => false],
            VehicleServiceLineSourceType::ServiceItem, VehicleServiceLineSourceType::LabourItem => ['inventory' => false, 'external' => false, 'employee' => true],
            VehicleServiceLineSourceType::ComboParent => ['inventory' => false, 'external' => false, 'employee' => false],
            VehicleServiceLineSourceType::ComboChild => [
                'inventory' => (bool) $item?->is_stockable,
                'external' => false,
                'employee' => in_array($item?->item_type, [ItemType::Service, ItemType::Labour], true),
            ],
        };
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

    private function recalculateAssignments(VehicleServiceJobLine $line): void
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

    private function renumber(VehicleServiceJob $job): void
    {
        foreach ($job->lines()->orderBy('line_number')->get() as $index => $line) {
            $line->line_number = $index + 1;
            $line->save();
        }
    }

    private function assertBelongsToJob(VehicleServiceJob $job, VehicleServiceJobLine $line): void
    {
        if ((int) $line->vehicle_service_job_id !== (int) $job->getKey()) {
            throw new InvalidArgumentException('Service job line does not belong to the service job.');
        }
    }
}
