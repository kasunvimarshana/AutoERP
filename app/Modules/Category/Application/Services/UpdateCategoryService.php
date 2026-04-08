<?php

declare(strict_types=1);

namespace Modules\Category\Application\Services;

use Illuminate\Support\Str;
use Modules\Category\Application\Contracts\UpdateCategoryServiceInterface;
use Modules\Category\Application\DTOs\CategoryData;
use Modules\Category\Domain\Entities\Category;
use Modules\Category\Domain\Events\CategoryUpdated;
use Modules\Category\Domain\Exceptions\CategoryNotFoundException;
use Modules\Category\Domain\RepositoryInterfaces\CategoryRepositoryInterface;
use Modules\Core\Application\Services\BaseService;

class UpdateCategoryService extends BaseService implements UpdateCategoryServiceInterface
{
    public function __construct(private readonly CategoryRepositoryInterface $categoryRepository)
    {
        parent::__construct($categoryRepository);
    }

    protected function handle(array $data): Category
    {
        $id = $data['id'];
        $category = $this->categoryRepository->find($id);

        if (! $category) {
            throw new CategoryNotFoundException($id);
        }

        $dto = CategoryData::fromArray($data);
        $slug = $dto->slug ?: Str::slug($dto->name);

        [$depth, $path] = $this->resolveDepthAndPath($dto->parent_id, $slug);

        $category->updateDetails(
            name: $dto->name,
            slug: $slug,
            description: $dto->description,
            parentId: $dto->parent_id,
            path: $path,
            depth: $depth,
            attributes: $dto->attributes,
            metadata: $dto->metadata,
        );

        if (isset($dto->status)) {
            if ($dto->status === 'active') {
                $category->activate();
            } elseif ($dto->status === 'inactive') {
                $category->deactivate();
            }
        }

        $saved = $this->categoryRepository->save($category);

        $this->addEvent(new CategoryUpdated($saved));

        return $saved;
    }

    private function resolveDepthAndPath(?int $parentId, string $slug): array
    {
        if ($parentId === null) {
            return [0, $slug];
        }

        $parent = $this->categoryRepository->find($parentId);
        if ($parent) {
            $depth = $parent->getDepth() + 1;
            $path = $parent->getPath().'/'.$slug;

            return [$depth, $path];
        }

        return [0, $slug];
    }
}
