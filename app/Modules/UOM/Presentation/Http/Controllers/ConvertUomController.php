<?php

declare(strict_types=1);

namespace Modules\UOM\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use Modules\UOM\Domain\Constants\UomErrorCode;
use Modules\UOM\Presentation\Http\Requests\ConvertUomRequest;

final class ConvertUomController extends Controller
{
    public function __construct(
        private readonly UomConversionServiceInterface $conversionService,
    ) {
    }

    /**
     * POST api/uom/convert
     */
    public function __invoke(ConvertUomRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $tenantId = (int) $validated['tenant_id'];
        $quantity = (float) $validated['quantity'];
        $fromUomId = (int) $validated['from_uom_id'];
        $toUomId = (int) $validated['to_uom_id'];
        $itemId = isset($validated['item_id']) ? (int) $validated['item_id'] : null;

        $result = $this->conversionService->convert($quantity, $fromUomId, $toUomId, $tenantId, $itemId);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === UomErrorCode::NOT_FOUND ? 404 : 422;

            return response()->json(['message' => $error->message, 'code' => $error->code], $status);
        }

        return response()->json([
            'from_uom_id' => $fromUomId,
            'to_uom_id' => $toUomId,
            'quantity' => $quantity,
            'result' => $result->valueOrFail(),
            'item_id' => $itemId,
        ]);
    }
}
