<?php

declare(strict_types=1);

namespace Modules\Core\Presentation\API\Controllers;

use Illuminate\Http\JsonResponse;

final class HealthController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
        ]);
    }
}
