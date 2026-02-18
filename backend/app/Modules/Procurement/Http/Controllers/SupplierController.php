<?php

namespace App\Modules\Procurement\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Procurement\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Supplier API Controller
 *
 * Handles HTTP requests for supplier management.
 */
class SupplierController extends BaseController
{
    public function __construct(
        protected SupplierService $supplierService
    ) {}

    /**
     * Display a listing of suppliers
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $suppliers = $this->supplierService->getAllSuppliers($perPage);

        return $this->successResponse($suppliers, 'Suppliers retrieved successfully');
    }

    /**
     * Store a newly created supplier
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'payment_terms_days' => 'nullable|integer|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $supplier = $this->supplierService->createSupplier($validated);

        return $this->createdResponse($supplier, 'Supplier created successfully');
    }

    /**
     * Display the specified supplier
     */
    public function show(int $id): JsonResponse
    {
        $supplier = $this->supplierService->getSupplierById($id);

        return $this->successResponse($supplier, 'Supplier retrieved successfully');
    }

    /**
     * Update the specified supplier
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'payment_terms_days' => 'nullable|integer|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $supplier = $this->supplierService->updateSupplier($id, $validated);

        return $this->successResponse($supplier, 'Supplier updated successfully');
    }

    /**
     * Remove the specified supplier
     */
    public function destroy(int $id): JsonResponse
    {
        $this->supplierService->deleteSupplier($id);

        return $this->successResponse(null, 'Supplier deleted successfully');
    }

    /**
     * Search suppliers
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->input('q', '');
        $suppliers = $this->supplierService->searchSuppliers($search);

        return $this->successResponse($suppliers, 'Search results retrieved successfully');
    }

    /**
     * Get active suppliers
     */
    public function active(): JsonResponse
    {
        $suppliers = $this->supplierService->getActiveSuppliers();

        return $this->successResponse($suppliers, 'Active suppliers retrieved successfully');
    }
}
