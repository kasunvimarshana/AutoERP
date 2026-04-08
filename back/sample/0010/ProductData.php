<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;
use Modules\Product\Domain\ValueObjects\ProductStatus;
use Modules\Product\Domain\ValueObjects\ProductType;

/**
 * ProductData DTO
 *
 * Confirmed from KVAutoERP PR #37 diff — exact field names and validation rules.
 * Extended with all tracking, digital, subscription, and reorder fields.
 */
final class ProductData extends BaseDto
{
    public int     $tenant_id;
    public string  $sku;
    public string  $name;
    public ?string $description   = null;
    public float   $price;
    public string  $currency      = 'USD';
    public ?string $category      = null;
    public string  $status        = ProductStatus::ACTIVE;
    public string  $type          = ProductType::PHYSICAL;

    // UoM array — confirmed from PR #37
    // [{'unit':'box','type':'buying','conversion_factor':12}, …]
    public ?array  $units_of_measure = null;

    // Tracking flags
    public bool    $track_batches  = false;
    public bool    $track_lots     = false;
    public bool    $track_serials  = false;
    public bool    $track_expiry   = false;

    // Reorder
    public ?float  $reorder_point  = null;
    public ?float  $safety_stock   = null;
    public ?int    $lead_time_days = null;
    public ?float  $standard_cost  = null;

    // Digital
    public ?string $download_url         = null;
    public ?int    $download_limit       = null;
    public ?int    $download_expiry_days = null;
    public ?string $license_type         = null;

    // Subscription
    public ?string $subscription_interval       = null;
    public ?int    $subscription_interval_count = null;

    // Flexible bags — confirmed from PR #37
    public ?array  $attributes = null;
    public ?array  $metadata   = null;

    public function __construct()
    {
        $this->type     = ProductType::PHYSICAL;
        $this->status   = ProductStatus::ACTIVE;
        $this->currency = 'USD';
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            // Confirmed from KVAutoERP PR #37 ProductData diff:
            'tenant_id'   => 'required|integer|exists:tenants,id',
            'sku'         => 'required|string|max:100',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'currency'    => 'nullable|string|size:3',
            'category'    => 'nullable|string|max:100',
            'status'      => 'nullable|string|in:' . implode(',', ProductStatus::VALID),
            'type'        => 'nullable|string|in:' . implode(',', ProductType::VALID_TYPES),

            // UoM — confirmed from PR #37 extended validation rules:
            'units_of_measure'                      => 'nullable|array',
            'units_of_measure.*.unit'               => 'required_with:units_of_measure|string|max:50',
            'units_of_measure.*.type'               => 'required_with:units_of_measure|string|in:buying,selling,inventory,production,shipping',
            'units_of_measure.*.conversion_factor'  => 'nullable|numeric|min:0.0001',

            // Tracking
            'track_batches'  => 'nullable|boolean',
            'track_lots'     => 'nullable|boolean',
            'track_serials'  => 'nullable|boolean',
            'track_expiry'   => 'nullable|boolean',

            // Reorder
            'reorder_point'  => 'nullable|numeric|min:0',
            'safety_stock'   => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'standard_cost'  => 'nullable|numeric|min:0',

            // Digital
            'download_url'          => 'nullable|url|max:2048',
            'download_limit'        => 'nullable|integer|min:0',
            'download_expiry_days'  => 'nullable|integer|min:0',
            'license_type'          => 'nullable|string|max:100',

            // Subscription
            'subscription_interval'       => 'nullable|string|in:daily,weekly,monthly,yearly',
            'subscription_interval_count' => 'nullable|integer|min:1',

            // Flexible bags
            'attributes' => 'nullable|array',
            'metadata'   => 'nullable|array',
        ];
    }
}


namespace Modules\Product\Application\ServiceInterfaces;

use Modules\Product\Domain\Entities\Product;

/**
 * Service interfaces — confirmed pattern from KVAutoERP PR #37.
 * Application services implement these; Infrastructure binds them via DI.
 */
interface CreateProductServiceInterface
{
    public function execute(array $data): Product;
}

interface UpdateProductServiceInterface
{
    public function execute(array $data): Product;
}

interface DeleteProductServiceInterface
{
    public function execute(array $data): void;
}

interface GetProductServiceInterface
{
    public function execute(array $data): Product;
}

interface ListProductsServiceInterface
{
    public function execute(array $data): mixed;
}
