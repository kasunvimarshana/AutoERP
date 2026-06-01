<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Modules\Core\Application\Results\Result;
use Modules\Purchase\Domain\Constants\PurchaseErrorCode;

trait RespondsWithPurchaseResult
{
    protected function respond(Result $result, bool $includeErrorCode = false): JsonResponse
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $payload = ['message' => $error->message];
            if ($includeErrorCode) {
                $payload['code'] = $error->code;
            }

            return response()->json(
                $payload,
                in_array($error->code, [PurchaseErrorCode::NOT_FOUND, 'PAYMENT_NOT_FOUND'], true) ? 404 : 422,
            );
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }
}
