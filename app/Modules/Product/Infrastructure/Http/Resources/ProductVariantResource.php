<?php
namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Domain\Entities\ProductVariant;

class ProductVariantResource extends JsonResource
{
    public function __construct(private readonly ProductVariant $variant)
    {
        parent::__construct($variant);
    }

    public function toArray($request): array
    {
        return [
            'id'         => $this->variant->id,
            'productId'  => $this->variant->productId,
            'sku'        => $this->variant->sku,
            'name'       => $this->variant->name,
            'basePrice'  => $this->variant->basePrice,
            'costPrice'  => $this->variant->costPrice,
            'barcode'    => $this->variant->barcode,
            'attributes' => $this->variant->attributes,
            'isActive'   => $this->variant->isActive,
        ];
    }
}
