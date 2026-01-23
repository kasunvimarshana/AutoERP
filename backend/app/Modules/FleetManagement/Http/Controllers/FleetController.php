<?php

namespace App\Modules\FleetManagement\Http\Controllers;

use App\Core\Base\BaseController;
use OpenApi\Attributes as OA;
use App\Modules\FleetManagement\Services\FleetService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FleetController extends BaseController
{
    protected FleetService $fleetService;

    public function __construct(FleetService $fleetService)
    {
        $this->fleetService = $fleetService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $criteria = [
                'customer_id' => $request->input('customer_id'),
                'status' => $request->input('status'),
                'per_page' => $request->input('per_page', 15),
            ];

            $fleets = $this->fleetService->search($criteria);
            return $this->success($fleets);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'status' => 'nullable|in:active,inactive',
            ]);

            $fleet = $this->fleetService->create($request->all());
            return $this->created($fleet, 'Fleet created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $fleet = $this->fleetService->findById($id);
            
            if (!$fleet) {
                return $this->notFound('Fleet not found');
            }

            $fleet->load('vehicles');
            return $this->success($fleet);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'status' => 'sometimes|in:active,inactive',
            ]);

            $fleet = $this->fleetService->update($id, $request->all());
            return $this->success($fleet, 'Fleet updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->fleetService->delete($id);
            return $this->success(null, 'Fleet deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function addVehicle(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'vehicle_id' => 'required|exists:vehicles,id',
            ]);

            $fleet = $this->fleetService->addVehicle($id, $request->input('vehicle_id'));
            return $this->success($fleet, 'Vehicle added to fleet successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function removeVehicle(int $id, int $vehicleId): JsonResponse
    {
        try {
            $fleet = $this->fleetService->removeVehicle($id, $vehicleId);
            return $this->success($fleet, 'Vehicle removed from fleet successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
