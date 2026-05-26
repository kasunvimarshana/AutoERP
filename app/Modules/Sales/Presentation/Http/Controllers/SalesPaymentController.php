<?php

declare(strict_types=1);

namespace Modules\Sales\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sales\Domain\Services\SalesLifecycleService;
use Modules\Sales\Presentation\Http\Resources\SalesOrderResource;
use Throwable;

final class SalesPaymentController extends Controller
{
    public function __construct(private readonly SalesLifecycleService $lifecycle)
    {
    }

    public function store(Request $request): JsonResponse|SalesOrderResource
    {
        try {
            return (new SalesOrderResource($this->lifecycle->createPayment($request->all())))->response()->setStatusCode(201);
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
