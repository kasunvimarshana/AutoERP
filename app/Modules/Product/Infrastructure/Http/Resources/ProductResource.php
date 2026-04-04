<?php
namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Domain\Entities\Product;

class ProductResource extends JsonResource
{
    public function __construct(private readonly Product $product)
    {
        parent::__construct($product);
    }

    public function toArray($request): array
    {
        return [
            'id'             => $this->product->id,
            'tenantId'       => $this->product->tenantId,
            'sku'            => $this->product->sku,
            'name'           => $this->product->name,
            'type'           => $this->product->type,
            'status'         => $this->product->status,
            'categoryId'     => $this->product->categoryId,
            'description'    => $this->product->description,
            'barcode'        => $this->product->barcode,
            'basePrice'      => $this->product->basePrice,
            'costPrice'      => $this->product->costPrice,
            'baseUomId'      => $this->product->baseUomId,
            'trackInventory' => $this->product->trackInventory,
            'trackBatch'     => $this->product->trackBatch,
            'trackSerial'    => $this->product->trackSerial,
            'trackLot'       => $this->product->trackLot,
            'attributes'     => $this->product->attributes,
        ];
    }
}
