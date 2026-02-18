<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Services\ConfigurationService;
use Modules\Core\Services\TenantContext;

class MetadataController extends BaseController
{
    public function __construct(
        private readonly ConfigurationService $configService,
        private readonly TenantContext $tenantContext
    ) {}

    /**
     * Get tenant configuration with modules metadata
     *
     * @OA\Get(
     *     path="/api/metadata/tenant/configuration",
     *     operationId="getTenantConfiguration",
     *     tags={"Metadata"},
     *     summary="Get tenant configuration",
     *     description="Retrieve comprehensive tenant configuration including theme, locale, currency, enabled features, and available modules. Requires authentication and tenant context.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tenant configuration retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="name", type="string", example="Acme Corporation"),
     *                 @OA\Property(property="domain", type="string", example="acme.example.com"),
     *                 @OA\Property(
     *                     property="theme",
     *                     type="object",
     *                     @OA\Property(property="primary", type="string", example="#3B82F6"),
     *                     @OA\Property(property="secondary", type="string", example="#10B981"),
     *                     @OA\Property(property="dark", type="boolean", example=false)
     *                 ),
     *                 @OA\Property(
     *                     property="locale",
     *                     type="object",
     *                     @OA\Property(property="default", type="string", example="en"),
     *                     @OA\Property(property="supported", type="array", @OA\Items(type="string"))
     *                 ),
     *                 @OA\Property(property="timezone", type="string", example="UTC"),
     *                 @OA\Property(
     *                     property="features",
     *                     type="object",
     *                     example={"inventory": true, "sales": true, "accounting": false}
     *                 ),
     *                 @OA\Property(
     *                     property="modules",
     *                     type="object",
     *                     description="Available modules metadata"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Tenant context required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function getTenantConfiguration(Request $request): JsonResponse
    {
        $tenant = $this->tenantContext->getCurrentTenant();

        $configuration = [
            'id' => $tenant->uuid,
            'name' => $tenant->name,
            'domain' => $tenant->domain,
            'theme' => $this->getThemeConfiguration($tenant),
            'locale' => $this->getLocaleConfiguration($tenant),
            'currency' => $this->getCurrencyConfiguration($tenant),
            'timezone' => $tenant->getSetting('timezone', 'UTC'),
            'features' => $this->getFeatures($tenant),
            'modules' => $this->getModulesMetadata($tenant),
            'customization' => $tenant->getSetting('customization', []),
        ];

        return $this->success($configuration, 'Tenant configuration retrieved successfully');
    }

    /**
     * Get module metadata
     *
     * @OA\Get(
     *     path="/api/metadata/modules/{moduleName}",
     *     operationId="getModuleMetadata",
     *     tags={"Metadata"},
     *     summary="Get module metadata",
     *     description="Retrieve detailed metadata for a specific module including routes, navigation items, permissions, and features. Requires authentication and tenant context.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="moduleName",
     *         in="path",
     *         description="Module name",
     *         required=true,
     *         @OA\Schema(type="string", enum={"inventory", "sales", "purchasing", "accounting", "manufacturing", "hr"}, example="inventory")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Module metadata retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="name", type="string", example="inventory"),
     *                 @OA\Property(property="label", type="string", example="Inventory"),
     *                 @OA\Property(property="icon", type="string", example="cube"),
     *                 @OA\Property(property="description", type="string", example="Inventory and warehouse management"),
     *                 @OA\Property(property="enabled", type="boolean", example=true),
     *                 @OA\Property(property="order", type="integer", example=2),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="routes", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="navigation", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="features", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Tenant context required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Module not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function getModuleMetadata(Request $request, string $moduleName): JsonResponse
    {
        $metadata = $this->getModuleConfiguration($moduleName);

        if (! $metadata) {
            return $this->error('Module not found', 404);
        }

        return $this->success($metadata, 'Module metadata retrieved successfully');
    }

    /**
     * Get form metadata
     *
     * @OA\Get(
     *     path="/api/metadata/forms/{formId}",
     *     operationId="getFormMetadata",
     *     tags={"Metadata"},
     *     summary="Get form metadata",
     *     description="Retrieve dynamic form configuration including fields, validation rules, sections, and buttons. Used by frontend to render forms dynamically. Requires authentication and tenant context.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="formId",
     *         in="path",
     *         description="Form identifier",
     *         required=true,
     *         @OA\Schema(type="string", example="product.create")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Form metadata retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="product.create"),
     *                 @OA\Property(property="title", type="string", example="Create Product"),
     *                 @OA\Property(property="description", type="string", example="Create a new product in the inventory"),
     *                 @OA\Property(
     *                     property="sections",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", example="basic"),
     *                         @OA\Property(property="title", type="string", example="Basic Information"),
     *                         @OA\Property(
     *                             property="fields",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="name", type="string", example="sku"),
     *                                 @OA\Property(property="label", type="string", example="SKU"),
     *                                 @OA\Property(property="type", type="string", example="text"),
     *                                 @OA\Property(property="required", type="boolean", example=true)
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Tenant context required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Form not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function getFormMetadata(Request $request, string $formId): JsonResponse
    {
        $metadata = $this->getFormConfiguration($formId);

        if (! $metadata) {
            return $this->error('Form not found', 404);
        }

        return $this->success($metadata, 'Form metadata retrieved successfully');
    }

    /**
     * Get table metadata
     *
     * @OA\Get(
     *     path="/api/metadata/tables/{tableId}",
     *     operationId="getTableMetadata",
     *     tags={"Metadata"},
     *     summary="Get table metadata",
     *     description="Retrieve dynamic table/data grid configuration including columns, sorting, filtering, pagination, and action buttons. Used by frontend to render tables dynamically. Requires authentication and tenant context.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="tableId",
     *         in="path",
     *         description="Table identifier",
     *         required=true,
     *         @OA\Schema(type="string", example="modules.list")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Table metadata retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="modules.list"),
     *                 @OA\Property(property="title", type="string", example="Module Records"),
     *                 @OA\Property(property="apiEndpoint", type="string", example="/api/modules/inventory"),
     *                 @OA\Property(
     *                     property="columns",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="key", type="string", example="name"),
     *                         @OA\Property(property="label", type="string", example="Name"),
     *                         @OA\Property(property="type", type="string", example="text"),
     *                         @OA\Property(property="sortable", type="boolean", example=true)
     *                     )
     *                 ),
     *                 @OA\Property(property="searchable", type="boolean", example=true),
     *                 @OA\Property(property="sortable", type="boolean", example=true),
     *                 @OA\Property(property="exportable", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Tenant context required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Table not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function getTableMetadata(Request $request, string $tableId): JsonResponse
    {
        $metadata = $this->getTableConfiguration($tableId);

        if (! $metadata) {
            return $this->error('Table not found', 404);
        }

        return $this->success($metadata, 'Table metadata retrieved successfully');
    }

    /**
     * Get dashboard metadata
     *
     * @OA\Get(
     *     path="/api/metadata/dashboards/{dashboardId}",
     *     operationId="getDashboardMetadata",
     *     tags={"Metadata"},
     *     summary="Get dashboard metadata",
     *     description="Retrieve dynamic dashboard configuration including widgets, charts, and layout. Requires authentication and tenant context.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="dashboardId",
     *         in="path",
     *         description="Dashboard identifier (defaults to 'default' if not provided)",
     *         required=false,
     *         @OA\Schema(type="string", default="default", example="sales")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dashboard metadata retrieved successfully"),
     *             @OA\Property(property="data", type="object", description="Dashboard configuration")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Tenant context required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Dashboard not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function getDashboardMetadata(Request $request, string $dashboardId = 'default'): JsonResponse
    {
        $metadata = $this->getDashboardConfiguration($dashboardId);

        if (! $metadata) {
            return $this->error('Dashboard not found', 404);
        }

        return $this->success($metadata, 'Dashboard metadata retrieved successfully');
    }

    /**
     * Get user permissions
     *
     * @OA\Get(
     *     path="/api/metadata/user/permissions",
     *     operationId="getUserPermissions",
     *     tags={"Metadata"},
     *     summary="Get current user permissions",
     *     description="Retrieve permissions and roles for the authenticated user, including user-level, role-level, and tenant-level permissions. Used by frontend for UI authorization. Requires authentication and tenant context.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User permissions retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="array",
     *                     description="Direct user permissions",
     *                     @OA\Items(type="string"),
     *                     example={"inventory.products.create", "inventory.products.view", "sales.orders.view"}
     *                 ),
     *                 @OA\Property(
     *                     property="role",
     *                     type="array",
     *                     description="User's assigned roles",
     *                     @OA\Items(type="string"),
     *                     example={"inventory_manager", "sales_user"}
     *                 ),
     *                 @OA\Property(
     *                     property="tenant",
     *                     type="array",
     *                     description="Tenant-level permissions",
     *                     @OA\Items(type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Tenant context required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function getUserPermissions(Request $request): JsonResponse
    {
        $user = $request->user();

        $permissions = [
            'user' => $user->getAllPermissions()->pluck('name')->toArray(),
            'role' => $user->getRoleNames()->toArray(),
            'tenant' => $this->tenantContext->getCurrentTenant()?->getSetting('permissions', []),
        ];

        return $this->success($permissions, 'User permissions retrieved successfully');
    }

    /**
     * Get all modules metadata
     *
     * @OA\Get(
     *     path="/api/metadata/modules",
     *     operationId="getAllModules",
     *     tags={"Metadata"},
     *     summary="Get all modules metadata",
     *     description="Retrieve metadata for all available modules for the current tenant. Includes enabled/disabled status and module capabilities. Requires authentication and tenant context.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Modules retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="inventory"),
     *                     @OA\Property(property="label", type="string", example="Inventory"),
     *                     @OA\Property(property="icon", type="string", example="cube"),
     *                     @OA\Property(property="enabled", type="boolean", example=true),
     *                     @OA\Property(property="order", type="integer", example=2)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Tenant context required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function getAllModules(Request $request): JsonResponse
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        $modules = $this->getModulesMetadata($tenant);

        return $this->success(array_values($modules), 'Modules retrieved successfully');
    }

    /**
     * Get navigation metadata
     *
     * @OA\Get(
     *     path="/api/metadata/navigation",
     *     operationId="getNavigation",
     *     tags={"Metadata"},
     *     summary="Get navigation structure",
     *     description="Retrieve the complete navigation menu structure for the current tenant based on enabled modules and user permissions. Used by frontend to build main navigation. Requires authentication and tenant context.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Navigation retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="inventory"),
     *                     @OA\Property(property="label", type="string", example="Inventory"),
     *                     @OA\Property(property="icon", type="string", example="cube"),
     *                     @OA\Property(property="path", type="string", nullable=true, example="/inventory"),
     *                     @OA\Property(property="order", type="integer", example=2),
     *                     @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="visible", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="children",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="string"),
     *                             @OA\Property(property="label", type="string"),
     *                             @OA\Property(property="path", type="string")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Tenant context required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function getNavigation(Request $request): JsonResponse
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        $modules = $this->getModulesMetadata($tenant);
        
        $navigation = [];
        foreach ($modules as $module) {
            if (!empty($module['navigation'])) {
                $navigation = array_merge($navigation, $module['navigation']);
            }
        }

        return $this->success($navigation, 'Navigation retrieved successfully');
    }

    /**
     * Get all permissions
     *
     * @OA\Get(
     *     path="/api/metadata/permissions",
     *     operationId="getPermissions",
     *     tags={"Metadata"},
     *     summary="Get all permissions for current user",
     *     description="Retrieve a flat list of all permissions assigned to the authenticated user. Requires authentication and tenant context.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permissions retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 example={"inventory.products.view", "inventory.products.create", "inventory.products.update", "sales.orders.view"}
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Tenant context required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function getPermissions(Request $request): JsonResponse
    {
        $user = $request->user();
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();

        return $this->success($permissions, 'Permissions retrieved successfully');
    }

    /**
     * Get workflow metadata
     *
     * @OA\Get(
     *     path="/api/metadata/workflows/{workflowId}",
     *     operationId="getWorkflowMetadata",
     *     tags={"Metadata"},
     *     summary="Get workflow metadata",
     *     description="Retrieve workflow state machine configuration including states, transitions, and permissions. Used for approval workflows and status management. Requires authentication and tenant context.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="workflowId",
     *         in="path",
     *         description="Workflow identifier",
     *         required=true,
     *         @OA\Schema(type="string", example="sales_order.approval")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Workflow metadata retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="sales_order.approval"),
     *                 @OA\Property(property="name", type="string", example="Sales Order Approval"),
     *                 @OA\Property(property="description", type="string", example="Approval workflow for sales orders"),
     *                 @OA\Property(
     *                     property="states",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", example="draft"),
     *                         @OA\Property(property="label", type="string", example="Draft"),
     *                         @OA\Property(property="initial", type="boolean", example=true)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="transitions",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="from", type="string", example="draft"),
     *                         @OA\Property(property="to", type="string", example="pending_approval"),
     *                         @OA\Property(property="action", type="string", example="submit_for_approval"),
     *                         @OA\Property(property="label", type="string", example="Submit for Approval"),
     *                         @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Forbidden - Tenant context required", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Workflow not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function getWorkflowMetadata(Request $request, string $workflowId): JsonResponse
    {
        $metadata = $this->getWorkflowConfiguration($workflowId);

        if (!$metadata) {
            return $this->error('Workflow not found', 404);
        }

        return $this->success($metadata, 'Workflow metadata retrieved successfully');
    }

    /**
     * Get theme configuration
     */
    private function getThemeConfiguration($tenant): array
    {
        return [
            'primary' => $tenant->getSetting('theme.primary', '#3B82F6'),
            'secondary' => $tenant->getSetting('theme.secondary', '#10B981'),
            'accent' => $tenant->getSetting('theme.accent', '#8B5CF6'),
            'success' => $tenant->getSetting('theme.success', '#10B981'),
            'warning' => $tenant->getSetting('theme.warning', '#F59E0B'),
            'danger' => $tenant->getSetting('theme.danger', '#EF4444'),
            'info' => $tenant->getSetting('theme.info', '#3B82F6'),
            'dark' => $tenant->getSetting('theme.dark', false),
            'customCss' => $tenant->getSetting('theme.customCss'),
        ];
    }

    /**
     * Get locale configuration
     */
    private function getLocaleConfiguration($tenant): array
    {
        return [
            'default' => $tenant->getSetting('locale.default', 'en'),
            'supported' => $tenant->getSetting('locale.supported', ['en', 'es', 'fr', 'de']),
            'fallback' => $tenant->getSetting('locale.fallback', 'en'),
        ];
    }

    /**
     * Get currency configuration
     */
    private function getCurrencyConfiguration($tenant): array
    {
        return [
            'default' => $tenant->getSetting('currency.default', 'USD'),
            'supported' => $tenant->getSetting('currency.supported', ['USD', 'EUR', 'GBP', 'JPY']),
            'displayFormat' => $tenant->getSetting('currency.displayFormat', '$0,0.00'),
            'decimalPlaces' => $tenant->getSetting('currency.decimalPlaces', 2),
        ];
    }

    /**
     * Get enabled features
     */
    private function getFeatures($tenant): array
    {
        return $tenant->getSetting('features', [
            'inventory' => true,
            'sales' => true,
            'purchasing' => true,
            'accounting' => false,
            'manufacturing' => false,
            'hr' => false,
            'analytics' => true,
        ]);
    }

    /**
     * Get modules metadata
     */
    private function getModulesMetadata($tenant): array
    {
        $modules = [];

        // Core module
        $modules['core'] = [
            'name' => 'core',
            'label' => 'Core',
            'icon' => 'cog',
            'description' => 'Core functionality and settings',
            'enabled' => true,
            'order' => 1,
            'permissions' => ['core.access'],
            'routes' => [],
            'navigation' => [],
            'features' => [],
        ];

        // Inventory module
        if ($tenant->getSetting('features.inventory', true)) {
            $modules['inventory'] = $this->getModuleConfiguration('inventory');
        }

        // Sales module
        if ($tenant->getSetting('features.sales', true)) {
            $modules['sales'] = $this->getModuleConfiguration('sales');
        }

        // Purchasing module
        if ($tenant->getSetting('features.purchasing', true)) {
            $modules['purchasing'] = $this->getModuleConfiguration('purchasing');
        }

        return $modules;
    }

    /**
     * Get module configuration
     */
    private function getModuleConfiguration(string $moduleName): array
    {
        $configurations = [
            'inventory' => [
                'name' => 'inventory',
                'label' => 'Inventory',
                'icon' => 'cube',
                'description' => 'Inventory and warehouse management',
                'enabled' => true,
                'order' => 2,
                'permissions' => ['inventory.access'],
                'routes' => [
                    [
                        'path' => '/inventory/products',
                        'name' => 'inventory.products',
                        'component' => 'inventory/ProductList',
                        'meta' => [
                            'title' => 'Products',
                            'permissions' => ['inventory.products.view'],
                            'breadcrumbs' => [
                                ['label' => 'Home', 'path' => '/'],
                                ['label' => 'Inventory'],
                                ['label' => 'Products'],
                            ],
                            'requiresAuth' => true,
                        ],
                    ],
                    [
                        'path' => '/inventory/warehouses',
                        'name' => 'inventory.warehouses',
                        'component' => 'inventory/WarehouseList',
                        'meta' => [
                            'title' => 'Warehouses',
                            'permissions' => ['inventory.warehouses.view'],
                            'breadcrumbs' => [
                                ['label' => 'Home', 'path' => '/'],
                                ['label' => 'Inventory'],
                                ['label' => 'Warehouses'],
                            ],
                            'requiresAuth' => true,
                        ],
                    ],
                ],
                'navigation' => [
                    [
                        'id' => 'inventory',
                        'label' => 'Inventory',
                        'icon' => 'cube',
                        'path' => null,
                        'order' => 2,
                        'permissions' => ['inventory.access'],
                        'children' => [
                            [
                                'id' => 'inventory.products',
                                'label' => 'Products',
                                'icon' => 'cube',
                                'path' => '/inventory/products',
                                'order' => 1,
                                'permissions' => ['inventory.products.view'],
                                'visible' => true,
                            ],
                            [
                                'id' => 'inventory.warehouses',
                                'label' => 'Warehouses',
                                'icon' => 'building',
                                'path' => '/inventory/warehouses',
                                'order' => 2,
                                'permissions' => ['inventory.warehouses.view'],
                                'visible' => true,
                            ],
                        ],
                        'visible' => true,
                    ],
                ],
                'features' => [
                    'batch_tracking' => true,
                    'serial_tracking' => true,
                    'multi_warehouse' => true,
                ],
            ],
            'sales' => [
                'name' => 'sales',
                'label' => 'Sales',
                'icon' => 'shopping-cart',
                'description' => 'Sales and CRM',
                'enabled' => true,
                'order' => 3,
                'permissions' => ['sales.access'],
                'routes' => [],
                'navigation' => [],
                'features' => [],
            ],
            'purchasing' => [
                'name' => 'purchasing',
                'label' => 'Purchasing',
                'icon' => 'shopping-bag',
                'description' => 'Procurement and supplier management',
                'enabled' => true,
                'order' => 4,
                'permissions' => ['purchasing.access'],
                'routes' => [],
                'navigation' => [],
                'features' => [],
            ],
        ];

        return $configurations[$moduleName] ?? [];
    }

    /**
     * Get form configuration
     */
    private function getFormConfiguration(string $formId): ?array
    {
        // Sample form configurations
        $forms = [
            'product.create' => [
                'id' => 'product.create',
                'title' => 'Create Product',
                'description' => 'Create a new product in the inventory',
                'sections' => [
                    [
                        'id' => 'basic',
                        'title' => 'Basic Information',
                        'fields' => [
                            [
                                'name' => 'sku',
                                'label' => 'SKU',
                                'type' => 'text',
                                'required' => true,
                                'placeholder' => 'PRD-001',
                            ],
                            [
                                'name' => 'name',
                                'label' => 'Product Name',
                                'type' => 'text',
                                'required' => true,
                            ],
                            [
                                'name' => 'category_id',
                                'label' => 'Category',
                                'type' => 'select',
                                'required' => false,
                                'options' => [],
                            ],
                            [
                                'name' => 'status',
                                'label' => 'Status',
                                'type' => 'select',
                                'required' => true,
                                'defaultValue' => 'active',
                                'options' => [
                                    ['label' => 'Active', 'value' => 'active'],
                                    ['label' => 'Inactive', 'value' => 'inactive'],
                                ],
                            ],
                        ],
                    ],
                ],
                'submitButton' => [
                    'label' => 'Create Product',
                    'variant' => 'primary',
                ],
                'cancelButton' => [
                    'label' => 'Cancel',
                ],
            ],
            'modules.form' => [
                'id' => 'modules.form',
                'title' => 'Module Record',
                'description' => 'Create or edit a module record',
                'sections' => [
                    [
                        'id' => 'main',
                        'title' => 'General Information',
                        'fields' => [
                            [
                                'name' => 'name',
                                'label' => 'Name',
                                'type' => 'text',
                                'required' => true,
                                'placeholder' => 'Enter name',
                                'helpText' => 'Enter a unique name for this record',
                            ],
                            [
                                'name' => 'description',
                                'label' => 'Description',
                                'type' => 'textarea',
                                'required' => false,
                                'placeholder' => 'Enter description',
                                'rows' => 4,
                            ],
                            [
                                'name' => 'status',
                                'label' => 'Status',
                                'type' => 'select',
                                'required' => true,
                                'defaultValue' => 'active',
                                'options' => [
                                    ['label' => 'Active', 'value' => 'active'],
                                    ['label' => 'Inactive', 'value' => 'inactive'],
                                ],
                            ],
                        ],
                    ],
                ],
                'submitButton' => [
                    'label' => 'Save',
                    'variant' => 'primary',
                ],
                'cancelButton' => [
                    'label' => 'Cancel',
                ],
            ],
            // IAM Module Forms
            'iam.users.create' => [
                'id' => 'iam.users.create',
                'title' => 'Create User',
                'description' => 'Create a new user account',
                'sections' => [
                    [
                        'id' => 'basic',
                        'title' => 'Basic Information',
                        'fields' => [
                            [
                                'name' => 'name',
                                'label' => 'Full Name',
                                'type' => 'text',
                                'required' => true,
                                'placeholder' => 'John Doe',
                                'helpText' => "User's full name",
                            ],
                            [
                                'name' => 'email',
                                'label' => 'Email Address',
                                'type' => 'email',
                                'required' => true,
                                'placeholder' => 'john.doe@example.com',
                                'helpText' => "User's email address (must be unique)",
                            ],
                            [
                                'name' => 'phone',
                                'label' => 'Phone Number',
                                'type' => 'text',
                                'required' => false,
                                'placeholder' => '+1234567890',
                            ],
                            [
                                'name' => 'password',
                                'label' => 'Password',
                                'type' => 'password',
                                'required' => true,
                                'placeholder' => '••••••••',
                                'helpText' => 'Minimum 8 characters with mixed case, numbers, and symbols',
                            ],
                            [
                                'name' => 'password_confirmation',
                                'label' => 'Confirm Password',
                                'type' => 'password',
                                'required' => true,
                                'placeholder' => '••••••••',
                            ],
                        ],
                    ],
                    [
                        'id' => 'settings',
                        'title' => 'Settings',
                        'fields' => [
                            [
                                'name' => 'is_active',
                                'label' => 'Active',
                                'type' => 'checkbox',
                                'defaultValue' => true,
                                'helpText' => 'Enable or disable user account',
                            ],
                            [
                                'name' => 'timezone',
                                'label' => 'Timezone',
                                'type' => 'select',
                                'required' => false,
                                'defaultValue' => 'UTC',
                                'options' => [
                                    ['label' => 'UTC', 'value' => 'UTC'],
                                    ['label' => 'America/New_York', 'value' => 'America/New_York'],
                                    ['label' => 'Europe/London', 'value' => 'Europe/London'],
                                    ['label' => 'Asia/Tokyo', 'value' => 'Asia/Tokyo'],
                                ],
                            ],
                            [
                                'name' => 'locale',
                                'label' => 'Language',
                                'type' => 'select',
                                'required' => false,
                                'defaultValue' => 'en',
                                'options' => [
                                    ['label' => 'English', 'value' => 'en'],
                                    ['label' => 'Spanish', 'value' => 'es'],
                                    ['label' => 'French', 'value' => 'fr'],
                                    ['label' => 'German', 'value' => 'de'],
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => 'roles',
                        'title' => 'Roles & Permissions',
                        'fields' => [
                            [
                                'name' => 'roles',
                                'label' => 'Roles',
                                'type' => 'multiselect',
                                'required' => false,
                                'options' => [],
                                'apiEndpoint' => '/api/roles',
                                'helpText' => 'Assign roles to this user',
                            ],
                        ],
                    ],
                ],
                'submitButton' => [
                    'label' => 'Create User',
                    'variant' => 'primary',
                ],
                'cancelButton' => [
                    'label' => 'Cancel',
                ],
            ],
            'iam.users.edit' => [
                'id' => 'iam.users.edit',
                'title' => 'Edit User',
                'description' => 'Update user account information',
                'sections' => [
                    [
                        'id' => 'basic',
                        'title' => 'Basic Information',
                        'fields' => [
                            [
                                'name' => 'name',
                                'label' => 'Full Name',
                                'type' => 'text',
                                'required' => true,
                                'placeholder' => 'John Doe',
                            ],
                            [
                                'name' => 'email',
                                'label' => 'Email Address',
                                'type' => 'email',
                                'required' => true,
                                'placeholder' => 'john.doe@example.com',
                            ],
                            [
                                'name' => 'phone',
                                'label' => 'Phone Number',
                                'type' => 'text',
                                'required' => false,
                                'placeholder' => '+1234567890',
                            ],
                        ],
                    ],
                    [
                        'id' => 'settings',
                        'title' => 'Settings',
                        'fields' => [
                            [
                                'name' => 'is_active',
                                'label' => 'Active',
                                'type' => 'checkbox',
                                'defaultValue' => true,
                            ],
                            [
                                'name' => 'timezone',
                                'label' => 'Timezone',
                                'type' => 'select',
                                'required' => false,
                                'options' => [
                                    ['label' => 'UTC', 'value' => 'UTC'],
                                    ['label' => 'America/New_York', 'value' => 'America/New_York'],
                                    ['label' => 'Europe/London', 'value' => 'Europe/London'],
                                    ['label' => 'Asia/Tokyo', 'value' => 'Asia/Tokyo'],
                                ],
                            ],
                            [
                                'name' => 'locale',
                                'label' => 'Language',
                                'type' => 'select',
                                'required' => false,
                                'options' => [
                                    ['label' => 'English', 'value' => 'en'],
                                    ['label' => 'Spanish', 'value' => 'es'],
                                    ['label' => 'French', 'value' => 'fr'],
                                    ['label' => 'German', 'value' => 'de'],
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => 'roles',
                        'title' => 'Roles & Permissions',
                        'fields' => [
                            [
                                'name' => 'roles',
                                'label' => 'Roles',
                                'type' => 'multiselect',
                                'required' => false,
                                'options' => [],
                                'apiEndpoint' => '/api/roles',
                            ],
                        ],
                    ],
                ],
                'submitButton' => [
                    'label' => 'Update User',
                    'variant' => 'primary',
                ],
                'cancelButton' => [
                    'label' => 'Cancel',
                ],
            ],
            'iam.roles.create' => [
                'id' => 'iam.roles.create',
                'title' => 'Create Role',
                'description' => 'Create a new role with permissions',
                'sections' => [
                    [
                        'id' => 'basic',
                        'title' => 'Basic Information',
                        'fields' => [
                            [
                                'name' => 'name',
                                'label' => 'Role Name',
                                'type' => 'text',
                                'required' => true,
                                'placeholder' => 'manager',
                                'helpText' => 'Unique role identifier (lowercase, no spaces)',
                            ],
                            [
                                'name' => 'display_name',
                                'label' => 'Display Name',
                                'type' => 'text',
                                'required' => false,
                                'placeholder' => 'Manager',
                                'helpText' => 'Human-readable role name',
                            ],
                            [
                                'name' => 'description',
                                'label' => 'Description',
                                'type' => 'textarea',
                                'required' => false,
                                'placeholder' => 'Manager role with elevated permissions',
                                'rows' => 3,
                            ],
                        ],
                    ],
                    [
                        'id' => 'hierarchy',
                        'title' => 'Role Hierarchy',
                        'fields' => [
                            [
                                'name' => 'parent_id',
                                'label' => 'Parent Role',
                                'type' => 'select',
                                'required' => false,
                                'options' => [],
                                'apiEndpoint' => '/api/roles',
                                'helpText' => 'Optional parent role for hierarchical structure',
                            ],
                        ],
                    ],
                    [
                        'id' => 'permissions',
                        'title' => 'Permissions',
                        'fields' => [
                            [
                                'name' => 'permissions',
                                'label' => 'Assign Permissions',
                                'type' => 'multiselect',
                                'required' => false,
                                'options' => [],
                                'apiEndpoint' => '/api/permissions',
                                'helpText' => 'Select permissions for this role',
                            ],
                        ],
                    ],
                ],
                'submitButton' => [
                    'label' => 'Create Role',
                    'variant' => 'primary',
                ],
                'cancelButton' => [
                    'label' => 'Cancel',
                ],
            ],
            'iam.roles.edit' => [
                'id' => 'iam.roles.edit',
                'title' => 'Edit Role',
                'description' => 'Update role information and permissions',
                'sections' => [
                    [
                        'id' => 'basic',
                        'title' => 'Basic Information',
                        'fields' => [
                            [
                                'name' => 'name',
                                'label' => 'Role Name',
                                'type' => 'text',
                                'required' => true,
                                'placeholder' => 'manager',
                            ],
                            [
                                'name' => 'display_name',
                                'label' => 'Display Name',
                                'type' => 'text',
                                'required' => false,
                                'placeholder' => 'Manager',
                            ],
                            [
                                'name' => 'description',
                                'label' => 'Description',
                                'type' => 'textarea',
                                'required' => false,
                                'rows' => 3,
                            ],
                        ],
                    ],
                    [
                        'id' => 'hierarchy',
                        'title' => 'Role Hierarchy',
                        'fields' => [
                            [
                                'name' => 'parent_id',
                                'label' => 'Parent Role',
                                'type' => 'select',
                                'required' => false,
                                'options' => [],
                                'apiEndpoint' => '/api/roles',
                            ],
                        ],
                    ],
                    [
                        'id' => 'permissions',
                        'title' => 'Permissions',
                        'fields' => [
                            [
                                'name' => 'permissions',
                                'label' => 'Assign Permissions',
                                'type' => 'multiselect',
                                'required' => false,
                                'options' => [],
                                'apiEndpoint' => '/api/permissions',
                            ],
                        ],
                    ],
                ],
                'submitButton' => [
                    'label' => 'Update Role',
                    'variant' => 'primary',
                ],
                'cancelButton' => [
                    'label' => 'Cancel',
                ],
            ],
            'iam.permissions.create' => [
                'id' => 'iam.permissions.create',
                'title' => 'Create Permission',
                'description' => 'Create a new permission',
                'sections' => [
                    [
                        'id' => 'basic',
                        'title' => 'Permission Information',
                        'fields' => [
                            [
                                'name' => 'resource',
                                'label' => 'Resource',
                                'type' => 'text',
                                'required' => true,
                                'placeholder' => 'user',
                                'helpText' => 'Resource name (e.g., user, role, product)',
                            ],
                            [
                                'name' => 'action',
                                'label' => 'Action',
                                'type' => 'text',
                                'required' => true,
                                'placeholder' => 'create',
                                'helpText' => 'Action name (e.g., create, read, update, delete)',
                            ],
                            [
                                'name' => 'description',
                                'label' => 'Description',
                                'type' => 'textarea',
                                'required' => false,
                                'placeholder' => 'Allows creating new users',
                                'rows' => 3,
                                'helpText' => 'Brief description of what this permission allows',
                            ],
                        ],
                    ],
                ],
                'submitButton' => [
                    'label' => 'Create Permission',
                    'variant' => 'primary',
                ],
                'cancelButton' => [
                    'label' => 'Cancel',
                ],
            ],
        ];

        return $forms[$formId] ?? null;
    }

    /**
     * Get table configuration
     */
    private function getTableConfiguration(string $tableId): ?array
    {
        // Sample table configurations
        $tables = [
            'modules.list' => [
                'id' => 'modules.list',
                'title' => 'Module Records',
                'apiEndpoint' => '/api/modules/' . request()->route('module'),
                'columns' => [
                    [
                        'key' => 'id',
                        'label' => 'ID',
                        'type' => 'number',
                        'sortable' => true,
                        'width' => '80px',
                    ],
                    [
                        'key' => 'name',
                        'label' => 'Name',
                        'type' => 'text',
                        'sortable' => true,
                    ],
                    [
                        'key' => 'status',
                        'label' => 'Status',
                        'type' => 'badge',
                        'sortable' => true,
                        'width' => '120px',
                    ],
                    [
                        'key' => 'created_at',
                        'label' => 'Created',
                        'type' => 'datetime',
                        'sortable' => true,
                        'width' => '180px',
                    ],
                ],
                'actions' => [
                    [
                        'id' => 'view',
                        'label' => 'View',
                        'action' => 'view',
                        'variant' => 'secondary',
                    ],
                    [
                        'id' => 'edit',
                        'label' => 'Edit',
                        'action' => 'edit',
                        'variant' => 'primary',
                        'permissions' => ['*.update'],
                    ],
                    [
                        'id' => 'delete',
                        'label' => 'Delete',
                        'action' => 'delete',
                        'variant' => 'danger',
                        'permissions' => ['*.delete'],
                        'confirm' => [
                            'title' => 'Delete Record',
                            'message' => 'Are you sure you want to delete this record? This action cannot be undone.',
                        ],
                    ],
                ],
                'searchable' => true,
                'sortable' => true,
                'pagination' => [
                    'enabled' => true,
                    'pageSize' => 15,
                    'pageSizeOptions' => [10, 15, 25, 50, 100],
                ],
                'exportable' => true,
            ],
            // IAM Module Tables
            'iam.users.list' => [
                'id' => 'iam.users.list',
                'title' => 'Users',
                'apiEndpoint' => '/api/users',
                'columns' => [
                    [
                        'key' => 'id',
                        'label' => 'ID',
                        'type' => 'number',
                        'sortable' => true,
                        'width' => '80px',
                    ],
                    [
                        'key' => 'name',
                        'label' => 'Name',
                        'type' => 'text',
                        'sortable' => true,
                    ],
                    [
                        'key' => 'email',
                        'label' => 'Email',
                        'type' => 'text',
                        'sortable' => true,
                    ],
                    [
                        'key' => 'roles',
                        'label' => 'Roles',
                        'type' => 'tags',
                        'sortable' => false,
                        'width' => '200px',
                    ],
                    [
                        'key' => 'is_active',
                        'label' => 'Status',
                        'type' => 'badge',
                        'sortable' => true,
                        'width' => '100px',
                        'formatter' => [
                            'type' => 'badge',
                            'mapping' => [
                                '1' => ['label' => 'Active', 'variant' => 'success'],
                                '0' => ['label' => 'Inactive', 'variant' => 'danger'],
                            ],
                        ],
                    ],
                    [
                        'key' => 'created_at',
                        'label' => 'Created',
                        'type' => 'datetime',
                        'sortable' => true,
                        'width' => '180px',
                    ],
                ],
                'actions' => [
                    [
                        'id' => 'view',
                        'label' => 'View',
                        'action' => 'view',
                        'variant' => 'secondary',
                    ],
                    [
                        'id' => 'edit',
                        'label' => 'Edit',
                        'action' => 'edit',
                        'variant' => 'primary',
                        'permissions' => ['iam.users.update'],
                    ],
                    [
                        'id' => 'delete',
                        'label' => 'Delete',
                        'action' => 'delete',
                        'variant' => 'danger',
                        'permissions' => ['iam.users.delete'],
                        'confirm' => [
                            'title' => 'Delete User',
                            'message' => 'Are you sure you want to delete this user? This action cannot be undone.',
                        ],
                    ],
                ],
                'searchable' => true,
                'sortable' => true,
                'pagination' => [
                    'enabled' => true,
                    'pageSize' => 20,
                    'pageSizeOptions' => [10, 20, 50, 100],
                ],
                'exportable' => true,
                'filters' => [
                    [
                        'name' => 'is_active',
                        'label' => 'Status',
                        'type' => 'select',
                        'options' => [
                            ['label' => 'All', 'value' => ''],
                            ['label' => 'Active', 'value' => '1'],
                            ['label' => 'Inactive', 'value' => '0'],
                        ],
                    ],
                ],
            ],
            'iam.roles.list' => [
                'id' => 'iam.roles.list',
                'title' => 'Roles',
                'apiEndpoint' => '/api/roles',
                'columns' => [
                    [
                        'key' => 'id',
                        'label' => 'ID',
                        'type' => 'number',
                        'sortable' => true,
                        'width' => '80px',
                    ],
                    [
                        'key' => 'name',
                        'label' => 'Role Name',
                        'type' => 'text',
                        'sortable' => true,
                    ],
                    [
                        'key' => 'display_name',
                        'label' => 'Display Name',
                        'type' => 'text',
                        'sortable' => true,
                    ],
                    [
                        'key' => 'description',
                        'label' => 'Description',
                        'type' => 'text',
                        'sortable' => false,
                    ],
                    [
                        'key' => 'permissions_count',
                        'label' => 'Permissions',
                        'type' => 'number',
                        'sortable' => true,
                        'width' => '120px',
                    ],
                    [
                        'key' => 'created_at',
                        'label' => 'Created',
                        'type' => 'datetime',
                        'sortable' => true,
                        'width' => '180px',
                    ],
                ],
                'actions' => [
                    [
                        'id' => 'view',
                        'label' => 'View',
                        'action' => 'view',
                        'variant' => 'secondary',
                    ],
                    [
                        'id' => 'edit',
                        'label' => 'Edit',
                        'action' => 'edit',
                        'variant' => 'primary',
                        'permissions' => ['iam.roles.update'],
                    ],
                    [
                        'id' => 'delete',
                        'label' => 'Delete',
                        'action' => 'delete',
                        'variant' => 'danger',
                        'permissions' => ['iam.roles.delete'],
                        'confirm' => [
                            'title' => 'Delete Role',
                            'message' => 'Are you sure you want to delete this role? Users with this role will lose their assigned permissions.',
                        ],
                    ],
                ],
                'searchable' => true,
                'sortable' => true,
                'pagination' => [
                    'enabled' => true,
                    'pageSize' => 20,
                    'pageSizeOptions' => [10, 20, 50, 100],
                ],
                'exportable' => true,
            ],
            'iam.permissions.list' => [
                'id' => 'iam.permissions.list',
                'title' => 'Permissions',
                'apiEndpoint' => '/api/permissions',
                'columns' => [
                    [
                        'key' => 'id',
                        'label' => 'ID',
                        'type' => 'number',
                        'sortable' => true,
                        'width' => '80px',
                    ],
                    [
                        'key' => 'name',
                        'label' => 'Permission Name',
                        'type' => 'text',
                        'sortable' => true,
                    ],
                    [
                        'key' => 'resource',
                        'label' => 'Resource',
                        'type' => 'text',
                        'sortable' => true,
                        'width' => '150px',
                    ],
                    [
                        'key' => 'action',
                        'label' => 'Action',
                        'type' => 'text',
                        'sortable' => true,
                        'width' => '120px',
                    ],
                    [
                        'key' => 'description',
                        'label' => 'Description',
                        'type' => 'text',
                        'sortable' => false,
                    ],
                    [
                        'key' => 'created_at',
                        'label' => 'Created',
                        'type' => 'datetime',
                        'sortable' => true,
                        'width' => '180px',
                    ],
                ],
                'actions' => [
                    [
                        'id' => 'view',
                        'label' => 'View',
                        'action' => 'view',
                        'variant' => 'secondary',
                    ],
                    [
                        'id' => 'delete',
                        'label' => 'Delete',
                        'action' => 'delete',
                        'variant' => 'danger',
                        'permissions' => ['iam.permissions.delete'],
                        'confirm' => [
                            'title' => 'Delete Permission',
                            'message' => 'Are you sure you want to delete this permission? Roles using this permission will lose it.',
                        ],
                    ],
                ],
                'searchable' => true,
                'sortable' => true,
                'pagination' => [
                    'enabled' => true,
                    'pageSize' => 20,
                    'pageSizeOptions' => [10, 20, 50, 100],
                ],
                'exportable' => true,
                'filters' => [
                    [
                        'name' => 'resource',
                        'label' => 'Resource',
                        'type' => 'select',
                        'options' => [
                            ['label' => 'All', 'value' => ''],
                            ['label' => 'User', 'value' => 'user'],
                            ['label' => 'Role', 'value' => 'role'],
                            ['label' => 'Permission', 'value' => 'permission'],
                        ],
                    ],
                ],
            ],
        ];

        return $tables[$tableId] ?? null;
    }

    /**
     * Get dashboard configuration
     */
    private function getDashboardConfiguration(string $dashboardId): ?array
    {
        // Sample dashboard configurations
        return null;
    }

    /**
     * Get workflow configuration
     */
    private function getWorkflowConfiguration(string $workflowId): ?array
    {
        // Sample workflow configurations
        $workflows = [
            'sales_order.approval' => [
                'id' => 'sales_order.approval',
                'name' => 'Sales Order Approval',
                'description' => 'Approval workflow for sales orders',
                'states' => [
                    ['id' => 'draft', 'label' => 'Draft', 'initial' => true],
                    ['id' => 'pending_approval', 'label' => 'Pending Approval'],
                    ['id' => 'approved', 'label' => 'Approved'],
                    ['id' => 'rejected', 'label' => 'Rejected'],
                ],
                'transitions' => [
                    [
                        'from' => 'draft',
                        'to' => 'pending_approval',
                        'action' => 'submit_for_approval',
                        'label' => 'Submit for Approval',
                        'permissions' => ['sales.orders.submit'],
                    ],
                    [
                        'from' => 'pending_approval',
                        'to' => 'approved',
                        'action' => 'approve',
                        'label' => 'Approve',
                        'permissions' => ['sales.orders.approve'],
                    ],
                    [
                        'from' => 'pending_approval',
                        'to' => 'rejected',
                        'action' => 'reject',
                        'label' => 'Reject',
                        'permissions' => ['sales.orders.approve'],
                    ],
                ],
            ],
        ];

        return $workflows[$workflowId] ?? null;
    }
}
