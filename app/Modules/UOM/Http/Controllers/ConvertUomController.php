<?php

declare(strict_types=1);

namespace Modules\UOM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Services\DecimalMath;
use Modules\UOM\DTOs\UomConversionResultData;
use Modules\UOM\Constants\UomErrorCode;
use Modules\UOM\Contracts\Services\UomConversionServiceInterface;
use Modules\UOM\Http\Requests\ConvertUomRequest;
use Modules\UOM\Repositories\UnitOfMeasureRepositoryInterface;

final class ConvertUomController extends Controller
{
    public function __construct(
        private readonly UomConversionServiceInterface $conversionService,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly UnitOfMeasureRepositoryInterface $uoms,
        private readonly DecimalMath $math,
    ) {}

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

        $quantity = $this->math->normalize((string) $validated['quantity']);
        $fromUomId = (int) $validated['from_uom_id'];
        $toUomId = (int) $validated['to_uom_id'];

        $factorResult = $this->conversionService->getConversionFactor($fromUomId, $toUomId, $tenantId);
        if ($factorResult->isFailure()) {
            $error = $factorResult->errorOrFail();
            $status = $error->code === UomErrorCode::NOT_FOUND ? 404 : 422;

            return response()->json(['message' => $error->message, 'code' => $error->code], $status);
        }

        $result = $this->conversionService->convert($quantity, $fromUomId, $toUomId, $tenantId);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === UomErrorCode::NOT_FOUND ? 404 : 422;

            return response()->json(['message' => $error->message, 'code' => $error->code], $status);
        }

        $fromUom = $this->uoms->findByIdInTenant($fromUomId, $tenantId);
        $toUom = $this->uoms->findByIdInTenant($toUomId, $tenantId);

        return response()->json((new UomConversionResultData(
            quantity: $quantity,
            fromUom: $this->uomSummary($fromUom?->toArray() ?? []),
            toUom: $this->uomSummary($toUom?->toArray() ?? []),
            conversionFactor: (string) $factorResult->valueOrFail(),
            convertedQuantity: (string) $result->valueOrFail(),
        ))->toArray());
    }

    private function uomSummary(array $uom): array
    {
        return [
            'id' => $uom['id'] ?? null,
            'code' => $uom['code'] ?? null,
            'name' => $uom['name'] ?? null,
            'symbol' => $uom['symbol'] ?? null,
        ];
    }

}
