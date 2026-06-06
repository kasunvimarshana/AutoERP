<?php

declare(strict_types=1);

namespace Modules\UOM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\UOM\Constants\UomErrorCode;
use Modules\UOM\Contracts\Services\UomConversionServiceInterface;
use Modules\UOM\Http\Requests\ConvertUomRequest;

final class ConvertUomController extends Controller
{
    public function __construct(
        private readonly UomConversionServiceInterface $conversionService,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
    ) {}

    /**
     * POST api/uom/convert
     */
    public function __invoke(ConvertUomRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $tenantId = (int) ($validated['tenant_id'] ?? $this->currentTenant->currentTenantId());
        if ($tenantId <= 0) {
            return response()->json([
                'message' => 'Tenant context is required.',
                'code' => UomErrorCode::INVALID_VALUE,
            ], 422);
        }

        $quantity = (float) $validated['quantity'];
        $fromUomId = (int) $validated['from_uom_id'];
        $toUomId = (int) $validated['to_uom_id'];
        $itemId = isset($validated['item_id']) ? (int) $validated['item_id'] : null;

        $factorResult = $this->conversionService->getConversionFactor($fromUomId, $toUomId, $tenantId, $itemId);
        if ($factorResult->isFailure()) {
            $error = $factorResult->errorOrFail();
            $status = $error->code === UomErrorCode::NOT_FOUND ? 404 : 422;

            return response()->json(['message' => $error->message, 'code' => $error->code], $status);
        }

        $result = $this->conversionService->convert($quantity, $fromUomId, $toUomId, $tenantId, $itemId);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === UomErrorCode::NOT_FOUND ? 404 : 422;

            return response()->json(['message' => $error->message, 'code' => $error->code], $status);
        }

        return response()->json([
            'input' => [
                'from_uom_id' => $fromUomId,
                'to_uom_id' => $toUomId,
                'quantity' => $quantity,
                'item_id' => $itemId,
            ],
            'calculated' => [
                'converted_quantity' => $result->valueOrFail(),
                'factor' => $factorResult->valueOrFail(),
                'precision' => 10,
            ],
            'breakdown' => [],
            'warnings' => [],
            'errors' => [],
        ]);
    }
}
