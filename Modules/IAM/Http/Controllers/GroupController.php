<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\IAM\Http\Requests\StoreGroupRequest;
use Modules\IAM\Http\Requests\UpdateGroupRequest;
use Modules\IAM\Http\Resources\GroupResource;
use Modules\IAM\Services\GroupService;

/**
 * Group Controller
 *
 * Handles HTTP requests for group/team management.
 */
class GroupController extends Controller
{
    /**
     * GroupController constructor.
     */
    public function __construct(
        protected GroupService $groupService
    ) {}

    /**
     * Display a listing of groups.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $groups = $this->groupService->getPaginated($perPage);

        return GroupResource::collection($groups)
            ->response();
    }

    /**
     * Get all active groups.
     */
    public function active(): JsonResponse
    {
        $groups = $this->groupService->getAllActive();

        return GroupResource::collection($groups)
            ->response();
    }

    /**
     * Get group hierarchy tree.
     */
    public function tree(): JsonResponse
    {
        $groups = $this->groupService->getGroupTree();

        return GroupResource::collection($groups)
            ->response();
    }

    /**
     * Get root groups (no parent).
     */
    public function roots(): JsonResponse
    {
        $groups = $this->groupService->getRootGroups();

        return GroupResource::collection($groups)
            ->response();
    }

    /**
     * Store a newly created group.
     */
    public function store(StoreGroupRequest $request): JsonResponse
    {
        try {
            $group = $this->groupService->create($request->validated());

            return (new GroupResource($group))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create group',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified group.
     */
    public function show(int $id): JsonResponse
    {
        $group = $this->groupService->findById($id);

        if (!$group) {
            return response()->json([
                'message' => 'Group not found',
            ], 404);
        }

        return (new GroupResource($group->load(['users', 'roles', 'parent', 'children'])))
            ->response();
    }

    /**
     * Update the specified group.
     */
    public function update(UpdateGroupRequest $request, int $id): JsonResponse
    {
        try {
            $group = $this->groupService->update($id, $request->validated());

            return (new GroupResource($group))
                ->response();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update group',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified group.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->groupService->delete($id);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete group',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Add a user to the group.
     */
    public function addUser(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $group = $this->groupService->addUser($id, $validated['user_id']);

            return (new GroupResource($group->load('users')))
                ->response();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add user to group',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove a user from the group.
     */
    public function removeUser(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $group = $this->groupService->removeUser($id, $validated['user_id']);

            return (new GroupResource($group->load('users')))
                ->response();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove user from group',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Assign a role to the group.
     */
    public function assignRole(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        try {
            $group = $this->groupService->assignRole($id, $validated['role_id']);

            return (new GroupResource($group->load('roles')))
                ->response();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to assign role to group',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove a role from the group.
     */
    public function removeRole(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        try {
            $group = $this->groupService->removeRole($id, $validated['role_id']);

            return (new GroupResource($group->load('roles')))
                ->response();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove role from group',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
