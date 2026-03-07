<?php

namespace App\Modules\Product\DTOs;

class ProductDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $sku,
        public readonly string $name,
        public readonly ?string $description,
        public readonly float $price,
    ) {
    }

    /**
     * Create a DTO from an array (e.g., from a formal HTTP Request).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            sku: $data['sku'],
            name: $data['name'],
            description: $data['description'] ?? null,
            price: (float) $data['price']
        );
    }

    /**
     * Convert the DTO to an array for database insertion or events.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
        ];
    }
}
