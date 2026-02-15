<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\POS;

use App\Http\Controllers\BaseController;
use App\Modules\POS\Services\POSService;
use App\Modules\POS\Http\Resources\POSTransactionResource;
use App\Modules\POS\Http\Requests\CreateTransactionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class POSTransactionController extends BaseController
{
    protected POSService $posService;

    public function __construct(POSService $posService)
    {
        $this->posService = $posService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 15);
            $transactions = $this->posService->getAll([], $perPage);

            return $this->paginatedResponse(
                POSTransactionResource::collection($transactions),
                'Transactions retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve transactions: ' . $e->getMessage());
        }
    }

    public function store(CreateTransactionRequest $request): JsonResponse
    {
        try {
            $transaction = $this->posService->createTransaction($request->validated());

            return $this->successResponse(
                new POSTransactionResource($transaction),
                'Transaction created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create transaction: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $transaction = $this->posService->getById($id);

            return $this->successResponse(
                new POSTransactionResource($transaction),
                'Transaction retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve transaction: ' . $e->getMessage(), [], 404);
        }
    }

    public function complete(int $id): JsonResponse
    {
        try {
            $transaction = $this->posService->completeTransaction($id);

            return $this->successResponse(
                new POSTransactionResource($transaction),
                'Transaction completed successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to complete transaction: ' . $e->getMessage());
        }
    }

    public function generateReceipt(Request $request, int $id): JsonResponse
    {
        try {
            $format = $request->input('format', 'pdf');
            $receipt = $this->posService->generateReceipt($id, $format);

            return $this->successResponse($receipt, 'Receipt generated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to generate receipt: ' . $e->getMessage());
        }
    }
}
