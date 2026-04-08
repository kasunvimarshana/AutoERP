<?php

namespace App\Modules\UnitOfMeasure\Services;

use App\Modules\UnitOfMeasure\Models\UnitOfMeasure;
use App\Modules\UnitOfMeasure\Models\UomConversion;
use App\Modules\UnitOfMeasure\Models\ProductUomSetting;
use Illuminate\Support\Facades\Cache;

/**
 * UomConversionService
 *
 * Handles all Unit of Measure conversions across the WIMS.
 *
 * Key capabilities:
 *   - Convert between any two UOMs (direct or via base unit)
 *   - Per-product UOM overrides
 *   - Purchase / Sales / Inventory / Base UOM resolution
 *   - GS1 AI parsing (GTIN, batch, serial, expiry, weight)
 *   - Rounding modes: round | floor | ceil | none
 *   - Packaging hierarchy traversal (piece → box → pallet)
 */
class UomConversionService
{
    /**
     * Convert a quantity from one UOM to another.
     * Tries: direct conversion → via base unit → product-specific conversion.
     *
     * @throws \DomainException if no conversion path exists
     */
    public function convert(
        float  $qty,
        int    $fromUomId,
        int    $toUomId,
        ?int   $productId   = null,
        string $roundMode   = 'round',
        int    $precision   = 6
    ): float {
        if ($fromUomId === $toUomId) return round($qty, $precision);

        // Try direct / product-specific conversion
        $factor = $this->resolveConversionFactor($fromUomId, $toUomId, $productId);

        if ($factor === null) {
            throw new \DomainException(
                "No UOM conversion path found from UOM #{$fromUomId} to UOM #{$toUomId}."
            );
        }

        $converted = $qty * $factor;

        return $this->applyRounding($converted, $roundMode, $precision);
    }

    /**
     * Convert using a named UOM role (purchase_qty → inventory_qty, etc.)
     */
    public function convertToInventoryUom(float $qty, int $productId, int $fromUomId, ?int $variantId = null): float
    {
        $settings = $this->getProductUomSettings($productId, $variantId);
        if (! $settings) return $qty;

        $inventoryUomId = $settings->inventory_uom_id;

        if ($fromUomId === $inventoryUomId) return $qty;

        return $this->convert($qty, $fromUomId, $inventoryUomId, $productId);
    }

    public function convertFromPurchaseUom(float $qty, int $productId, ?int $variantId = null): float
    {
        $settings = $this->getProductUomSettings($productId, $variantId);
        if (! $settings) return $qty;

        return $this->convert($qty, $settings->purchase_uom_id, $settings->inventory_uom_id, $productId);
    }

    public function convertFromSalesUom(float $qty, int $productId, ?int $variantId = null): float
    {
        $settings = $this->getProductUomSettings($productId, $variantId);
        if (! $settings) return $qty;

        return $this->convert($qty, $settings->sales_uom_id, $settings->inventory_uom_id, $productId);
    }

    /**
     * Get UOM display info for API responses.
     */
    public function getUomInfo(int $uomId): ?array
    {
        $uom = UnitOfMeasure::find($uomId);
        if (! $uom) return null;

        return [
            'id'         => $uom->id,
            'code'       => $uom->code,
            'name'       => $uom->name,
            'symbol'     => $uom->symbol,
            'gs1_code'   => $uom->gs1_uom_code,
            'unece_code' => $uom->unece_code,
            'category'   => $uom->category?->name,
        ];
    }

    /**
     * Parse a GS1-128 / GS1 DataMatrix barcode string into structured data.
     * Supports common Application Identifiers (AI).
     *
     * Example: (01)04150555914780(17)210630(10)ABC123(21)XYZ789
     */
    public function parseGs1Barcode(string $barcode): array
    {
        $result = [
            'gtin'             => null,  // AI(01)
            'serial_number'    => null,  // AI(21)
            'batch_lot'        => null,  // AI(10)
            'expiry_date'      => null,  // AI(17)
            'manufacture_date' => null,  // AI(11)
            'best_before'      => null,  // AI(15)
            'net_weight_kg'    => null,  // AI(310x)
            'net_quantity'     => null,  // AI(37)
            'sscc'             => null,  // AI(00)
            'extra_ais'        => [],
        ];

        // Strip brackets format: (AI)value → parse
        if (preg_match_all('/\((\d{2,4})\)([^\(]+)/', $barcode, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $ai    = $m[1];
                $value = rtrim($m[2]);
                $this->mapAiToResult($ai, $value, $result);
            }
            return $result;
        }

        // Try plain concatenated format (GS1-128 without brackets)
        // Standard AI lengths per GS1 spec
        $this->parseConcatenatedGs1($barcode, $result);

        return $result;
    }

    protected function mapAiToResult(string $ai, string $value, array &$result): void
    {
        switch ($ai) {
            case '00': $result['sscc']             = $value; break;
            case '01': $result['gtin']             = $value; break;
            case '10': $result['batch_lot']        = $value; break;
            case '11': $result['manufacture_date'] = $this->parseGs1Date($value); break;
            case '15': $result['best_before']      = $this->parseGs1Date($value); break;
            case '17': $result['expiry_date']      = $this->parseGs1Date($value); break;
            case '21': $result['serial_number']    = $value; break;
            case '37': $result['net_quantity']     = (float) $value; break;
            default:
                if (str_starts_with($ai, '31')) {
                    // AI(310x) = net weight in kg (x decimal places)
                    $decimals = (int) substr($ai, 3, 1);
                    $result['net_weight_kg'] = (float) $value / pow(10, $decimals);
                } else {
                    $result['extra_ais'][$ai] = $value;
                }
        }
    }

    protected function parseGs1Date(string $yymmdd): ?\DateTime
    {
        if (strlen($yymmdd) !== 6) return null;
        try {
            $year  = (int) substr($yymmdd, 0, 2);
            $month = (int) substr($yymmdd, 2, 2);
            $day   = (int) substr($yymmdd, 4, 2);
            $year += ($year >= 0 && $year <= 49) ? 2000 : 1900;
            if ($day === 0) $day = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
            return new \DateTime("{$year}-{$month}-{$day}");
        } catch (\Exception) {
            return null;
        }
    }

    protected function parseConcatenatedGs1(string $barcode, array &$result): void
    {
        // Variable-length AI parsing (simplified subset)
        $pos = 0;
        $len = strlen($barcode);

        while ($pos < $len) {
            $ai2 = substr($barcode, $pos, 2);
            $ai3 = substr($barcode, $pos, 3);
            $ai4 = substr($barcode, $pos, 4);

            if ($ai2 === '00' && $pos + 20 <= $len) {
                $result['sscc'] = substr($barcode, $pos + 2, 18); $pos += 20;
            } elseif ($ai2 === '01' && $pos + 16 <= $len) {
                $result['gtin'] = substr($barcode, $pos + 2, 14); $pos += 16;
            } elseif ($ai2 === '17' && $pos + 8 <= $len) {
                $result['expiry_date'] = $this->parseGs1Date(substr($barcode, $pos + 2, 6)); $pos += 8;
            } elseif ($ai2 === '11' && $pos + 8 <= $len) {
                $result['manufacture_date'] = $this->parseGs1Date(substr($barcode, $pos + 2, 6)); $pos += 8;
            } else {
                break; // Cannot parse further without FNC1 separators
            }
        }
    }

    protected function resolveConversionFactor(int $fromUomId, int $toUomId, ?int $productId): ?float
    {
        $cacheKey = "uom_conversion:{$fromUomId}:{$toUomId}:" . ($productId ?? 'global');

        return Cache::remember($cacheKey, 600, function () use ($fromUomId, $toUomId, $productId) {
            // 1. Product-specific conversion
            if ($productId) {
                $specific = UomConversion::where('from_uom_id', $fromUomId)
                    ->where('to_uom_id', $toUomId)
                    ->where('product_id', $productId)
                    ->where('is_active', true)
                    ->first();

                if ($specific) return (float) $specific->factor;
            }

            // 2. Direct conversion
            $direct = UomConversion::where('from_uom_id', $fromUomId)
                ->where('to_uom_id', $toUomId)
                ->whereNull('product_id')
                ->where('is_active', true)
                ->first();

            if ($direct) return (float) $direct->factor;

            // 3. Reverse direction (if bidirectional)
            $reverse = UomConversion::where('from_uom_id', $toUomId)
                ->where('to_uom_id', $fromUomId)
                ->where('is_bidirectional', true)
                ->whereNull('product_id')
                ->where('is_active', true)
                ->first();

            if ($reverse) return 1.0 / (float) $reverse->factor;

            // 4. Via base unit (from → base → to)
            $fromUom = UnitOfMeasure::find($fromUomId);
            $toUom   = UnitOfMeasure::find($toUomId);

            if ($fromUom && $toUom && $fromUom->uom_category_id === $toUom->uom_category_id) {
                $fromFactor = (float) $fromUom->conversion_factor;  // relative to base
                $toFactor   = (float) $toUom->conversion_factor;
                if ($toFactor > 0) return $fromFactor / $toFactor;
            }

            return null;
        });
    }

    protected function getProductUomSettings(int $productId, ?int $variantId): ?ProductUomSetting
    {
        $cacheKey = "product_uom:{$productId}:" . ($variantId ?? 'base');

        return Cache::remember($cacheKey, 300, function () use ($productId, $variantId) {
            return ProductUomSetting::where('product_id', $productId)
                ->where(function ($q) use ($variantId) {
                    $q->where('variant_id', $variantId)->orWhereNull('variant_id');
                })
                ->orderByRaw('CASE WHEN variant_id IS NOT NULL THEN 0 ELSE 1 END')
                ->first();
        });
    }

    protected function applyRounding(float $value, string $mode, int $precision): float
    {
        return match ($mode) {
            'floor' => floor($value * pow(10, $precision)) / pow(10, $precision),
            'ceil'  => ceil($value * pow(10, $precision)) / pow(10, $precision),
            'none'  => $value,
            default => round($value, $precision),
        };
    }
}
