<?php

namespace App\Modules\Appointments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Appointments\Http\Requests\ServiceBayRequest;
use App\Modules\Appointments\Http\Resources\ServiceBayResource;
use App\Modules\Appointments\Services\ServiceBayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceBayController extends Controller
{
    public function __construct(
        private ServiceBayService $serviceBayService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);
        $serviceBays = $this->serviceBayService->paginate($perPage);
        
        return ServiceBayResource::collection($serviceBays);
    }

    public function store(ServiceBayRequest $request): JsonResponse
    {
        $serviceBay = $this->serviceBayService->create($request->validated());
        
        return response()->json([
            'message' => 'Service bay created successfully',
            'data' => new ServiceBayResource($serviceBay)
        ], 201);
    }

    public function show(int $id): ServiceBayResource
    {
        $serviceBay = $this->serviceBayService->findById($id);
        
        return new ServiceBayResource($serviceBay);
    }

    public function update(ServiceBayRequest $request, int $id): JsonResponse
    {
        $serviceBay = $this->serviceBayService->update($id, $request->validated());
        
        return response()->json([
            'message' => 'Service bay updated successfully',
            'data' => new ServiceBayResource($serviceBay)
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->serviceBayService->delete($id);
        
        return response()->json([
            'message' => 'Service bay deleted successfully'
        ]);
    }
}
