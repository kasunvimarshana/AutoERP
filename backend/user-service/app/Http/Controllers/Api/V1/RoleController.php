<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $roles = $this->roleService->list(
            $request->filled('per_page') ? (int) $request->input('per_page') : null,
        );

        return response()->json(JsonResource::collection($roles)->response()->getData(true));
    }

    public function show(string $name): JsonResponse
    {
        $role = $this->roleService->findByName($name);

        if (! $role) {
            return response()->json(['message' => 'Role not found.'], 404);
        }

        return response()->json(['data' => $role]);
    }
}
