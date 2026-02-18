<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Services\MetadataMenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MenuController extends BaseController
{
    protected MetadataMenuService $menuService;

    public function __construct(MetadataMenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    /**
     * Get menu structure for current user.
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            $menu = $this->menuService->getMenuForUser($user);

            return $this->successResponse($menu, 'Menu retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve menu', $e->getMessage(), 500);
        }
    }

    /**
     * Get all menus (admin view).
     */
    public function all(): JsonResponse
    {
        try {
            $menus = $this->menuService->getAllMenus();

            return $this->successResponse($menus, 'All menus retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve menus', $e->getMessage(), 500);
        }
    }

    /**
     * Create menu item.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'parent_id' => 'nullable|exists:metadata_menus,id',
                'name' => 'required|string',
                'label' => 'required|string',
                'icon' => 'nullable|string',
                'route' => 'nullable|string',
                'url' => 'nullable|string',
                'entity_name' => 'nullable|string',
                'permission' => 'nullable|string',
                'order' => 'integer',
                'is_active' => 'boolean',
                'config' => 'nullable|array',
            ]);

            $menu = $this->menuService->createMenu($validated);

            return $this->successResponse($menu, 'Menu created successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create menu', $e->getMessage(), 500);
        }
    }

    /**
     * Update menu item.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'parent_id' => 'nullable|exists:metadata_menus,id',
                'label' => 'sometimes|string',
                'icon' => 'nullable|string',
                'route' => 'nullable|string',
                'url' => 'nullable|string',
                'entity_name' => 'nullable|string',
                'permission' => 'nullable|string',
                'order' => 'integer',
                'is_active' => 'boolean',
                'config' => 'nullable|array',
            ]);

            $result = $this->menuService->updateMenu($id, $validated);

            if (! $result) {
                return $this->errorResponse('Menu not found or cannot be updated', null, 404);
            }

            return $this->successResponse(null, 'Menu updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update menu', $e->getMessage(), 500);
        }
    }

    /**
     * Delete menu item.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->menuService->deleteMenu($id);

            if (! $result) {
                return $this->errorResponse('Menu not found or cannot be deleted', null, 404);
            }

            return $this->successResponse(null, 'Menu deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete menu', $e->getMessage(), 500);
        }
    }

    /**
     * Reorder menu items.
     */
    public function reorder(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'items' => 'required|array',
                'items.*.id' => 'required|exists:metadata_menus,id',
            ]);

            $this->menuService->reorderMenus($validated['items']);

            return $this->successResponse(null, 'Menus reordered successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reorder menus', $e->getMessage(), 500);
        }
    }
}
