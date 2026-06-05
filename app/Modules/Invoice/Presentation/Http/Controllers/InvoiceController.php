<?php

declare(strict_types=1);

namespace Modules\Invoice\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\Invoice\Application\Services\InvoiceService;
use Modules\Invoice\Presentation\Http\Requests\ListInvoiceRequest;
use Modules\Invoice\Presentation\Http\Requests\UpsertInvoiceRequest;
use Modules\Invoice\Presentation\Http\Resources\InvoiceResource;

final class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices) {}

    public function index(ListInvoiceRequest $request): AnonymousResourceCollection
    {
        return InvoiceResource::collection($this->invoices->paginate($request->validated()));
    }

    public function show(int $invoice): InvoiceResource
    {
        return new InvoiceResource($this->invoices->find($invoice));
    }

    public function store(UpsertInvoiceRequest $request): JsonResponse
    {
        return (new InvoiceResource($this->invoices->create($request->validated())))->response()->setStatusCode(201);
    }

    public function update(UpsertInvoiceRequest $request, int $invoice): InvoiceResource
    {
        return new InvoiceResource($this->invoices->update($invoice, $request->validated()));
    }

    public function issue(int $invoice): InvoiceResource
    {
        return new InvoiceResource($this->invoices->issue($invoice));
    }

    public function cancel(int $invoice): InvoiceResource
    {
        return new InvoiceResource($this->invoices->cancel($invoice, request('reason')));
    }

    public function destroy(int $invoice): JsonResponse
    {
        $this->invoices->delete($invoice);

        return response()->json(null, 204);
    }
}
