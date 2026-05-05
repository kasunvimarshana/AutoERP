<?php declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Rental\Application\Contracts\ManageRentalTransactionServiceInterface;
use Modules\Rental\Infrastructure\Http\Requests\CheckInRentalTransactionRequest;
use Modules\Rental\Infrastructure\Http\Requests\CheckOutRentalTransactionRequest;
use Modules\Rental\Infrastructure\Http\Resources\RentalTransactionResource;

class RentalTransactionController extends Controller
{
    public function __construct(
        private readonly ManageRentalTransactionServiceInterface $service,
    ) {}

    public function checkOut(CheckOutRentalTransactionRequest $request): JsonResponse
    {
        $transaction = $this->service->checkOut($request->validated());
        return response()->json(new RentalTransactionResource($transaction), 201);
    }

    public function checkIn(CheckInRentalTransactionRequest $request, string $transactionId): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $transaction = $this->service->checkIn($tenantId, $transactionId, $request->validated());
        return response()->json(new RentalTransactionResource($transaction));
    }

    public function open(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $transactions = $this->service->getOpen($tenantId);
        return response()->json(['data' => $transactions]);
    }
}
