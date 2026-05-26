<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Purchase\Application\DTOs\CreateAdvancePaymentDTO;
use Modules\Purchase\Application\UseCases\CreateAdvancePaymentAction;
use Modules\Purchase\Presentation\Http\Requests\AllocateAdvancePaymentRequest;
use Modules\Purchase\Presentation\Http\Requests\UpsertAdvancePaymentRequest;
use Modules\Purchase\Presentation\Http\Resources\PurchaseOrderResource;
use Throwable;

final class PurchaseAdvancePaymentController extends Controller
{
    public function __construct(private readonly CreateAdvancePaymentAction $action)
    {
    }

    public function store(UpsertAdvancePaymentRequest $request): JsonResponse|PurchaseOrderResource
    {
        try {
            $record = $this->action->execute(new CreateAdvancePaymentDTO($request->validated()));
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new PurchaseOrderResource($record))->response()->setStatusCode(201);
    }

    public function allocate(AllocateAdvancePaymentRequest $request, int|string $id): JsonResponse|PurchaseOrderResource
    {
        try {
            return new PurchaseOrderResource($this->action->allocate((int) $id, $request->validated()));
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
