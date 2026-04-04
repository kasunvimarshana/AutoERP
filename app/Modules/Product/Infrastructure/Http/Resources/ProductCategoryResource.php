<?php
namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Domain\Entities\ProductCategory;

class ProductCategoryResource extends JsonResource
{
    public function __construct(private readonly ProductCategory $category)
    {
        parent::__construct($category);
    }

    public function toArray($request): array
    {
        return [
            'id'          => $this->category->id,
            'tenantId'    => $this->category->tenantId,
            'name'        => $this->category->name,
            'slug'        => $this->category->slug,
            'parentId'    => $this->category->parentId,
            'description' => $this->category->description,
            'isActive'    => $this->category->isActive,
        ];
    }
}
