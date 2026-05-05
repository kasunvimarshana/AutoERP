<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\ReturnRefund\Application\Contracts\ProcessReturnAndRefundServiceInterface;
use Modules\ReturnRefund\Application\DTOs\ProcessReturnInput;
use Modules\ReturnRefund\Infrastructure\Http\Requests\ProcessReturnRefundRequest;
use Modules\ReturnRefund\Infrastructure\Http\Resources\ProcessReturnRefundResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ReturnRefundController
{
    public function __construct(private readonly ProcessReturnAndRefundServiceInterface $service)
    {
    }

    public function process(ProcessReturnRefundRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->execute(new ProcessReturnInput(
            tenantId: (string) $validated['tenant_id'],
            rentalTransactionId: (string) $validated['rental_transaction_id'],
            grossAmount: number_format((float) $validated['gross_amount'], 6, '.', ''),
            isDamaged: (bool) $validated['is_damaged'],
            damageNotes: (string) ($validated['damage_notes'] ?? ''),
            damageCharge: number_format((float) $validated['damage_charge'], 6, '.', ''),
            fuelAdjustmentCharge: number_format((float) $validated['fuel_adjustment_charge'], 6, '.', ''),
            lateReturnCharge: number_format((float) $validated['late_return_charge'], 6, '.', ''),
        ));

        return (new ProcessReturnRefundResource($result))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }
}
