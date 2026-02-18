<?php

namespace App\Repositories;

use App\Models\MetadataMenu;
use Illuminate\Database\Eloquent\Collection;

class MetadataMenuRepository extends BaseRepository
{
    protected function model(): string
    {
        return MetadataMenu::class;
    }

    /**
     * Get menu tree structure for user.
     */
    public function getMenuTreeForUser($user): Collection
    {
        return $this->model
            ->active()
            ->root()
            ->with(['children' => function ($query) {
                $query->where('is_active', true)->orderBy('order');
            }])
            ->get()
            ->filter(function ($menu) use ($user) {
                // Filter by permissions
                if ($menu->permission && ! $user->hasPermission($menu->permission)) {
                    return false;
                }

                // Filter children by permissions
                $menu->children = $menu->children->filter(function ($child) use ($user) {
                    return ! $child->permission || $user->hasPermission($child->permission);
                });

                return true;
            });
    }

    /**
     * Get all root menus.
     */
    public function getRootMenus(): Collection
    {
        return $this->model
            ->active()
            ->root()
            ->get();
    }

    /**
     * Reorder menus.
     */
    public function reorder(array $items): void
    {
        foreach ($items as $index => $item) {
            $this->model
                ->where('id', $item['id'])
                ->update(['order' => $index]);
        }
    }
}
