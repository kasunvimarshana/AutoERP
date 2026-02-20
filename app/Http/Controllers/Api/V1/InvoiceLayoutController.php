<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\InvoiceLayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceLayoutController extends Controller
{
    public function __construct(
        private readonly InvoiceLayoutService $invoiceLayoutService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['search']);

        return response()->json($this->invoiceLayoutService->paginate($tenantId, $filters, $perPage));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'header_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'footer_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'show_business_name' => ['sometimes', 'boolean'],
            'show_location_name' => ['sometimes', 'boolean'],
            'show_mobile_number' => ['sometimes', 'boolean'],
            'show_address' => ['sometimes', 'boolean'],
            'show_email' => ['sometimes', 'boolean'],
            'show_tax_1' => ['sometimes', 'boolean'],
            'show_tax_2' => ['sometimes', 'boolean'],
            'show_barcode' => ['sometimes', 'boolean'],
            'show_customer' => ['sometimes', 'boolean'],
            'show_client_id' => ['sometimes', 'boolean'],
            'show_credit_limit' => ['sometimes', 'boolean'],
            'show_expiry_date' => ['sometimes', 'boolean'],
            'show_lot_number' => ['sometimes', 'boolean'],
            'design' => ['sometimes', 'string', 'in:classic,modern,simple,receipt'],
            'invoice_no_prefix' => ['sometimes', 'nullable', 'string', 'max:20'],
            'cn_no_prefix' => ['sometimes', 'nullable', 'string', 'max:20'],
            'is_default' => ['sometimes', 'boolean'],
            'module_info' => ['sometimes', 'nullable', 'array'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json($this->invoiceLayoutService->create($data), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'header_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'footer_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'show_business_name' => ['sometimes', 'boolean'],
            'show_location_name' => ['sometimes', 'boolean'],
            'show_mobile_number' => ['sometimes', 'boolean'],
            'show_address' => ['sometimes', 'boolean'],
            'show_email' => ['sometimes', 'boolean'],
            'show_tax_1' => ['sometimes', 'boolean'],
            'show_tax_2' => ['sometimes', 'boolean'],
            'show_barcode' => ['sometimes', 'boolean'],
            'show_customer' => ['sometimes', 'boolean'],
            'show_client_id' => ['sometimes', 'boolean'],
            'show_credit_limit' => ['sometimes', 'boolean'],
            'show_expiry_date' => ['sometimes', 'boolean'],
            'show_lot_number' => ['sometimes', 'boolean'],
            'design' => ['sometimes', 'string', 'in:classic,modern,simple,receipt'],
            'invoice_no_prefix' => ['sometimes', 'nullable', 'string', 'max:20'],
            'cn_no_prefix' => ['sometimes', 'nullable', 'string', 'max:20'],
            'is_default' => ['sometimes', 'boolean'],
            'module_info' => ['sometimes', 'nullable', 'array'],
        ]);

        return response()->json($this->invoiceLayoutService->update($id, $data));
    }

    public function setDefault(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);
        $tenantId = $request->user()->tenant_id;

        return response()->json($this->invoiceLayoutService->setDefault($id, $tenantId));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage'), 403);
        $this->invoiceLayoutService->delete($id);

        return response()->json(null, 204);
    }
}
