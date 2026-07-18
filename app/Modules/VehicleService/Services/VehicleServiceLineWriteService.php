<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Enums\ItemType;
use Modules\Item\Models\Item;
use Modules\Item\Services\ItemPriceResolutionService;
use Modules\VehicleService\DTOs\VehicleServiceLineData;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Enums\VehicleServiceLineStatus;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;
use Modules\VehicleService\Services\Concerns\AssertsVehicleServiceExpectedVersion;

final class VehicleServiceLineWriteService
{
    use AssertsVehicleServiceExpectedVersion;

    private const ZERO_AMOUNT = '0.000000';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly VehicleServiceValidationService $jobValidator,
        private readonly VehicleServiceLineValidator $lineValidator,
        private readonly VehicleServiceLineRuleService $rules,
        private readonly VehicleServiceLineCalculationService $calculations,
        private readonly ItemPriceResolutionService $prices,
    ) {}

    public function create(
        VehicleServiceJob $job,
        VehicleServiceLineData $data,
        ?int $expectedVersion = null,
    ): VehicleServiceJobLine {
        return DB::transaction(function () use ($job, $data, $expectedVersion): VehicleServiceJobLine {
            $job = VehicleServiceJob::query()->lockForUpdate()->findOrFail($job->getKey());
            $this->assertExpectedVersion($job, $expectedVersion);
            $this->jobValidator->assertMutable($job);

            $line = $this->persist($job, $data);
            if ($data->lineSourceType === VehicleServiceLineSourceType::ComboParent && $data->expandCombo) {
                $this->expandCombo($job, $line);
            }
            $this->calculations->recalculateJob($job);

            return $line->refresh()->load(['item', 'variant', 'uom', 'children.item', 'children.uom']);
        });
    }

    public function update(
        VehicleServiceJob $job,
        VehicleServiceJobLine $line,
        VehicleServiceLineData $data,
        ?int $expectedVersion = null,
    ): VehicleServiceJobLine {
        return DB::transaction(function () use ($job, $line, $data, $expectedVersion): VehicleServiceJobLine {
            $job = VehicleServiceJob::query()->lockForUpdate()->findOrFail($job->getKey());
            $line = $job->lines()->with('job')->findOrFail($line->getKey());
            $this->assertExpectedVersion($job, $expectedVersion);
            $this->rules->assertBelongsToJob($job, $line);
            $this->jobValidator->assertMutable($job);
            $this->rules->assertCanUpdate($line, $data);

            $item = $this->lineValidator->validate($job, $data);
            $attributes = $this->calculations->attributes($data, $item);
            if ($line->employeeAssignments()->exists() && ! $attributes['is_employee_assignable']) {
                throw new InvalidArgumentException(
                    'Remove employee assignments before changing this to a non-assignable line.',
                );
            }

            $line->fill($attributes);
            $line->save();
            $this->calculations->recalculateAssignments($line);
            $this->calculations->recalculateJob($job);

            return $line->refresh()->load(['item', 'variant', 'uom', 'children.item', 'children.uom']);
        });
    }

    public function delete(VehicleServiceJob $job, VehicleServiceJobLine $line, ?int $expectedVersion = null): void
    {
        DB::transaction(function () use ($job, $line, $expectedVersion): void {
            $job = VehicleServiceJob::query()->lockForUpdate()->findOrFail($job->getKey());
            $line = $job->lines()->with('job')->findOrFail($line->getKey());
            $this->assertExpectedVersion($job, $expectedVersion);
            $this->rules->assertBelongsToJob($job, $line);
            $this->jobValidator->assertMutable($job);
            $this->rules->assertCanDelete($line);

            $line->delete();
            $this->renumber($job);
            $this->calculations->recalculateJob($job);
        });
    }

    private function persist(VehicleServiceJob $job, VehicleServiceLineData $data): VehicleServiceJobLine
    {
        $item = $this->lineValidator->validate($job, $data);

        return VehicleServiceJobLine::query()->create(array_merge(
            $this->calculations->attributes($data, $item),
            [
                'tenant_id' => $job->tenant_id,
                'organization_unit_id' => $job->organization_unit_id,
                'vehicle_service_job_id' => $job->getKey(),
                'line_number' => ((int) $job->lines()->max('line_number')) + 1,
                'status' => VehicleServiceLineStatus::Pending->value,
            ],
        ));
    }

    private function expandCombo(VehicleServiceJob $job, VehicleServiceJobLine $parent): void
    {
        $parent->load('item.bundleLines.childItem');
        if ($parent->item === null || $parent->item->bundleLines->isEmpty()) {
            throw new InvalidArgumentException('Combo parent item must contain at least one valid bundle line.');
        }

        foreach ($parent->item->bundleLines as $bundleLine) {
            $child = $bundleLine->childItem;
            if ($child === null) {
                throw new InvalidArgumentException('Combo bundle contains an unavailable child item.');
            }

            $this->persist($job, new VehicleServiceLineData(
                lineSourceType: VehicleServiceLineSourceType::ComboChild,
                description: (string) $child->name,
                quantity: $this->math->mul((string) $parent->quantity, (string) $bundleLine->quantity),
                unitPrice: $this->comboChildUnitPrice(
                    $job,
                    $child,
                    $bundleLine->uom_id ?? $child->base_uom_id,
                    $bundleLine->child_variant_id,
                ),
                parentLineId: (int) $parent->getKey(),
                itemId: (int) $child->getKey(),
                itemVariantId: $bundleLine->child_variant_id,
                uomId: $bundleLine->uom_id ?? $child->base_uom_id,
                isBillable: false,
                expandCombo: false,
            ));
        }
    }

    private function comboChildUnitPrice(
        VehicleServiceJob $job,
        Item $child,
        ?int $uomId,
        ?int $variantId,
    ): string {
        if (! in_array($child->item_type, [ItemType::Service, ItemType::Labour], true)) {
            return self::ZERO_AMOUNT;
        }

        $price = $this->prices->resolvePrice(
            item: $child,
            context: ItemPriceResolutionService::CONTEXT_SERVICE,
            uomId: $uomId,
            organizationUnitId: $job->organization_unit_id,
            date: $job->job_date?->toDateString(),
            variantId: $variantId,
        );
        if ($price->amount === null) {
            throw new InvalidArgumentException(
                "Combo child item {$child->code} requires an effective service price for the selected UOM.",
            );
        }

        return $price->amount;
    }

    private function renumber(VehicleServiceJob $job): void
    {
        foreach ($job->lines()->orderBy('line_number')->get() as $index => $line) {
            $line->line_number = $index + 1;
            $line->save();
        }
    }
}
