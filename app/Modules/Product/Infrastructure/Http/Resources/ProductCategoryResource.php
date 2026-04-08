<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class ProductCategoryResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'parent_id'   => $this->parent_id,
            'code'        => $this->code,
            'name'        => $this->name,
            'description' => $this->description,
            'image_path'  => $this->image_path,
            'sort_order'  => $this->sort_order,
            'is_active'   => $this->is_active,
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }
}
