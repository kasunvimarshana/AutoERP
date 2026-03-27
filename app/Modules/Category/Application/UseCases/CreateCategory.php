<?php

declare(strict_types=1);

namespace Modules\Category\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Category\Application\DTOs\CategoryData;
use Modules\Category\Domain\Entities\Category;
use Modules\Category\Domain\Events\CategoryCreated;
use Modules\Category\Domain\RepositoryInterfaces\CategoryRepositoryInterface;

class CreateCategory
{
    public function __construct(private readonly CategoryRepositoryInterface $categoryRepo) {}

    public function execute(CategoryData $data): Category
    {
        $slug = $data->slug ?: Str::slug($data->name);

        $depth = 0;
        $path = $slug;

        if ($data->parent_id !== null) {
            $parent = $this->categoryRepo->find($data->parent_id);
            if ($parent) {
                $depth = $parent->getDepth() + 1;
                $path = $parent->getPath().'/'.$slug;
            }
        }

        $category = new Category(
            tenantId: $data->tenant_id,
            name: $data->name,
            slug: $slug,
            description: $data->description,
            parentId: $data->parent_id,
            depth: $depth,
            path: $path,
            status: $data->status ?? 'active',
            attributes: $data->attributes,
            metadata: $data->metadata,
        );

        $saved = $this->categoryRepo->save($category);

        event(new CategoryCreated($saved));

        return $saved;
    }
}
