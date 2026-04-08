<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Request;
use Modules\Auth\Application\Contracts\PermissionServiceInterface;
use Modules\Auth\Infrastructure\Http\Resources\PermissionResource;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;

/**
 * @OA\Tag(name="Permissions", description="Permission read endpoints (system-managed)")
 */
final class PermissionController extends AuthorizedController
{
    public function __construct(private readonly PermissionServiceInterface $permissionService) {}

    /**
     * @OA\Get(
     *     path="/api/permissions",
     *     tags={"Permissions"},
     *     summary="List all permissions",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="module", in="query", description="Filter by module", @OA\Schema(type="string")),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated permission list")
     * )
     */
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', PermissionResource::class);

        $filters  = array_filter(['module' => $request->query('module')]);
        $perPage  = (int) $request->query('per_page', 50);

        $paginated = $this->permissionService->list($filters, $perPage);

        return PermissionResource::collection($paginated);
    }

    /**
     * @OA\Get(
     *     path="/api/permissions/{id}",
     *     tags={"Permissions"},
     *     summary="Get a permission by ID",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Permission details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $permission): JsonResponse
    {
        $this->authorize('view', PermissionResource::class);

        $record = $this->permissionService->find($permission);

        return (new PermissionResource($record))->response();
    }
}
