<?php

declare(strict_types=1);

namespace Modules\Category\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Category\Domain\Entities\Category;
use Modules\Category\Domain\Entities\CategoryImage;
use Modules\Category\Domain\RepositoryInterfaces\CategoryRepositoryInterface;
use Modules\Category\Infrastructure\Persistence\Eloquent\Models\CategoryImageModel;
use Modules\Category\Infrastructure\Persistence\Eloquent\Models\CategoryModel;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;

class EloquentCategoryRepository extends EloquentRepository implements CategoryRepositoryInterface
{
    public function __construct(CategoryModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (CategoryModel $model): Category => $this->mapModelToDomainEntity($model));
    }

    public function findBySlug(int $tenantId, string $slug): ?Category
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('slug', $slug)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByTenant(int $tenantId): Collection
    {
        return $this->toDomainCollection($this->model->where('tenant_id', $tenantId)->get());
    }

    public function findChildren(int $parentId): Collection
    {
        return $this->toDomainCollection(
            $this->model->where('parent_id', $parentId)->with('image')->get()
        );
    }

    public function findRoots(int $tenantId): Collection
    {
        return $this->toDomainCollection(
            $this->model->where('tenant_id', $tenantId)->whereNull('parent_id')->with('image')->get()
        );
    }

    public function getTree(int $tenantId, ?int $rootId = null): Collection
    {
        $query = $this->model->where('tenant_id', $tenantId)->with(['image', 'allChildren.image']);

        if ($rootId !== null) {
            $query->where('id', $rootId);
        } else {
            $query->whereNull('parent_id');
        }

        $models = $query->get();

        return $this->buildTree($models);
    }

    public function getDescendants(int $id): Collection
    {
        $model = $this->model->find($id);
        if (! $model) {
            return new Collection;
        }

        $descendants = collect();
        $this->collectDescendants($model, $descendants);

        return $descendants;
    }

    public function save(Category $category): Category
    {
        $data = [
            'tenant_id'   => $category->getTenantId(),
            'name'        => $category->getName(),
            'slug'        => $category->getSlug(),
            'description' => $category->getDescription(),
            'parent_id'   => $category->getParentId(),
            'depth'       => $category->getDepth(),
            'path'        => $category->getPath(),
            'status'      => $category->getStatus(),
            'attributes'  => $category->getAttributes(),
            'metadata'    => $category->getMetadata(),
        ];

        if ($category->getId()) {
            $model = $this->update($category->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var CategoryModel $model */
        $model->load('image');

        return $this->toDomainEntity($model);
    }

    public function find($id, array $columns = ['*']): ?Category
    {
        $this->with(['image']);

        return parent::find($id, $columns);
    }

    public function paginate(?int $perPage = null, array $columns = ['*'], ?string $pageName = null, ?int $page = null): LengthAwarePaginator
    {
        $this->with(['image']);

        return parent::paginate($perPage, $columns, $pageName, $page);
    }

    private function buildTree(\Illuminate\Database\Eloquent\Collection $models): Collection
    {
        return $models->map(fn (CategoryModel $model): Category => $this->mapModelToDomainEntityWithChildren($model));
    }

    private function mapModelToDomainEntityWithChildren(CategoryModel $model): Category
    {
        $category = $this->mapModelToDomainEntity($model);

        if ($model->relationLoaded('allChildren') && $model->allChildren->isNotEmpty()) {
            $children = $model->allChildren->map(fn (CategoryModel $child): Category => $this->mapModelToDomainEntityWithChildren($child));
            $category->setChildren(collect($children));
        }

        return $category;
    }

    private function collectDescendants(CategoryModel $model, \Illuminate\Support\Collection $collected): void
    {
        $children = $this->model->where('parent_id', $model->id)->with('image')->get();
        foreach ($children as $child) {
            $collected->push($this->mapModelToDomainEntity($child));
            $this->collectDescendants($child, $collected);
        }
    }

    private function mapModelToDomainEntity(CategoryModel $model): Category
    {
        $category = new Category(
            tenantId: $model->tenant_id,
            name: $model->name,
            slug: $model->slug,
            description: $model->description,
            parentId: $model->parent_id,
            depth: $model->depth ?? 0,
            path: $model->path ?? $model->slug,
            status: $model->status,
            attributes: $model->attributes,
            metadata: $model->metadata,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );

        if ($model->relationLoaded('image') && $model->image !== null) {
            $category->setImage($this->mapImageModelToDomainEntity($model->image));
        }

        return $category;
    }

    private function mapImageModelToDomainEntity(CategoryImageModel $model): CategoryImage
    {
        return new CategoryImage(
            tenantId: $model->tenant_id,
            categoryId: $model->category_id,
            uuid: $model->uuid,
            name: $model->name,
            filePath: $model->file_path,
            mimeType: $model->mime_type,
            size: $model->size,
            metadata: $model->metadata,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
