<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Auth\Application\Contracts\RoleServiceInterface;
use Modules\Auth\Application\DTOs\RoleData;
use Modules\Auth\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\Core\Domain\Exceptions\NotFoundException;

final class RoleService implements RoleServiceInterface
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function create(RoleData $dto): mixed
    {
        $dto->validate($dto->toArray());

        return DB::transaction(function () use ($dto) {
            return $this->roleRepository->create([
                'name'        => $dto->name,
                'slug'        => $dto->slug ?: Str::slug($dto->name),
                'tenant_id'   => $dto->tenant_id,
                'description' => $dto->description,
                'is_system'   => $dto->is_system,
                'guard_name'  => $dto->guard_name,
                'metadata'    => $dto->metadata,
            ]);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function find(int $id): mixed
    {
        $role = $this->roleRepository->find($id);

        if (! $role) {
            throw new NotFoundException('Role', $id);
        }

        return $role;
    }

    /**
     * {@inheritdoc}
     */
    public function findBySlug(string $slug, ?int $tenantId = null): mixed
    {
        $role = $this->roleRepository->findBySlug($slug, $tenantId);

        if (! $role) {
            throw new NotFoundException('Role', $slug);
        }

        return $role;
    }

    /**
     * {@inheritdoc}
     */
    public function list(array $filters = [], ?int $perPage = null): mixed
    {
        $perPage = $perPage ?? config('auth_module.pagination.per_page', 15);
        $repo    = clone $this->roleRepository;

        foreach ($filters as $field => $value) {
            $repo->where($field, $value);
        }

        return $repo->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): mixed
    {
        $role = $this->roleRepository->find($id);

        if (! $role) {
            throw new NotFoundException('Role', $id);
        }

        return DB::transaction(function () use ($id, $data) {
            return $this->roleRepository->update($id, $data);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        $role = $this->roleRepository->find($id);

        if (! $role) {
            throw new NotFoundException('Role', $id);
        }

        if ($role->is_system) {
            throw new \RuntimeException('System roles cannot be deleted.');
        }

        return DB::transaction(fn () => $this->roleRepository->delete($id));
    }
}
