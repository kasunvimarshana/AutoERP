<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Core\Http\Controllers\BaseCrudController;
use App\Modules\CRM\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * Enhanced Customer Controller with Advanced CRUD Features
 *
 * Demonstrates the complete CRUD framework with:
 * - Field-level filtering: ?filter[status]=active&filter[type]=individual
 * - Global search: ?search=john
 * - Multi-field sorting: ?sort=name,-created_at
 * - Eager loading: ?include=organization,branch,contacts,vehicles
 * - Sparse fieldsets: ?fields[customers]=id,name,email
 * - Pagination: ?page[number]=1&page[size]=20
 *
 * @OA\Tag(
 *     name="Customers (Enhanced)",
 *     description="Enhanced customer management with advanced query features"
 * )
 */
class CustomerEnhancedController extends BaseCrudController
{
    /**
     * CustomerEnhancedController constructor.
     */
    public function __construct(CustomerService $service)
    {
        parent::__construct($service);
    }

    /**
     * Configure advanced query capabilities.
     */
    protected function getQueryConfig(): array
    {
        return [
            // Define which fields can be filtered
            'allowedFilters' => [
                'first_name',
                'last_name',
                'company_name',
                'email',
                'phone',
                'mobile',
                'customer_code',
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('organization_id'),
                AllowedFilter::exact('branch_id'),
                AllowedFilter::scope('active'), // Uses a model scope if defined
                // Custom callback filter for date range
                AllowedFilter::callback('created_after', function ($query, $value) {
                    $query->where('created_at', '>=', $value);
                }),
                AllowedFilter::callback('created_before', function ($query, $value) {
                    $query->where('created_at', '<=', $value);
                }),
                // Relation-based filter
                AllowedFilter::callback('has_vehicles', function ($query, $value) {
                    if ($value === 'true' || $value === '1') {
                        $query->has('vehicles');
                    }
                }),
            ],

            // Define which fields can be used for sorting
            'allowedSorts' => [
                'first_name',
                'last_name',
                'company_name',
                'email',
                'customer_code',
                'created_at',
                'updated_at',
                // Custom sort
                AllowedSort::field('organization_name', 'organizations.name'),
            ],

            // Define which relations can be eager loaded
            'allowedIncludes' => [
                'organization',
                'branch',
                'contacts',
                'vehicles',
                'vehicles.serviceHistory', // Nested includes
            ],

            // Define sparse fieldsets
            'allowedFields' => [
                'customers' => ['id', 'first_name', 'last_name', 'company_name', 'email', 'phone', 'mobile', 'customer_code', 'type', 'status', 'created_at'],
                'organizations' => ['id', 'name', 'code'],
                'branches' => ['id', 'name', 'code'],
            ],

            // Default sorting
            'defaultSort' => '-created_at',

            // Default pagination
            'perPage' => 15,

            // Fields for global search
            'globalSearch' => ['first_name', 'last_name', 'company_name', 'email', 'phone', 'mobile', 'customer_code'],
        ];
    }

    /**
     * Get validation rules for create and update operations.
     *
     * @param  mixed  $id
     */
    protected function getValidationRules(string $action, $id = null): array
    {
        if ($action === 'create') {
            return [
                'type' => 'required|in:individual,business',
                // For individuals - require first_name and last_name
                'first_name' => 'required_if:type,individual|string|max:255',
                'last_name' => 'required_if:type,individual|string|max:255',
                // For businesses - require company_name
                'company_name' => 'required_if:type,business|string|max:255',
                'email' => 'nullable|email|unique:customers,email',
                'phone' => 'nullable|string|max:20',
                'mobile' => 'nullable|string|max:20',
                'organization_id' => 'sometimes|exists:organizations,id',
                'branch_id' => 'sometimes|exists:branches,id',
                'status' => 'sometimes|in:active,inactive,blocked',
                'billing_address' => 'nullable|string',
                'shipping_address' => 'nullable|string',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'tax_id' => 'nullable|string|max:50',
                'credit_limit' => 'nullable|numeric|min:0',
                'payment_terms_days' => 'nullable|integer|min:0',
                'preferences' => 'nullable|json',
                'metadata' => 'nullable|json',
            ];
        }

        return [
            'type' => 'sometimes|in:individual,business',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'company_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:customers,email,'.$id,
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'organization_id' => 'sometimes|exists:organizations,id',
            'branch_id' => 'sometimes|exists:branches,id',
            'status' => 'sometimes|in:active,inactive,blocked',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:50',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:0',
            'preferences' => 'nullable|json',
            'metadata' => 'nullable|json',
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/v1/customers/enhanced",
     *     summary="Get all customers with advanced filtering",
     *     tags={"Customers (Enhanced)"},
     *
     *     @OA\Parameter(
     *         name="filter[name]",
     *         in="query",
     *         description="Filter by name (partial match)",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[status]",
     *         in="query",
     *         description="Filter by exact status",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"active", "inactive", "suspended"})
     *     ),
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Global search across name, email, phone, customer_code",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort by field(s). Use '-' prefix for descending. Example: -created_at,name",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="include",
     *         in="query",
     *         description="Eager load relations. Example: organization,branch,contacts",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="page[number]",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="page[size]",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/customers/enhanced",
     *     summary="Create a new customer",
     *     tags={"Customers (Enhanced)"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"type", "name", "email", "organization_id", "branch_id"},
     *
     *             @OA\Property(property="type", type="string", enum={"individual", "business"}),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="organization_id", type="integer"),
     *             @OA\Property(property="branch_id", type="integer"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Customer created successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function store(Request $request): JsonResponse
    {
        return parent::store($request);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/customers/enhanced/{id}",
     *     summary="Get customer by ID",
     *     tags={"Customers (Enhanced)"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="include",
     *         in="query",
     *         description="Eager load relations",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function show(int $id): JsonResponse
    {
        return parent::show($id);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/customers/enhanced/{id}",
     *     summary="Update customer",
     *     tags={"Customers (Enhanced)"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Customer updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        return parent::update($request, $id);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/customers/enhanced/{id}",
     *     summary="Delete customer",
     *     tags={"Customers (Enhanced)"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Customer deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }
}
