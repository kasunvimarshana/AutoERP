<?php

namespace App\Modules\CRMManagement\Http\Controllers;

use App\Core\Base\BaseController;
use OpenApi\Attributes as OA;
use App\Modules\CRMManagement\Services\CommunicationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CommunicationController extends BaseController
{
    protected CommunicationService $communicationService;

    public function __construct(CommunicationService $communicationService)
    {
        $this->communicationService = $communicationService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $criteria = [
                'customer_id' => $request->input('customer_id'),
                'channel' => $request->input('channel'),
                'status' => $request->input('status'),
                'per_page' => $request->input('per_page', 15),
            ];

            $communications = $this->communicationService->search($criteria);
            return $this->success($communications);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'channel' => 'required|in:email,sms,whatsapp,phone,in_app',
                'subject' => 'nullable|string|max:500',
                'message' => 'required|string',
                'status' => 'nullable|in:pending,sent,failed,delivered',
                'scheduled_at' => 'nullable|date',
            ]);

            $communication = $this->communicationService->create($request->all());
            return $this->created($communication, 'Communication created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $communication = $this->communicationService->findById($id);
            
            if (!$communication) {
                return $this->notFound('Communication not found');
            }

            return $this->success($communication);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->communicationService->delete($id);
            return $this->success(null, 'Communication deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
