<?php

declare(strict_types=1);

namespace App\Application\Product\DTOs;

/**
 * UpdateProductDTO — only non-null fields are applied to the update.
 */
final class UpdateProductDTO
{
    public function __construct(
        public readonly string  $tenantId,
        public readonly ?string $categoryId   = null,
        public readonly ?string $name         = null,
        public readonly ?string $code         = null,
        public readonly ?string $sku          = null,
        public readonly ?float  $price        = null,
        public readonly ?string $currency     = null,
        public readonly ?string $barcode      = null,
        public readonly ?string $description  = null,
        public readonly ?float  $costPrice    = null,
        public readonly ?string $unit         = null,
        public readonly ?float  $weight       = null,
        public readonly ?array  $dimensions   = null,
        public readonly ?string $status       = null,
        public readonly ?array  $attributes   = null,
        public readonly ?array  $metadata     = null,
        public readonly ?string $imageUrl     = null,
        public readonly ?bool   $isTrackable  = null,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            tenantId:    $data['tenant_id'],
            categoryId:  $data['category_id']  ?? null,
            name:        $data['name']          ?? null,
            code:        $data['code']          ?? null,
            sku:         $data['sku']           ?? null,
            price:       isset($data['price']) ? (float) $data['price'] : null,
            currency:    $data['currency']      ?? null,
            barcode:     $data['barcode']       ?? null,
            description: $data['description']   ?? null,
            costPrice:   isset($data['cost_price']) ? (float) $data['cost_price'] : null,
            unit:        $data['unit']          ?? null,
            weight:      isset($data['weight']) ? (float) $data['weight'] : null,
            dimensions:  $data['dimensions']    ?? null,
            status:      $data['status']        ?? null,
            attributes:  $data['attributes']    ?? null,
            metadata:    $data['metadata']      ?? null,
            imageUrl:    $data['image_url']     ?? null,
            isTrackable: isset($data['is_trackable']) ? (bool) $data['is_trackable'] : null,
        );
    }

    /**
     * Return only the non-null fields as an associative array.
     * This prevents accidentally nullifying fields that were not provided.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'category_id'  => $this->categoryId,
            'name'         => $this->name,
            'code'         => $this->code,
            'sku'          => $this->sku,
            'price'        => $this->price,
            'currency'     => $this->currency,
            'barcode'      => $this->barcode,
            'description'  => $this->description,
            'cost_price'   => $this->costPrice,
            'unit'         => $this->unit,
            'weight'       => $this->weight,
            'dimensions'   => $this->dimensions,
            'status'       => $this->status,
            'attributes'   => $this->attributes,
            'metadata'     => $this->metadata,
            'image_url'    => $this->imageUrl,
            'is_trackable' => $this->isTrackable,
        ], fn ($v) => $v !== null);
    }
}
