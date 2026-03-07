<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as PaginatorInstance;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class RoleService
{
    private array $roles = [
        'admin' => [
            'name' => 'admin',
            'description' => 'Full system access',
            'permissions' => ['users.*', 'roles.*', 'reports.*', 'settings.*'],
        ],
        'manager' => [
            'name' => 'manager',
            'description' => 'Department management access',
            'permissions' => ['users.read', 'users.create', 'users.update', 'reports.*'],
        ],
        'staff' => [
            'name' => 'staff',
            'description' => 'Basic access',
            'permissions' => ['users.read.own'],
        ],
    ];

    private array $permissions = [
        ['name' => 'users.read', 'description' => 'Read any user'],
        ['name' => 'users.read.own', 'description' => 'Read own profile'],
        ['name' => 'users.create', 'description' => 'Create users'],
        ['name' => 'users.update', 'description' => 'Update users'],
        ['name' => 'users.delete', 'description' => 'Delete users'],
        ['name' => 'roles.*', 'description' => 'Full roles access'],
        ['name' => 'reports.*', 'description' => 'Full reports access'],
        ['name' => 'settings.*', 'description' => 'Full settings access'],
    ];

    public function all(): array
    {
        return array_values($this->roles);
    }

    public function findByName(string $name): ?array
    {
        return $this->roles[$name] ?? null;
    }

    public function allPermissions(): array
    {
        return $this->permissions;
    }

    public function list(?int $perPage = null): LengthAwarePaginator|Collection
    {
        return $this->paginateCollection(collect(array_values($this->roles)), $perPage);
    }

    public function listPermissions(?int $perPage = null): LengthAwarePaginator|Collection
    {
        return $this->paginateCollection(collect($this->permissions), $perPage);
    }

    private function paginateCollection(Collection $items, ?int $perPage): LengthAwarePaginator|Collection
    {
        if ($perPage === null) {
            return $items;
        }

        $page = Paginator::resolveCurrentPage();

        return new PaginatorInstance(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => request()->query()]
        );
    }
}
