<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Purchase\Application\DTOs\CreatePurchaseInvoiceDTO;
use Modules\Purchase\Application\UseCases\CreatePurchaseInvoiceAction;
use Modules\Purchase\Presentation\Http\Requests\UpsertPurchaseInvoiceRequest;
use Modules\Purchase\Presentation\Http\Resources\PurchaseOrderResource;
use Throwable;

final class PurchaseInvoiceController extends Controller
{
    public function __construct(private readonly CreatePurchaseInvoiceAction $action)
    {
    }

    public function store(UpsertPurchaseInvoiceRequest $request): JsonResponse|PurchaseOrderResource
    {
        try {
            $record = $this->action->execute(new CreatePurchaseInvoiceDTO($request->validated()));
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new PurchaseOrderResource($record))->response()->setStatusCode(201);
    }

    public function approve(int|string $id): JsonResponse|PurchaseOrderResource
    {
        try {
            return new PurchaseOrderResource($this->action->approve((int) $id));
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
