<?php

namespace App\Modules\Billing\Http\Controllers;

use App\Core\BaseController;
use App\Modules\Billing\Services\InvoiceItemService;
use App\Modules\Billing\Requests\CreateInvoiceItemRequest;
use App\Modules\Billing\Requests\UpdateInvoiceItemRequest;
use App\Modules\Billing\Resources\InvoiceItemResource;
use Illuminate\Http\JsonResponse;

class InvoiceItemController extends BaseController
{
    protected InvoiceItemService $service;

    public function __construct(InvoiceItemService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $items = $this->service->getAllInvoiceItems();
        return $this->collection(InvoiceItemResource::collection($items));
    }

    public function store(CreateInvoiceItemRequest $request): JsonResponse
    {
        $item = $this->service->createInvoiceItem($request->validated());
        return $this->created(new InvoiceItemResource($item));
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->service->getInvoiceItem($id);
        if (!$item) {
            return $this->notFound();
        }
        return $this->resource(new InvoiceItemResource($item));
    }

    public function update(UpdateInvoiceItemRequest $request, int $id): JsonResponse
    {
        $result = $this->service->updateInvoiceItem($id, $request->validated());
        if (!$result) {
            return $this->notFound();
        }
        return $this->success(null, 'Invoice item updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteInvoiceItem($id);
        if (!$result) {
            return $this->notFound();
        }
        return $this->success(null, 'Invoice item deleted successfully');
    }
}
