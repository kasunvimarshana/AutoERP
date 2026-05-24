<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Purchase\Application\Services\PurchaseService;
use Modules\Purchase\Domain\Exceptions\PurchaseRecordNotFoundException;
use Modules\Purchase\Presentation\Http\Resources\PurchaseRecordResource;

class PurchaseRecalculationController extends Controller
{
    public function __construct(private readonly PurchaseService $purchases) {}

    public function purchaseOrder(int|string $tenant, int|string $purchaseOrder): PurchaseRecordResource|JsonResponse
    {
        try {
            return new PurchaseRecordResource($this->purchases->recalculatePurchaseOrder($tenant, $purchaseOrder));
        } catch (PurchaseRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function grnHeader(int|string $tenant, int|string $grnHeader): PurchaseRecordResource|JsonResponse
    {
        try {
            return new PurchaseRecordResource($this->purchases->recalculateGrnHeader($tenant, $grnHeader));
        } catch (PurchaseRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function purchaseReturn(int|string $tenant, int|string $purchaseReturn): PurchaseRecordResource|JsonResponse
    {
        try {
            return new PurchaseRecordResource($this->purchases->recalculatePurchaseReturn($tenant, $purchaseReturn));
        } catch (PurchaseRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }
}
