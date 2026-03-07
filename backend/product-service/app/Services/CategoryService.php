<?php

namespace App\Services;

use App\DTOs\CategoryDTO;
use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
    ) {}

    public function list(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection
    {
        return $this->categoryRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): Category
    {
        return $this->categoryRepository->findById($id)
            ?? throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Category not found.');
    }

    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
            $dto = CategoryDTO::fromArray($data);

            return $this->categoryRepository->create($dto->toArray());
        });
    }

    public function update(int $id, array $data): Category
    {
        return DB::transaction(function () use ($id, $data) {
            $category = $this->findById($id);
            if (isset($data['name']) && ! isset($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            return $this->categoryRepository->update($category, $data);
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $category = $this->findById($id);
            $this->categoryRepository->delete($category);
        });
    }
}
