<?php

namespace App\Services;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizationService
{
    public function create(array $data): Organization
    {
        return DB::transaction(function () use ($data) {
            $data['slug'] ??= Str::slug($data['name']);
            $data['status'] ??= OrganizationStatus::Active;

            $org = Organization::create($data);
            $this->rebuildTree($org->tenant_id);

            return $org->fresh();
        });
    }

    public function update(string $id, array $data): Organization
    {
        return DB::transaction(function () use ($id, $data) {
            $org = Organization::findOrFail($id);
            $parentChanged = array_key_exists('parent_id', $data) && $data['parent_id'] !== $org->parent_id;
            $org->update($data);

            if ($parentChanged) {
                $this->rebuildTree($org->tenant_id);
            }

            return $org->fresh();
        });
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id) {
            $org = Organization::findOrFail($id);
            $org->delete();
        });
    }

    public function paginate(string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return Organization::where('tenant_id', $tenantId)
            ->with('parent')
            ->orderBy('lft')
            ->paginate($perPage);
    }

    public function tree(string $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return Organization::where('tenant_id', $tenantId)
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('lft')
            ->get();
    }

    private function rebuildTree(string $tenantId): void
    {
        $orgs = Organization::where('tenant_id', $tenantId)
            ->orderBy('created_at')
            ->get()
            ->keyBy('id');

        $counter = 0;
        $this->calculateNodes($orgs, null, $counter);
    }

    private function calculateNodes(\Illuminate\Database\Eloquent\Collection $orgs, ?string $parentId, int &$counter): void
    {
        foreach ($orgs->where('parent_id', $parentId) as $org) {
            $lft = ++$counter;
            $this->calculateNodes($orgs, $org->id, $counter);
            $rgt = ++$counter;
            $depth = $parentId ? ($orgs[$parentId]->depth ?? 0) + 1 : 0;

            Organization::where('id', $org->id)->update([
                'lft' => $lft,
                'rgt' => $rgt,
                'depth' => $depth,
            ]);
        }
    }
}
