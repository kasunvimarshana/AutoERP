<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Application\Contracts\PermissionServiceInterface;
use Modules\Auth\Application\DTOs\PermissionData;
use Modules\Auth\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\Core\Domain\Exceptions\NotFoundException;

final class PermissionService implements PermissionServiceInterface
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function create(PermissionData $dto): mixed
    {
        $dto->validate($dto->toArray());

        return DB::transaction(fn () => $this->permissionRepository->create([
            'name'        => $dto->name,
            'slug'        => $dto->slug,
            'description' => $dto->description,
            'module'      => $dto->module,
            'guard_name'  => $dto->guard_name,
            'metadata'    => $dto->metadata,
        ]));
    }

    /**
     * {@inheritdoc}
     */
    public function find(int $id): mixed
    {
        $permission = $this->permissionRepository->find($id);

        if (! $permission) {
            throw new NotFoundException('Permission', $id);
        }

        return $permission;
    }

    /**
     * {@inheritdoc}
     */
    public function findBySlug(string $slug): mixed
    {
        $permission = $this->permissionRepository->findBySlug($slug);

        if (! $permission) {
            throw new NotFoundException('Permission', $slug);
        }

        return $permission;
    }

    /**
     * {@inheritdoc}
     */
    public function list(array $filters = [], ?int $perPage = null): mixed
    {
        $perPage = $perPage ?? config('auth_module.pagination.per_page', 15);
        $repo    = clone $this->permissionRepository;

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
        $permission = $this->permissionRepository->find($id);

        if (! $permission) {
            throw new NotFoundException('Permission', $id);
        }

        return DB::transaction(fn () => $this->permissionRepository->update($id, $data));
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        $permission = $this->permissionRepository->find($id);

        if (! $permission) {
            throw new NotFoundException('Permission', $id);
        }

        return DB::transaction(fn () => $this->permissionRepository->delete($id));
    }
}
