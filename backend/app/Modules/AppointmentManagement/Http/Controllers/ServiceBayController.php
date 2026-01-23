<?php

namespace App\Modules\AppointmentManagement\Http\Controllers;

use App\Core\Base\BaseController;
use App\Modules\AppointmentManagement\Services\ServiceBayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceBayController extends BaseController
{
    protected ServiceBayService $serviceBayService;

    public function __construct(ServiceBayService $serviceBayService)
    {
        $this->serviceBayService = $serviceBayService;
    }

    /**
     * Display a listing of service bays
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $criteria = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'per_page' => $request->input('per_page', 15),
            ];

            $serviceBays = $this->serviceBayService->search($criteria);

            return $this->success($serviceBays);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created service bay
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            $data['tenant_id'] = $request->user()->tenant_id ?? null;

            $serviceBay = $this->serviceBayService->create($data);

            return $this->created($serviceBay, 'Service bay created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified service bay
     */
    public function show(int $id): JsonResponse
    {
        try {
            $serviceBay = $this->serviceBayService->findByIdOrFail($id);

            return $this->success($serviceBay);
        } catch (\Exception $e) {
            return $this->notFound('Service bay not found');
        }
    }

    /**
     * Update the specified service bay
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $serviceBay = $this->serviceBayService->update($id, $request->all());

            return $this->success($serviceBay, 'Service bay updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified service bay
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->serviceBayService->delete($id);

            return $this->success(null, 'Service bay deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Check availability of service bay
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        try {
            $availability = $this->serviceBayService->checkAvailability(
                $request->input('service_bay_id'),
                $request->input('start_time'),
                $request->input('end_time')
            );

            return $this->success($availability);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
