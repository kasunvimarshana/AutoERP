<?php

namespace App\Services;

use App\Models\MetadataMenu;
use App\Repositories\MetadataMenuRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class MetadataMenuService extends BaseService
{
    protected MetadataMenuRepository $repository;

    public function __construct(MetadataMenuRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get menu structure for authenticated user.
     */
    public function getMenuForUser($user): Collection
    {
        $cacheKey = "menu:user:{$user->id}:tenant:{$user->tenant_id}";

        return Cache::remember($cacheKey, 1800, function () use ($user) {
            return $this->repository->getMenuTreeForUser($user);
        });
    }

    /**
     * Get all menus (admin view).
     */
    public function getAllMenus(): Collection
    {
        return $this->repository->getRootMenus()->load('children');
    }

    /**
     * Create menu item.
     */
    public function createMenu(array $data): MetadataMenu
    {
        return $this->transaction(function () use ($data) {
            $menu = $this->repository->create($data);

            // Clear user menu caches
            $this->clearMenuCache();

            $this->logInfo("Menu created: {$menu->name}");

            return $menu;
        });
    }

    /**
     * Update menu item.
     */
    public function updateMenu(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $result = $this->repository->update($id, $data);

            // Clear user menu caches
            $this->clearMenuCache();

            $this->logInfo("Menu updated: ID {$id}");

            return $result;
        });
    }

    /**
     * Delete menu item.
     */
    public function deleteMenu(int $id): bool
    {
        $menu = $this->repository->find($id);

        if (! $menu || $menu->is_system) {
            return false;
        }

        return $this->transaction(function () use ($id) {
            $result = $this->repository->delete($id);

            // Clear user menu caches
            $this->clearMenuCache();

            $this->logInfo("Menu deleted: ID {$id}");

            return $result;
        });
    }

    /**
     * Reorder menu items.
     */
    public function reorderMenus(array $items): void
    {
        $this->transaction(function () use ($items) {
            $this->repository->reorder($items);
            $this->clearMenuCache();

            $this->logInfo('Menus reordered');
        });
    }

    /**
     * Clear menu cache for all users.
     */
    protected function clearMenuCache(): void
    {
        Cache::tags('menu')->flush();
    }
}
