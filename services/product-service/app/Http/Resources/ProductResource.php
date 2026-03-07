<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int         $id
 * @property string      $name
 * @property string|null $description
 * @property string      $sku
 * @property float       $price
 * @property string      $category
 * @property string      $status
 * @property int         $stock_quantity
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'description'    => $this->description,
            'sku'            => $this->sku,
            'price'          => (float) $this->price,
            'price_formatted'=> '$' . number_format((float) $this->price, 2),
            'category'       => $this->category,
            'status'         => $this->status,
            'is_active'      => $this->status === 'active',
            'stock_quantity' => (int) $this->stock_quantity,
            'in_stock'       => (int) $this->stock_quantity > 0,
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }

    public function with(Request $request): array
    {
        return [
            'meta' => [
                'service' => 'product-service',
                'version' => '1.0.0',
            ],
        ];
    }
}
