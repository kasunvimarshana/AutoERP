<?php

namespace App\Modules\POS\Http\Controllers;

use App\Core\BaseController;
use App\Modules\POS\Services\POSTransactionService;
use App\Modules\POS\Requests\CreatePOSTransactionRequest;
use App\Modules\POS\Requests\UpdatePOSTransactionRequest;
use App\Modules\POS\Requests\RefundPOSTransactionRequest;
use App\Modules\POS\Resources\POSTransactionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class POSTransactionController extends BaseController
{
    protected POSTransactionService $service;

    public function __construct(POSTransactionService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $transactions = $this->service->getAllTransactions();
        return $this->collection(POSTransactionResource::collection($transactions));
    }

    public function store(CreatePOSTransactionRequest $request): JsonResponse
    {
        $transaction = $this->service->createTransaction($request->validated());
        return $this->created(new POSTransactionResource($transaction));
    }

    public function show(int $id): JsonResponse
    {
        $transaction = $this->service->getTransaction($id);
        if (!$transaction) {
            return $this->notFound();
        }
        return $this->resource(new POSTransactionResource($transaction));
    }

    public function update(UpdatePOSTransactionRequest $request, int $id): JsonResponse
    {
        $result = $this->service->updateTransaction($id, $request->validated());
        if (!$result) {
            return $this->notFound();
        }
        return $this->success(null, 'POS Transaction updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteTransaction($id);
        if (!$result) {
            return $this->notFound();
        }
        return $this->noContent();
    }

    public function complete(int $id): JsonResponse
    {
        $result = $this->service->completeTransaction($id);
        if (!$result) {
            return $this->notFound();
        }
        return $this->success(null, 'POS Transaction completed successfully');
    }

    public function cancel(int $id): JsonResponse
    {
        $result = $this->service->cancelTransaction($id);
        if (!$result) {
            return $this->notFound();
        }
        return $this->success(null, 'POS Transaction cancelled successfully');
    }

    public function refund(RefundPOSTransactionRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->service->refundTransaction($id, $request->input('reason'));
            if (!$result) {
                return $this->notFound();
            }
            return $this->success(null, 'POS Transaction refunded successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function getByStatus(Request $request): JsonResponse
    {
        $status = $request->input('status');
        $transactions = $this->service->getByStatus($status);
        return $this->collection(POSTransactionResource::collection($transactions));
    }

    public function getByCustomer(int $customerId): JsonResponse
    {
        $transactions = $this->service->getByCustomer($customerId);
        return $this->collection(POSTransactionResource::collection($transactions));
    }

    public function getByCashier(int $cashierId): JsonResponse
    {
        $transactions = $this->service->getByCashier($cashierId);
        return $this->collection(POSTransactionResource::collection($transactions));
    }

    public function getByDateRange(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $transactions = $this->service->getByDateRange($startDate, $endDate);
        return $this->collection(POSTransactionResource::collection($transactions));
    }

    public function getTotalSales(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $total = $this->service->getTotalSales($tenantId, $startDate, $endDate);
        return $this->success(['total_sales' => number_format((float) $total, 2, '.', '')]);
    }

    public function getSalesByPaymentMethod(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $sales = $this->service->getSalesByPaymentMethod($tenantId, $startDate, $endDate);
        return $this->success(['sales_by_payment_method' => $sales]);
    }
}
