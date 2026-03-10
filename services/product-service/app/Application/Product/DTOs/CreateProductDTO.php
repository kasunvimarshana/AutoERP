<?php

declare(strict_types=1);

namespace App\Application\Product\DTOs;

/**
 * CreateProductDTO
 */
final class CreateProductDTO
{
    public function __construct(
        public readonly string  $tenantId,
        public readonly string  $categoryId,
        public readonly string  $name,
        public readonly string  $code,
        public readonly string  $sku,
        public readonly float   $price,
        public readonly string  $currency   = 'USD',
        public readonly ?string $barcode    = null,
        public readonly ?string $description = null,
        public readonly float   $costPrice  = 0.0,
        public readonly ?string $unit       = null,
        public readonly ?float  $weight     = null,
        public readonly array   $dimensions = [],
        public readonly string  $status     = 'active',
        public readonly array   $attributes = [],
        public readonly array   $metadata   = [],
        public readonly ?string $imageUrl   = null,
        public readonly bool    $isTrackable = true,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            tenantId:    $data['tenant_id'],
            categoryId:  $data['category_id'],
            name:        $data['name'],
            code:        $data['code'],
            sku:         $data['sku'],
            price:       (float) $data['price'],
            currency:    $data['currency']     ?? 'USD',
            barcode:     $data['barcode']      ?? null,
            description: $data['description']  ?? null,
            costPrice:   (float) ($data['cost_price'] ?? 0),
            unit:        $data['unit']         ?? null,
            weight:      isset($data['weight']) ? (float) $data['weight'] : null,
            dimensions:  $data['dimensions']   ?? [],
            status:      $data['status']       ?? 'active',
            attributes:  $data['attributes']   ?? [],
            metadata:    $data['metadata']     ?? [],
            imageUrl:    $data['image_url']    ?? null,
            isTrackable: (bool) ($data['is_trackable'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id'   => $this->tenantId,
            'category_id' => $this->categoryId,
            'name'        => $this->name,
            'code'        => $this->code,
            'sku'         => $this->sku,
            'barcode'     => $this->barcode,
            'description' => $this->description,
            'price'       => $this->price,
            'cost_price'  => $this->costPrice,
            'currency'    => $this->currency,
            'unit'        => $this->unit,
            'weight'      => $this->weight,
            'dimensions'  => $this->dimensions,
            'status'      => $this->status,
            'attributes'  => $this->attributes,
            'metadata'    => $this->metadata,
            'image_url'   => $this->imageUrl,
            'is_trackable'=> $this->isTrackable,
        ];
    }
}
