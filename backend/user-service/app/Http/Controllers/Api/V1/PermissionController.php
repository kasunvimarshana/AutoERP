<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $permissions = $this->roleService->listPermissions(
            $request->filled('per_page') ? (int) $request->input('per_page') : null,
        );

        return response()->json(JsonResource::collection($permissions)->response()->getData(true));
    }
}
