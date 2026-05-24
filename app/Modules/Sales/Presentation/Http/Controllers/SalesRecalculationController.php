<?php

declare(strict_types=1);

namespace Modules\Sales\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Sales\Application\Services\SalesService;
use Modules\Sales\Domain\Exceptions\SalesRecordNotFoundException;
use Modules\Sales\Presentation\Http\Resources\SalesRecordResource;

class SalesRecalculationController extends Controller
{
    public function __construct(private readonly SalesService $sales) {}

    public function salesOrder(int|string $tenant, int|string $salesOrder): SalesRecordResource|JsonResponse
    {
        try {
            return new SalesRecordResource($this->sales->recalculateSalesOrder($tenant, $salesOrder));
        } catch (SalesRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function gdnHeader(int|string $tenant, int|string $gdnHeader): SalesRecordResource|JsonResponse
    {
        try {
            return new SalesRecordResource($this->sales->recalculateGdnHeader($tenant, $gdnHeader));
        } catch (SalesRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function salesReturn(int|string $tenant, int|string $salesReturn): SalesRecordResource|JsonResponse
    {
        try {
            return new SalesRecordResource($this->sales->recalculateSalesReturn($tenant, $salesReturn));
        } catch (SalesRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }
}
