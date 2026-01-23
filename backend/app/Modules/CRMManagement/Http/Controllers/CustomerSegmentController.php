<?php

namespace App\Modules\CRMManagement\Http\Controllers;

use App\Core\Base\BaseController;
use App\Modules\CRMManagement\Services\CustomerSegmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerSegmentController extends BaseController
{
    protected CustomerSegmentService $segmentService;

    public function __construct(CustomerSegmentService $segmentService)
    {
        $this->segmentService = $segmentService;
    }

    public function index(): JsonResponse
    {
        try {
            $segments = $this->segmentService->getAll();
            return $this->success($segments);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'criteria' => 'required|array',
            ]);

            $segment = $this->segmentService->create($request->all());
            return $this->created($segment, 'Customer segment created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $segment = $this->segmentService->findById($id);
            
            if (!$segment) {
                return $this->notFound('Customer segment not found');
            }

            return $this->success($segment);
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
                'criteria' => 'sometimes|array',
            ]);

            $segment = $this->segmentService->update($id, $request->all());
            return $this->success($segment, 'Customer segment updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->segmentService->delete($id);
            return $this->success(null, 'Customer segment deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
