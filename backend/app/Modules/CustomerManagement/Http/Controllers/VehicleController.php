<?php

namespace App\Modules\CustomerManagement\Http\Controllers;

use App\Core\Base\BaseController;
use App\Modules\CustomerManagement\Http\Requests\StoreVehicleRequest;
use App\Modules\CustomerManagement\Services\VehicleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends BaseController
{
    protected VehicleService $vehicleService;

    public function __construct(VehicleService $vehicleService)
    {
        $this->vehicleService = $vehicleService;
    }

    /**
     * Display a listing of vehicles
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $criteria = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'customer_id' => $request->input('customer_id'),
                'service_due' => $request->input('service_due'),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'per_page' => $request->input('per_page', 15),
            ];

            $vehicles = $this->vehicleService->search($criteria);

            return $this->success($vehicles);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created vehicle
     */
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['tenant_id'] = $request->user()->tenant_id ?? null;
            $data['ownership_start_date'] = now();

            $vehicle = $this->vehicleService->create($data);

            return $this->created($vehicle, 'Vehicle created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified vehicle
     */
    public function show(int $id): JsonResponse
    {
        try {
            $vehicle = $this->vehicleService->findByIdOrFail($id);
            $vehicle->load(['currentCustomer', 'ownershipHistory']);

            return $this->success($vehicle);
        } catch (\Exception $e) {
            return $this->notFound('Vehicle not found');
        }
    }

    /**
     * Update the specified vehicle
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $vehicle = $this->vehicleService->update($id, $request->all());

            return $this->success($vehicle, 'Vehicle updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Transfer vehicle ownership
     */
    public function transferOwnership(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'new_customer_id' => 'required|exists:customers,id',
                'reason' => 'nullable|in:sale,gift,trade,inheritance,other',
                'notes' => 'nullable|string',
            ]);

            $vehicle = $this->vehicleService->transferOwnership(
                $id,
                $request->input('new_customer_id'),
                $request->only(['reason', 'notes'])
            );

            return $this->success($vehicle, 'Vehicle ownership transferred successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Update vehicle mileage
     */
    public function updateMileage(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'mileage' => 'required|numeric|min:0',
            ]);

            $vehicle = $this->vehicleService->updateMileage($id, $request->input('mileage'));

            return $this->success($vehicle, 'Vehicle mileage updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
