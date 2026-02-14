<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Core\BaseController;
use App\Modules\CRM\Services\VehicleService;
use App\Modules\CRM\Requests\CreateVehicleRequest;
use App\Modules\CRM\Requests\UpdateVehicleRequest;
use App\Modules\CRM\Resources\VehicleResource;
use Illuminate\Http\JsonResponse;

/**
 * Vehicle Controller
 * 
 * Handles CRUD operations for vehicle management
 * 
 * @package App\Modules\CRM\Http\Controllers
 */
class VehicleController extends BaseController
{
    protected VehicleService $service;

    public function __construct(VehicleService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of vehicles
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $vehicles = $this->service->getAllVehicles();
        return $this->collection(VehicleResource::collection($vehicles));
    }

    /**
     * Store a newly created vehicle
     *
     * @param CreateVehicleRequest $request
     * @return JsonResponse
     */
    public function store(CreateVehicleRequest $request): JsonResponse
    {
        $vehicle = $this->service->createVehicle($request->validated());
        return $this->created(new VehicleResource($vehicle));
    }

    /**
     * Display the specified vehicle
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $vehicle = $this->service->getVehicle($id);
        
        if (!$vehicle) {
            return $this->notFound();
        }
        
        return $this->resource(new VehicleResource($vehicle));
    }

    /**
     * Update the specified vehicle
     *
     * @param UpdateVehicleRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateVehicleRequest $request, int $id): JsonResponse
    {
        $result = $this->service->updateVehicle($id, $request->validated());
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->success(null, 'Vehicle updated successfully');
    }

    /**
     * Remove the specified vehicle
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteVehicle($id);
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->noContent();
    }
}
