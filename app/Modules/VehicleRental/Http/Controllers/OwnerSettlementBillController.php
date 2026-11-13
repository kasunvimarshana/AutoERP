<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Invoice\Http\Resources\InvoiceResource;
use Modules\VehicleRental\Http\Requests\OwnerSettlementBillRequest;
use Modules\VehicleRental\Services\OwnerSettlementBillingService;
use Symfony\Component\HttpFoundation\Response;

final class OwnerSettlementBillController
{
    public function __construct(private readonly OwnerSettlementBillingService $billingService) {}

    public function store(OwnerSettlementBillRequest $request): JsonResponse
    {
        $agreementId = (int) $request->input('owner_agreement_id');
        $invoice = $this->billingService->generateOwnerSettlementBill(
            $request->context(),
            $agreementId,
            $request->all()
        );

        return (new InvoiceResource($invoice))->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
