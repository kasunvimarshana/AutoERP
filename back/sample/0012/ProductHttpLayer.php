<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Product\Domain\ValueObjects\ProductStatus;
use Modules\Product\Domain\ValueObjects\ProductType;
use Modules\Product\Domain\ValueObjects\UnitOfMeasure;

/**
 * StoreProductRequest — confirmed from KVAutoERP PR #37 diff
 */
final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'sku'         => 'required|string|max:100',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'currency'    => 'nullable|string|size:3',
            'category'    => 'nullable|string|max:100',
            'status'      => 'nullable|string|in:' . implode(',', ProductStatus::VALID),
            'type'        => 'nullable|string|in:' . implode(',', ProductType::VALID_TYPES),

            'units_of_measure'                     => 'nullable|array',
            'units_of_measure.*.unit'              => 'required_with:units_of_measure|string|max:50',
            'units_of_measure.*.type'              => 'required_with:units_of_measure|string|in:' . implode(',', UnitOfMeasure::VALID_TYPES),
            'units_of_measure.*.conversion_factor' => 'nullable|numeric|min:0.0001',

            'track_batches'  => 'nullable|boolean',
            'track_lots'     => 'nullable|boolean',
            'track_serials'  => 'nullable|boolean',
            'track_expiry'   => 'nullable|boolean',

            'reorder_point'  => 'nullable|numeric|min:0',
            'safety_stock'   => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'standard_cost'  => 'nullable|numeric|min:0',

            'download_url'          => 'nullable|url|max:2048',
            'download_limit'        => 'nullable|integer|min:0',
            'download_expiry_days'  => 'nullable|integer|min:0',
            'license_type'          => 'nullable|string|max:100',

            'subscription_interval'       => 'nullable|string|in:daily,weekly,monthly,yearly',
            'subscription_interval_count' => 'nullable|integer|min:1',

            'attributes' => 'nullable|array',
            'metadata'   => 'nullable|array',
        ];
    }

    /** @OA\Schema(schema="StoreProductRequest", required={"sku","name","price"}) */
    public function bodyParameters(): array
    {
        return [
            'sku'                  => ['description' => 'Unique SKU, alphanumeric + - _ .'],
            'type'                 => ['description' => 'physical|service|digital|subscription|combo|variable|raw_material|finished_good|wip|kit'],
            'units_of_measure'     => ['description' => 'Array of {unit, type, conversion_factor} — confirmed from KVAutoERP PR #37'],
        ];
    }
}

/**
 * UpdateProductRequest — confirmed from KVAutoERP PR #37 diff
 */
final class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'currency'    => 'nullable|string|size:3',
            'category'    => 'nullable|string|max:100',
            'status'      => 'nullable|string|in:' . implode(',', ProductStatus::VALID),
            'type'        => 'nullable|string|in:' . implode(',', ProductType::VALID_TYPES),

            'units_of_measure'                     => 'nullable|array',
            'units_of_measure.*.unit'              => 'required_with:units_of_measure|string|max:50',
            'units_of_measure.*.type'              => 'required_with:units_of_measure|string|in:' . implode(',', UnitOfMeasure::VALID_TYPES),
            'units_of_measure.*.conversion_factor' => 'nullable|numeric|min:0.0001',

            'track_batches'  => 'nullable|boolean',
            'track_lots'     => 'nullable|boolean',
            'track_serials'  => 'nullable|boolean',
            'track_expiry'   => 'nullable|boolean',

            'reorder_point'  => 'nullable|numeric|min:0',
            'safety_stock'   => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'standard_cost'  => 'nullable|numeric|min:0',

            'attributes' => 'nullable|array',
            'metadata'   => 'nullable|array',
        ];
    }
}


namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Domain\Entities\Product;

/**
 * ProductResource — confirmed from KVAutoERP PR #37 diff
 * Transforms domain entity to API JSON response.
 */
final class ProductResource extends JsonResource
{
    /** @param Product $resource */
    public function toArray($request): array
    {
        $product = $this->resource instanceof Product
            ? $this->resource
            : $this->resource;

        if ($product instanceof Product) {
            return [
                'id'          => $product->getId(),
                'tenant_id'   => $product->getTenantId()->value(),
                'sku'         => (string) $product->getSku(),
                'name'        => $product->getName(),
                'type'        => (string) $product->getType(),
                'status'      => (string) $product->getStatus(),
                'description' => $product->getDescription(),
                'category'    => $product->getCategory(),
                'price'       => [
                    'amount'   => $product->getPrice()->amount(),
                    'currency' => $product->getPrice()->currency(),
                ],
                'units_of_measure' => array_map(
                    fn ($u) => $u->toArray(),
                    $product->getUnitsOfMeasure()
                ),
                'tracking' => [
                    'batches' => $product->isTrackBatches(),
                    'lots'    => $product->isTrackLots(),
                    'serials' => $product->isTrackSerials(),
                    'expiry'  => $product->isTrackExpiry(),
                ],
                'reorder' => [
                    'point'        => $product->getReorderPoint(),
                    'safety_stock' => $product->getSafetyStock(),
                    'lead_time_days' => $product->getLeadTimeDays(),
                ],
                'standard_cost'           => $product->getStandardCost(),
                'is_stockable'            => $product->isStockable(),
                'is_active'               => $product->isActive(),
                'attributes'              => $product->getAttributes(),
                'metadata'                => $product->getMetadata(),
                'created_at'              => $product->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'updated_at'              => $product->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        // Fallback for Eloquent model pagination
        return parent::toArray($request);
    }
}
