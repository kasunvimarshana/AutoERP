<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Tenant\Application\Contracts\TenantServiceInterface;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Events\TenantCreated;
use Modules\Tenant\Domain\Events\TenantUpdated;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

class TenantService implements TenantServiceInterface
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {}

    public function createTenant(array $data): Tenant
    {
        return DB::transaction(function () use ($data): Tenant {
            $now = now();
            $tenant = new Tenant(
                id: (string) Str::uuid(),
                name: $data['name'],
                domain: $data['domain'],
                slug: $data['slug'],
                status: $data['status'] ?? 'active',
                plan: $data['plan'] ?? 'free',
                settings: $data['settings'] ?? [],
                metadata: $data['metadata'] ?? [],
                createdAt: $now,
                updatedAt: $now,
            );

            $saved = $this->tenantRepository->save($tenant);

            Event::dispatch(new TenantCreated($saved));

            return $saved;
        });
    }

    public function updateTenant(string $id, array $data): Tenant
    {
        return DB::transaction(function () use ($id, $data): Tenant {
            $existing = $this->tenantRepository->findById($id);

            if ($existing === null) {
                throw new NotFoundException("Tenant with id [{$id}] not found.");
            }

            $updated = new Tenant(
                id: $existing->id,
                name: $data['name'] ?? $existing->name,
                domain: $data['domain'] ?? $existing->domain,
                slug: $data['slug'] ?? $existing->slug,
                status: $data['status'] ?? $existing->status,
                plan: $data['plan'] ?? $existing->plan,
                settings: $data['settings'] ?? $existing->settings,
                metadata: $data['metadata'] ?? $existing->metadata,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $saved = $this->tenantRepository->save($updated);

            Event::dispatch(new TenantUpdated($saved));

            return $saved;
        });
    }

    public function deleteTenant(string $id): void
    {
        DB::transaction(function () use ($id): void {
            if ($this->tenantRepository->findById($id) === null) {
                throw new NotFoundException("Tenant with id [{$id}] not found.");
            }

            $this->tenantRepository->delete($id);
        });
    }

    public function getTenant(string $id): Tenant
    {
        $tenant = $this->tenantRepository->findById($id);

        if ($tenant === null) {
            throw new NotFoundException("Tenant with id [{$id}] not found.");
        }

        return $tenant;
    }

    public function getAllTenants(): array
    {
        return $this->tenantRepository->findAll();
    }

    public function getTenantByDomain(string $domain): ?Tenant
    {
        return $this->tenantRepository->findByDomain($domain);
    }
}
