<?php

namespace App\Services;

use App\DTOs\TenantDTO;
use App\Models\Tenant;
use App\Repositories\Interfaces\TenantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Read
    |--------------------------------------------------------------------------
    */

    public function getTenant(int $id): ?Tenant
    {
        return $this->tenantRepository->findById($id);
    }

    /**
     * @return LengthAwarePaginator<Tenant>
     */
    public function listTenants(
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortDir = 'desc',
        ?string $search = null
    ): LengthAwarePaginator {
        return $this->tenantRepository->paginate($perPage, $filters, $sortBy, $sortDir, $search);
    }

    /*
    |--------------------------------------------------------------------------
    | Write (ACID transactions)
    |--------------------------------------------------------------------------
    */

    public function createTenant(TenantDTO $dto): Tenant
    {
        return DB::transaction(function () use ($dto): Tenant {
            // Auto-generate slug if not provided
            $slug = $dto->slug ?: Str::slug($dto->name);
            $slug = $this->ensureUniqueSlug($slug);

            $dto = new TenantDTO(
                name:     $dto->name,
                slug:     $slug,
                domain:   $dto->domain,
                plan:     $dto->plan,
                status:   $dto->status,
                settings: $dto->settings,
                maxUsers: $dto->maxUsers,
                metadata: $dto->metadata,
            );

            $tenant = $this->tenantRepository->create($dto);

            Log::info('Tenant created', ['tenant_id' => $tenant->id, 'slug' => $tenant->slug]);

            return $tenant;
        });
    }

    public function updateTenant(int $id, TenantDTO $dto): Tenant
    {
        return DB::transaction(function () use ($id, $dto): Tenant {
            $existing = $this->tenantRepository->findById($id);

            if (! $existing) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Tenant {$id} not found.");
            }

            // Check slug collision
            if ($dto->slug && $dto->slug !== $existing->slug) {
                $slugTaken = $this->tenantRepository->findBySlug($dto->slug);
                if ($slugTaken && $slugTaken->id !== $id) {
                    throw new \DomainException("Slug '{$dto->slug}' is already taken.");
                }
            }

            $tenant = $this->tenantRepository->update($id, $dto);

            Log::info('Tenant updated', ['tenant_id' => $id]);

            return $tenant;
        });
    }

    public function deleteTenant(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $tenant = $this->tenantRepository->findById($id);

            if (! $tenant) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Tenant {$id} not found.");
            }

            $deleted = $this->tenantRepository->delete($id);

            Log::info('Tenant deleted', ['tenant_id' => $id]);

            return $deleted;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function ensureUniqueSlug(string $base): string
    {
        $slug  = $base;
        $count = 1;

        while ($this->tenantRepository->findBySlug($slug)) {
            $slug = "{$base}-{$count}";
            $count++;
        }

        return $slug;
    }
}
