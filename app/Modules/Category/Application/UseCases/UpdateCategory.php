<?php

declare(strict_types=1);

namespace Modules\Category\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Category\Application\DTOs\CategoryData;
use Modules\Category\Domain\Entities\Category;
use Modules\Category\Domain\Events\CategoryUpdated;
use Modules\Category\Domain\Exceptions\CategoryNotFoundException;
use Modules\Category\Domain\RepositoryInterfaces\CategoryRepositoryInterface;

class UpdateCategory
{
    public function __construct(private readonly CategoryRepositoryInterface $categoryRepo) {}

    public function execute(int $id, CategoryData $data): Category
    {
        $category = $this->categoryRepo->find($id);
        if (! $category) {
            throw new CategoryNotFoundException($id);
        }

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

        $category->updateDetails(
            name: $data->name,
            slug: $slug,
            description: $data->description,
            parentId: $data->parent_id,
            path: $path,
            depth: $depth,
            attributes: $data->attributes,
            metadata: $data->metadata,
        );

        if (isset($data->status)) {
            if ($data->status === 'active') {
                $category->activate();
            } elseif ($data->status === 'inactive') {
                $category->deactivate();
            }
        }

        $saved = $this->categoryRepo->save($category);

        event(new CategoryUpdated($saved));

        return $saved;
    }
}
