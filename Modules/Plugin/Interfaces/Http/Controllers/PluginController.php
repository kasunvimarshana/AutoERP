<?php

declare(strict_types=1);

namespace Modules\Plugin\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Interfaces\Http\Resources\ApiResponse;
use Modules\Plugin\Application\DTOs\InstallPluginDTO;
use Modules\Plugin\Application\Services\PluginService;

/**
 * Plugin controller.
 *
 * Input validation and response formatting ONLY.
 * All business logic is delegated to PluginService.
 *
 * @OA\Tag(name="Plugin", description="Plugin marketplace: manifest registration and tenant enablement endpoints")
 */
class PluginController extends Controller
{
    public function __construct(private readonly PluginService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/plugins",
     *     tags={"Plugin"},
     *     summary="List all registered plugin manifests",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of plugin manifests",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(): JsonResponse
    {
        $plugins = $this->service->listPlugins();

        return ApiResponse::success($plugins, 'Plugins retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/plugins",
     *     tags={"Plugin"},
     *     summary="Install (register) a new plugin manifest",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","alias","version"},
     *             @OA\Property(property="name", type="string", example="My Plugin"),
     *             @OA\Property(property="alias", type="string", example="my-plugin"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="version", type="string", example="1.0.0"),
     *             @OA\Property(property="keywords", type="array", nullable=true, @OA\Items(type="string")),
     *             @OA\Property(property="requires", type="array", nullable=true, @OA\Items(type="string")),
     *             @OA\Property(property="manifest_data", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Plugin manifest installed"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=400, description="Dependency not found")
     * )
     */
    public function install(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'alias'         => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'version'       => ['required', 'string', 'max:50'],
            'keywords'      => ['nullable', 'array'],
            'keywords.*'    => ['string'],
            'requires'      => ['nullable', 'array'],
            'requires.*'    => ['string'],
            'manifest_data' => ['nullable', 'array'],
        ]);

        $dto    = InstallPluginDTO::fromArray($validated);
        $plugin = $this->service->installPlugin($dto);

        return ApiResponse::created($plugin, 'Plugin installed.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/plugins/{id}/enable",
     *     tags={"Plugin"},
     *     summary="Enable a plugin for the current tenant",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Plugin enabled for tenant"),
     *     @OA\Response(response=404, description="Plugin manifest not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function enable(int $id): JsonResponse
    {
        $tenantPlugin = $this->service->enableForTenant($id);

        return ApiResponse::success($tenantPlugin, 'Plugin enabled.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/plugins/{id}/disable",
     *     tags={"Plugin"},
     *     summary="Disable a plugin for the current tenant",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Plugin disabled for tenant"),
     *     @OA\Response(response=404, description="Tenant plugin record not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function disable(int $id): JsonResponse
    {
        $tenantPlugin = $this->service->disableForTenant($id);

        return ApiResponse::success($tenantPlugin, 'Plugin disabled.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/plugins/{id}",
     *     tags={"Plugin"},
     *     summary="Update a plugin manifest",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="version", type="string", example="1.1.0"),
     *             @OA\Property(property="keywords", type="array", nullable=true, @OA\Items(type="string")),
     *             @OA\Property(property="manifest_data", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Plugin manifest updated"),
     *     @OA\Response(response=404, description="Plugin manifest not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function updatePlugin(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'description'   => ['nullable', 'string'],
            'version'       => ['sometimes', 'string', 'max:50'],
            'keywords'      => ['nullable', 'array'],
            'keywords.*'    => ['string'],
            'manifest_data' => ['nullable', 'array'],
        ]);

        $plugin = $this->service->updatePlugin($id, $validated);

        return ApiResponse::success($plugin, 'Plugin updated.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/plugins/{id}",
     *     tags={"Plugin"},
     *     summary="Show a single plugin manifest",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Plugin manifest retrieved"),
     *     @OA\Response(response=404, description="Plugin manifest not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function showPlugin(int $id): JsonResponse
    {
        $plugin = $this->service->showPlugin($id);

        return ApiResponse::success($plugin, 'Plugin retrieved.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/plugins/{id}",
     *     tags={"Plugin"},
     *     summary="Uninstall a plugin manifest",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Plugin uninstalled"),
     *     @OA\Response(response=422, description="Plugin still enabled for one or more tenants"),
     *     @OA\Response(response=404, description="Plugin manifest not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function uninstallPlugin(int $id): JsonResponse
    {
        try {
            $this->service->uninstallPlugin($id);
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success(null, 'Plugin uninstalled.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/plugins/tenant/enabled",
     *     tags={"Plugin"},
     *     summary="List all plugins enabled for the current tenant",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of enabled tenant plugins"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listTenantPlugins(): JsonResponse
    {
        $tenantPlugins = $this->service->listTenantPlugins();

        return ApiResponse::success($tenantPlugins, 'Tenant plugins retrieved.');
    }
}
