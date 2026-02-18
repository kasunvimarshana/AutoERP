<?php

declare(strict_types=1);

namespace Modules\Organization\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Organization\Http\Requests\StoreLocationRequest;
use Modules\Organization\Http\Requests\UpdateLocationRequest;
use Modules\Organization\Http\Resources\LocationResource;
use Modules\Organization\Services\LocationService;
use Modules\Organization\Repositories\Contracts\LocationRepositoryInterface;

class LocationController extends Controller
{
    public function __construct(
        private LocationService $locationService,
        private LocationRepositoryInterface $locationRepository
    ) {}

    /**
     * Display a listing of locations.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $locations = $this->locationRepository->paginate($perPage);

        return response()->json(LocationResource::collection($locations));
    }

    /**
     * Store a newly created location.
     */
    public function store(StoreLocationRequest $request): JsonResponse
    {
        try {
            $location = $this->locationService->createLocation($request->validated());
            
            return response()->json(
                new LocationResource($location),
                201
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create location',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified location.
     */
    public function show(int $id): JsonResponse
    {
        $location = $this->locationRepository->findById($id);

        if (!$location) {
            return response()->json([
                'message' => 'Location not found'
            ], 404);
        }

        return response()->json(new LocationResource($location));
    }

    /**
     * Update the specified location.
     */
    public function update(UpdateLocationRequest $request, int $id): JsonResponse
    {
        try {
            $location = $this->locationService->updateLocation($id, $request->validated());
            
            return response()->json(new LocationResource($location));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update location',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove the specified location.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->locationService->deleteLocation($id);
            
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete location',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get children of a location.
     */
    public function children(int $id): JsonResponse
    {
        $location = $this->locationRepository->findById($id);

        if (!$location) {
            return response()->json([
                'message' => 'Location not found'
            ], 404);
        }

        return response()->json(
            LocationResource::collection($location->children)
        );
    }

    /**
     * Get locations by organization.
     */
    public function byOrganization(int $organizationId): JsonResponse
    {
        $locations = $this->locationRepository->getByOrganization($organizationId);
        
        return response()->json(
            LocationResource::collection($locations)
        );
    }
}
