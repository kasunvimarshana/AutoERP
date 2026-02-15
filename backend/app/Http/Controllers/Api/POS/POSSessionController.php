<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\POS;

use App\Http\Controllers\BaseController;
use App\Modules\POS\Services\POSService;
use App\Modules\POS\Http\Resources\POSSessionResource;
use App\Modules\POS\Http\Requests\OpenSessionRequest;
use App\Modules\POS\Http\Requests\CloseSessionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class POSSessionController extends BaseController
{
    protected POSService $posService;

    public function __construct(POSService $posService)
    {
        $this->posService = $posService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $sessions = $this->posService->sessionRepository->all();

            return $this->successResponse(
                POSSessionResource::collection($sessions),
                'Sessions retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve sessions: ' . $e->getMessage());
        }
    }

    public function store(OpenSessionRequest $request): JsonResponse
    {
        try {
            $session = $this->posService->openSession($request->validated());

            return $this->successResponse(
                new POSSessionResource($session),
                'Session opened successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to open session: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $session = $this->posService->sessionRepository->findById($id);

            return $this->successResponse(
                new POSSessionResource($session),
                'Session retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve session: ' . $e->getMessage(), [], 404);
        }
    }

    public function close(CloseSessionRequest $request, int $id): JsonResponse
    {
        try {
            $session = $this->posService->closeSession($id, $request->validated());

            return $this->successResponse(
                new POSSessionResource($session),
                'Session closed successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to close session: ' . $e->getMessage());
        }
    }

    public function current(Request $request): JsonResponse
    {
        try {
            $cashierId = $request->user()->id;
            $terminalId = $request->input('terminal_id');
            
            $session = $this->posService->sessionRepository->getCurrentOpenSession($cashierId, $terminalId);

            if (!$session) {
                return $this->errorResponse('No open session found', [], 404);
            }

            return $this->successResponse(
                new POSSessionResource($session),
                'Current session retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve current session: ' . $e->getMessage());
        }
    }
}
