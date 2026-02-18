<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;

/**
 * API Documentation Controller
 *
 * Serves OpenAPI 3.1 specification files without third-party packages.
 * Uses manual YAML files stored in docs/api/ directory.
 */
class DocumentationController extends Controller
{
    /**
     * Get the complete OpenAPI specification
     */
    public function specification(): JsonResponse
    {
        $specPath = base_path('docs/api/openapi.yaml');

        if (! File::exists($specPath)) {
            return response()->json([
                'error' => 'API specification not found',
            ], 404);
        }

        $yaml = File::get($specPath);

        // Parse YAML using native Symfony YAML component (included in Laravel)
        try {
            $spec = \Symfony\Component\Yaml\Yaml::parse($yaml);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Invalid YAML specification',
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json($spec);
    }

    /**
     * Serve the API documentation UI
     */
    public function index()
    {
        return view('api.documentation');
    }

    /**
     * Get specification for a specific module
     */
    public function moduleSpecification(string $module): JsonResponse
    {
        $specPath = base_path("docs/api/modules/{$module}.yaml");

        if (! File::exists($specPath)) {
            return response()->json([
                'error' => "Specification for module '{$module}' not found",
            ], 404);
        }

        $yaml = File::get($specPath);

        try {
            $spec = \Symfony\Component\Yaml\Yaml::parse($yaml);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Invalid YAML specification',
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json($spec);
    }
}
