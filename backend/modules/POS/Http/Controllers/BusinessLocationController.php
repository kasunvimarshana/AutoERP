<?php

declare(strict_types=1);

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\POS\Services\BusinessLocationService;
use Modules\POS\Repositories\BusinessLocationRepository;

class BusinessLocationController extends BaseController
{
    public function __construct(
        private BusinessLocationService $locationService,
        private BusinessLocationRepository $locationRepository
    ) {}

    public function index(): JsonResponse
    {
        $locations = $this->locationRepository->all();
        return $this->success($locations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:pos_business_locations,code',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'invoice_scheme_id' => 'nullable|uuid|exists:pos_invoice_schemes,id',
            'invoice_layout_id' => 'nullable|uuid|exists:pos_invoice_layouts,id',
            'is_active' => 'nullable|boolean',
            'settings' => 'nullable|array',
        ]);

        $location = $this->locationService->create($validated);

        return $this->success($location, 'Business location created successfully', 201);
    }

    public function show(string $id): JsonResponse
    {
        $location = $this->locationRepository->findById($id);

        if (!$location) {
            return $this->error('Business location not found', 404);
        }

        return $this->success($location);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $location = $this->locationRepository->findById($id);

        if (!$location) {
            return $this->error('Business location not found', 404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:50|unique:pos_business_locations,code,' . $id,
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'invoice_scheme_id' => 'nullable|uuid|exists:pos_invoice_schemes,id',
            'invoice_layout_id' => 'nullable|uuid|exists:pos_invoice_layouts,id',
            'is_active' => 'nullable|boolean',
            'settings' => 'nullable|array',
        ]);

        $updated = $this->locationService->update($location, $validated);

        return $this->success($updated, 'Business location updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $location = $this->locationRepository->findById($id);

        if (!$location) {
            return $this->error('Business location not found', 404);
        }

        $this->locationService->delete($location);

        return $this->success(null, 'Business location deleted successfully');
    }

    public function active(): JsonResponse
    {
        $locations = $this->locationService->getActive();
        return $this->success($locations);
    }
}
