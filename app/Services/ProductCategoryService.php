<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\ProductCategory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductCategoryService
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ProductCategory::where('tenant_id', $tenantId)
            ->with(['parent:id,name,slug']);

        if (isset($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id'] ?: null);
        }
        if (isset($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /** Return all root categories with nested children. */
    public function tree(string $tenantId): array
    {
        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        // Build plain-array map with children_list
        $map = [];
        foreach ($categories as $cat) {
            $map[$cat->id] = array_merge($cat->toArray(), ['children_list' => []]);
        }

        $roots = [];
        foreach ($categories as $cat) {
            if ($cat->parent_id && isset($map[$cat->parent_id])) {
                $map[$cat->parent_id]['children_list'][] = &$map[$cat->id];
            } else {
                $roots[] = &$map[$cat->id];
            }
        }

        return array_values($roots);
    }

    public function create(array $data): ProductCategory
    {
        return DB::transaction(function () use ($data) {
            if (empty($data['slug'])) {
                $data['slug'] = $this->uniqueSlug($data['tenant_id'], $data['name']);
            }

            $category = ProductCategory::create($data);

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: ProductCategory::class,
                auditableId: $category->id,
                newValues: $data
            );

            return $category;
        });
    }

    public function update(string $id, array $data): ProductCategory
    {
        return DB::transaction(function () use ($id, $data) {
            $category = ProductCategory::findOrFail($id);
            $old = $category->toArray();
            $category->update($data);

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: ProductCategory::class,
                auditableId: $category->id,
                oldValues: $old,
                newValues: $data
            );

            return $category->fresh();
        });
    }

    public function delete(string $id): void
    {
        $category = ProductCategory::findOrFail($id);
        // Re-parent children to null so they become roots
        ProductCategory::where('parent_id', $id)->update(['parent_id' => null]);
        $category->delete();
    }

    private function uniqueSlug(string $tenantId, string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (ProductCategory::where('tenant_id', $tenantId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
