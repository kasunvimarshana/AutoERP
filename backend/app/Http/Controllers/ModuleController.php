<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ModuleRegistry;
use Illuminate\Http\JsonResponse;

/**
 * Module Metadata API Controller
 *
 * Exposes module configuration and metadata to the frontend
 * for dynamic routing, navigation, and feature discovery.
 */
class ModuleController extends Controller
{
    public function __construct(
        protected ModuleRegistry $registry
    ) {}

    /**
     * Get all module metadata
     */
    public function index(): JsonResponse
    {
        $metadata = $this->registry->getMetadata();

        return response()->json([
            'success' => true,
            'data' => $metadata,
            'statistics' => $this->registry->getStatistics(),
        ]);
    }

    /**
     * Get specific module metadata
     */
    public function show(string $moduleId): JsonResponse
    {
        $metadata = $this->registry->getMetadata($moduleId);

        if (! $metadata) {
            return response()->json([
                'success' => false,
                'message' => "Module '{$moduleId}' not found",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $metadata,
        ]);
    }

    /**
     * Get all module routes
     */
    public function routes(): JsonResponse
    {
        $routes = $this->registry->getAllRoutes();

        return response()->json([
            'success' => true,
            'data' => $routes,
        ]);
    }

    /**
     * Get all module permissions
     */
    public function permissions(): JsonResponse
    {
        $permissions = $this->registry->getAllPermissions();

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ]);
    }
}
