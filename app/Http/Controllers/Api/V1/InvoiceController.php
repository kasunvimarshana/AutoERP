<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\CreateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService
    ) {}

    public function index(Request $request): ResourceCollection
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['status']);

        return InvoiceResource::collection($this->invoiceService->paginate($tenantId, $filters, $perPage));
    }

    public function store(CreateInvoiceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->tenant_id;

        return (new InvoiceResource($this->invoiceService->create($data)))->response()->setStatusCode(201);
    }

    public function send(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('invoices.send'), 403);

        return (new InvoiceResource($this->invoiceService->send($id)))->response();
    }

    public function void(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('invoices.void'), 403);

        return (new InvoiceResource($this->invoiceService->void($id)))->response();
    }
}
