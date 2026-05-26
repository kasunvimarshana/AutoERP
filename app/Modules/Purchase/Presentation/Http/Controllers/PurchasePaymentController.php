<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Purchase\Application\DTOs\CreatePurchasePaymentDTO;
use Modules\Purchase\Application\UseCases\CreatePurchasePaymentAction;
use Modules\Purchase\Presentation\Http\Requests\UpsertPurchasePaymentRequest;
use Modules\Purchase\Presentation\Http\Resources\PurchaseOrderResource;
use Throwable;

final class PurchasePaymentController extends Controller
{
    public function __construct(private readonly CreatePurchasePaymentAction $action)
    {
    }

    public function store(UpsertPurchasePaymentRequest $request): JsonResponse|PurchaseOrderResource
    {
        try {
            $record = $this->action->execute(new CreatePurchasePaymentDTO($request->validated()));
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new PurchaseOrderResource($record))->response()->setStatusCode(201);
    }
}
