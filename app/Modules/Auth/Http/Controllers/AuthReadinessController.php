<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Services\Readiness\AuthReadinessService;

final class AuthReadinessController extends Controller
{
    public function __invoke(AuthReadinessService $readiness): JsonResponse
    {
        $result = $readiness->inspect();

        return response()->json(['data' => $result]);
    }
}
