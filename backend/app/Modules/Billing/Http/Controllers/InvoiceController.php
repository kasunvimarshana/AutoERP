<?php

namespace App\Modules\Billing\Http\Controllers;

use App\Core\BaseController;
use App\Modules\Billing\Services\InvoiceService;
use App\Modules\Billing\Requests\CreateInvoiceRequest;
use App\Modules\Billing\Requests\UpdateInvoiceRequest;
use App\Modules\Billing\Resources\InvoiceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends BaseController
{
    protected InvoiceService $service;

    public function __construct(InvoiceService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $invoices = $this->service->getAllInvoices();
        return $this->collection(InvoiceResource::collection($invoices));
    }

    public function store(CreateInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->service->createInvoice($request->validated());
        return $this->created(new InvoiceResource($invoice));
    }

    public function show(int $id): JsonResponse
    {
        $invoice = $this->service->getInvoice($id);
        if (!$invoice) {
            return $this->notFound();
        }
        return $this->resource(new InvoiceResource($invoice));
    }

    public function update(UpdateInvoiceRequest $request, int $id): JsonResponse
    {
        $result = $this->service->updateInvoice($id, $request->validated());
        if (!$result) {
            return $this->notFound();
        }
        return $this->success(null, 'Invoice updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteInvoice($id);
        if (!$result) {
            return $this->notFound();
        }
        return $this->success(null, 'Invoice deleted successfully');
    }

    public function send(int $id): JsonResponse
    {
        $result = $this->service->sendInvoice($id);
        if (!$result) {
            return $this->error('Unable to send invoice', 400);
        }
        return $this->success(null, 'Invoice sent successfully');
    }

    public function markAsPaid(Request $request, int $id): JsonResponse
    {
        $result = $this->service->markAsPaid($id, $request->all());
        if (!$result) {
            return $this->error('Unable to mark invoice as paid', 400);
        }
        return $this->success(null, 'Invoice marked as paid successfully');
    }
}
