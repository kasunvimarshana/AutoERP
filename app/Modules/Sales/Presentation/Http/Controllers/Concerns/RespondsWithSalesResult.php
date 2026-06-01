<?php

declare(strict_types=1);

namespace Modules\Sales\Presentation\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Modules\Core\Application\Results\Result;
use Modules\Sales\Domain\Constants\SalesErrorCode;

trait RespondsWithSalesResult
{
    private function respond(Result $result): JsonResponse
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $notFoundCodes = [SalesErrorCode::NOT_FOUND, 'PAYMENT_NOT_FOUND'];

            return response()->json([
                'message' => $error->message,
                'code' => $error->code,
            ], in_array($error->code, $notFoundCodes, true) ? 404 : 422);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }
}
