<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Core\Services\ConfigurationService;

class ConfigurationController extends BaseController
{
    protected ConfigurationService $configService;

    public function __construct(ConfigurationService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * @OA\Get(
     *     path="/api/configuration",
     *     operationId="getAllConfigurations",
     *     tags={"Configuration"},
     *     summary="Get all configuration settings",
     *     description="Retrieve all configuration key-value pairs for the current tenant. Requires authentication and tenant context.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Configuration key-value pairs",
     *                 example={"app.timezone": "UTC", "app.locale": "en", "feature.inventory": true}
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Tenant context required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index()
    {
        $configs = $this->configService->all();

        return $this->success($configs);
    }

    /**
     * @OA\Get(
     *     path="/api/configuration/{key}",
     *     operationId="getConfigurationByKey",
     *     tags={"Configuration"},
     *     summary="Get configuration value by key",
     *     description="Retrieve a specific configuration value by its key. Requires authentication and tenant context.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="key",
     *         in="path",
     *         description="Configuration key",
     *         required=true,
     *         @OA\Schema(type="string", example="app.timezone")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="key", type="string", example="app.timezone"),
     *                 @OA\Property(property="value", oneOf={
     *                     @OA\Schema(type="string"),
     *                     @OA\Schema(type="boolean"),
     *                     @OA\Schema(type="number"),
     *                     @OA\Schema(type="object")
     *                 }, example="UTC")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Tenant context required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(
     *         response=404,
     *         description="Configuration not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(string $key)
    {
        $value = $this->configService->get($key);

        if ($value === null) {
            return $this->notFound('Configuration not found');
        }

        return $this->success(['key' => $key, 'value' => $value]);
    }

    /**
     * @OA\Post(
     *     path="/api/configuration",
     *     operationId="createConfiguration",
     *     tags={"Configuration"},
     *     summary="Create or update configuration",
     *     description="Set a configuration key-value pair. If the key already exists, it will be updated. Requires authentication and tenant context.",
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"key", "value"},
     *             @OA\Property(property="key", type="string", maxLength=255, example="app.locale", description="Configuration key (dot notation supported)"),
     *             @OA\Property(
     *                 property="value",
     *                 oneOf={
     *                     @OA\Schema(type="string"),
     *                     @OA\Schema(type="boolean"),
     *                     @OA\Schema(type="number"),
     *                     @OA\Schema(type="object"),
     *                     @OA\Schema(type="array", @OA\Items(type="string"))
     *                 },
     *                 example="en",
     *                 description="Configuration value (can be string, number, boolean, object, or array)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Configuration created/updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Resource created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="key", type="string", example="app.locale"),
     *                 @OA\Property(property="value", example="en")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Tenant context required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, description="Server error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required',
        ]);

        try {
            $this->configService->set($validated['key'], $validated['value']);

            return $this->created(['key' => $validated['key'], 'value' => $validated['value']]);
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/configuration/{key}",
     *     operationId="deleteConfiguration",
     *     tags={"Configuration"},
     *     summary="Delete configuration",
     *     description="Remove a configuration key-value pair from the tenant's settings. Requires authentication and tenant context.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="key",
     *         in="path",
     *         description="Configuration key to delete",
     *         required=true,
     *         @OA\Schema(type="string", example="app.custom_setting")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuration deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Resource deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Tenant context required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, description="Server error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function destroy(string $key)
    {
        try {
            $this->configService->forget($key);

            return $this->deleted();
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }
}
