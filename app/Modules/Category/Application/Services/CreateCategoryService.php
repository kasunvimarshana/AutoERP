<?php

declare(strict_types=1);

namespace Modules\Category\Application\Services;

use Illuminate\Support\Str;
use Modules\Category\Application\Contracts\CreateCategoryServiceInterface;
use Modules\Category\Application\DTOs\CategoryData;
use Modules\Category\Domain\Entities\Category;
use Modules\Category\Domain\Events\CategoryCreated;
use Modules\Category\Domain\RepositoryInterfaces\CategoryRepositoryInterface;
use Modules\Core\Application\Services\BaseService;

class CreateCategoryService extends BaseService implements CreateCategoryServiceInterface
{
    public function __construct(private readonly CategoryRepositoryInterface $categoryRepository)
    {
        parent::__construct($categoryRepository);
    }

    protected function handle(array $data): Category
    {
        $dto = CategoryData::fromArray($data);

        $slug = $dto->slug ?: Str::slug($dto->name);

        [$depth, $path] = $this->resolveDepthAndPath($dto->parent_id, $slug);

        $category = new Category(
            tenantId: $dto->tenant_id,
            name: $dto->name,
            slug: $slug,
            description: $dto->description,
            parentId: $dto->parent_id,
            depth: $depth,
            path: $path,
            status: $dto->status ?? 'active',
            attributes: $dto->attributes,
            metadata: $dto->metadata,
        );

        $saved = $this->categoryRepository->save($category);

        $this->addEvent(new CategoryCreated($saved));

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
