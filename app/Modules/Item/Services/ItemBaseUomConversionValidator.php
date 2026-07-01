<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemUnit;
use Modules\UOM\Models\UnitOfMeasureModel;
use Modules\UOM\Services\UomConversionService;

final class ItemBaseUomConversionValidator
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly ItemBaseUomUsageAuditService $usageAudit,
        private readonly UomConversionService $uomConversions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function validate(
        Item $item,
        int $newBaseUomId,
        ?string $providedFactor = null,
        ?string $effectiveAt = null,
    ): array {
        $audit = $this->usageAudit->audit($item);
        $blockers = $audit['blockers'];
        $warnings = $audit['warnings'];
        $oldUom = $item->baseUom()->first();
        $newUom = UnitOfMeasureModel::query()->find($newBaseUomId);
        $factor = null;
        $factorSource = null;

        if (! $oldUom instanceof UnitOfMeasureModel) {
            $blockers[] = $this->blocker('missing_old_uom', 'A used item must have an existing base UOM before conversion.');
        }

        if ($newUom === null) {
            $blockers[] = $this->blocker('new_uom_not_found', 'The selected new base UOM was not found.');
        } else {
            if ((int) $newUom->tenant_id !== (int) $item->tenant_id) {
                $blockers[] = $this->blocker('new_uom_tenant_mismatch', 'The selected UOM belongs to a different tenant.');
            }
            if ($item->organization_unit_id !== null
                && $newUom->organization_unit_id !== null
                && (int) $newUom->organization_unit_id !== (int) $item->organization_unit_id) {
                $blockers[] = $this->blocker('new_uom_org_mismatch', 'The selected UOM belongs to a different organization unit.');
            }
            if (! (bool) $newUom->is_active) {
                $blockers[] = $this->blocker('new_uom_inactive', 'The selected new base UOM is inactive.');
            }
        }

        if ($oldUom instanceof UnitOfMeasureModel && $newUom instanceof UnitOfMeasureModel) {
            if ((int) $oldUom->getKey() === (int) $newUom->getKey()) {
                $blockers[] = $this->blocker('same_uom', 'Old and new base UOM cannot be the same.');
            }
            if ($oldUom->type !== $newUom->type || $oldUom->category !== $newUom->category) {
                $blockers[] = $this->blocker('incompatible_uom', 'Old and new base UOM must use the same type and category.');
            }

            [$factor, $factorSource] = $this->resolveFactor($item, $newUom, $providedFactor);
        }

        if ($factor === null) {
            $blockers[] = $this->blocker('missing_conversion_factor', 'A positive old-base-to-new-base conversion factor is required.');
        } elseif ($this->math->compare($factor, '0') <= 0) {
            $blockers[] = $this->blocker('invalid_conversion_factor', 'Conversion factor must be greater than zero.');
        }

        $effective = $effectiveAt === null
            ? CarbonImmutable::now()
            : CarbonImmutable::parse($effectiveAt);
        if ($effective->isFuture()) {
            $blockers[] = $this->blocker('future_effective_at', 'Future-dated conversion is not supported because operational quantities change immediately.');
        }

        return [
            'is_valid' => $blockers === [],
            'old_uom' => $oldUom,
            'new_uom' => $newUom,
            'conversion_factor' => $factor,
            'factor_source' => $factorSource,
            'effective_at' => $effective,
            'blockers' => $blockers,
            'warnings' => $warnings,
            'audit' => $audit,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function assertValid(
        Item $item,
        int $newBaseUomId,
        ?string $providedFactor = null,
        ?string $effectiveAt = null,
    ): array {
        $validation = $this->validate($item, $newBaseUomId, $providedFactor, $effectiveAt);
        if (! $validation['is_valid']) {
            $blocker = $validation['blockers'][0];

            throw ValidationException::withMessages([
                $this->blockerField((string) $blocker['code']) => [(string) $blocker['message']],
            ]);
        }

        return $validation;
    }

    /**
     * Factor orientation is old base -> new base.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveFactor(Item $item, UnitOfMeasureModel $newUom, ?string $providedFactor): array
    {
        if ($providedFactor !== null && trim($providedFactor) !== '') {
            try {
                return [$this->math->normalize($providedFactor), 'provided'];
            } catch (InvalidArgumentException) {
                return [null, null];
            }
        }

        $itemUnit = ItemUnit::query()
            ->where('tenant_id', $item->tenant_id)
            ->where('item_id', $item->getKey())
            ->where('uom_id', $newUom->getKey())
            ->where('is_active', true)
            ->first();
        if ($itemUnit instanceof ItemUnit && ! $this->math->isZero((string) $itemUnit->conversion_factor)) {
            return [$this->math->div('1', (string) $itemUnit->conversion_factor), 'item_unit'];
        }

        $result = $this->uomConversions->getConversionFactor(
            (int) $item->base_uom_id,
            (int) $newUom->getKey(),
            (int) $item->tenant_id,
        );

        return $result->isSuccess()
            ? [$this->math->normalize((string) $result->valueOrFail()), 'uom_conversion']
            : [null, null];
    }

    /**
     * @return array{code: string, message: string, count: int}
     */
    private function blocker(string $code, string $message): array
    {
        return ['code' => $code, 'message' => $message, 'count' => 1];
    }

    private function blockerField(string $code): string
    {
        return match ($code) {
            'missing_conversion_factor', 'invalid_conversion_factor' => 'conversion_factor',
            'future_effective_at' => 'effective_at',
            default => 'new_base_uom_id',
        };
    }
}
