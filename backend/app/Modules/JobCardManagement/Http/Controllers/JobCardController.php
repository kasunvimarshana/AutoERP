<?php

namespace App\Modules\JobCardManagement\Http\Controllers;

use App\Core\Base\BaseController;
use App\Modules\JobCardManagement\Services\JobCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobCardController extends BaseController
{
    protected JobCardService $jobCardService;

    public function __construct(JobCardService $jobCardService)
    {
        $this->jobCardService = $jobCardService;
    }

    /**
     * Display a listing of job cards
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $criteria = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'customer_id' => $request->input('customer_id'),
                'vehicle_id' => $request->input('vehicle_id'),
                'technician_id' => $request->input('technician_id'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'per_page' => $request->input('per_page', 15),
            ];

            $jobCards = $this->jobCardService->search($criteria);

            return $this->success($jobCards);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created job card
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            $data['tenant_id'] = $request->user()->tenant_id ?? null;

            $jobCard = $this->jobCardService->create($data);

            return $this->created($jobCard, 'Job card created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified job card
     */
    public function show(int $id): JsonResponse
    {
        try {
            $jobCard = $this->jobCardService->findByIdOrFail($id);
            $jobCard->load(['customer', 'vehicle', 'appointment', 'services', 'parts']);

            return $this->success($jobCard);
        } catch (\Exception $e) {
            return $this->notFound('Job card not found');
        }
    }

    /**
     * Update the specified job card
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $jobCard = $this->jobCardService->update($id, $request->all());

            return $this->success($jobCard, 'Job card updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified job card
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->jobCardService->delete($id);

            return $this->success(null, 'Job card deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Open a job card
     */
    public function open(int $id): JsonResponse
    {
        try {
            $jobCard = $this->jobCardService->open($id);

            return $this->success($jobCard, 'Job card opened successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Close a job card
     */
    public function close(int $id): JsonResponse
    {
        try {
            $jobCard = $this->jobCardService->close($id);

            return $this->success($jobCard, 'Job card closed successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Assign a technician to job card
     */
    public function assign(Request $request, int $id): JsonResponse
    {
        try {
            $jobCard = $this->jobCardService->assign($id, $request->input('technician_id'));

            return $this->success($jobCard, 'Job card assigned successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
