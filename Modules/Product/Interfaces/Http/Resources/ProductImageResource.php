<?php

declare(strict_types=1);

namespace Modules\Product\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Modules\Product\Domain\Entities\ProductImage;

class ProductImageResource extends JsonResource
{
    /** @var ProductImage */
    public $resource;

    public function toArray(Request $request): array
    {
        $requiresSignedUrl = $this->resource->imageSourceType === 'upload';

        return [
            'id' => $this->resource->id,
            'product_id' => $this->resource->productId,
            'tenant_id' => $this->resource->tenantId,
            'image_path' => $this->resource->imagePath,
            'image_source_type' => $this->resource->imageSourceType,
            'url' => $requiresSignedUrl
                ? $this->resolveTemporaryUrl($this->resource->imagePath)
                : $this->resource->imagePath,
            'alt_text' => $this->resource->altText,
            'sort_order' => $this->resource->sortOrder,
            'is_primary' => $this->resource->isPrimary,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }

    private function resolveTemporaryUrl(string $path): ?string
    {
        try {
            return Storage::disk('local')->temporaryUrl($path, now()->addMinutes(30));
        } catch (\RuntimeException) {
            return null;
        }
    }
}
