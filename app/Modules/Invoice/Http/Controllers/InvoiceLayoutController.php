<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Invoice\Http\Requests\SetDefaultInvoiceLayoutRequest;
use Modules\Invoice\Http\Requests\StoreInvoiceLayoutRequest;
use Modules\Invoice\Http\Requests\UpdateInvoiceLayoutRequest;
use Modules\Invoice\Http\Requests\UploadInvoiceBladeTemplateRequest;
use Modules\Invoice\Http\Resources\InvoiceLayoutResource;
use Modules\Invoice\Services\InvoiceLayoutService;
use Modules\Invoice\Validators\InvoiceBladeSecurityValidator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class InvoiceLayoutController
{
    public function __construct(
        private readonly InvoiceLayoutService $layouts,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->currentTenantId($request);
        $organizationUnitId = $this->currentOrganizationUnitId($request);
        $search = $request->query('search');
        $paperSize = $request->query('paper_size');
        $isActive = $request->has('is_active')
            ? filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $items = $this->layouts->list(
            $tenantId,
            is_string($search) ? $search : null,
            is_string($paperSize) ? $paperSize : null,
            $isActive,
            $perPage,
        );

        $defaults = $this->layouts->getActiveDefaults($tenantId, $organizationUnitId);

        foreach ($items as $item) {
            $item->setAttribute('is_tenant_default', $item->code === $defaults['tenant_default']);
            $item->setAttribute('is_branch_default', $defaults['branch_default'] !== null && $item->code === $defaults['branch_default']);
        }

        return response()->json([
            'data' => InvoiceLayoutResource::collection($items->items()),
            'meta' => [
                'defaults' => $defaults,
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function show(Request $request, int $layout): JsonResponse
    {
        $tenantId = $this->currentTenantId($request);
        $model = $this->layouts->find($tenantId, $layout);

        if ($model === null) {
            throw new NotFoundHttpException('Invoice layout not found.');
        }

        return response()->json([
            'data' => new InvoiceLayoutResource($model),
        ]);
    }

    public function store(StoreInvoiceLayoutRequest $request): JsonResponse
    {
        $tenantId = $this->currentTenantId($request);
        $userId = $this->currentUserId($request);

        $model = $this->layouts->create($tenantId, $request->toDto(), $userId);

        return response()->json([
            'data' => new InvoiceLayoutResource($model),
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateInvoiceLayoutRequest $request, int $layout): JsonResponse
    {
        $tenantId = $this->currentTenantId($request);
        $userId = $this->currentUserId($request);

        $model = $this->layouts->update(
            $tenantId,
            $layout,
            $request->toDto(),
            $request->expectedVersion(),
            $userId,
        );

        return response()->json([
            'data' => new InvoiceLayoutResource($model),
        ]);
    }

    public function destroy(Request $request, int $layout): JsonResponse
    {
        $tenantId = $this->currentTenantId($request);
        $this->layouts->delete($tenantId, $layout);

        return response()->json([
            'data' => ['success' => true],
        ]);
    }

    public function preview(Request $request, int|string $layout): Response
    {
        $tenantId = $this->currentTenantId($request);
        $invoiceId = $request->has('invoice_id') && is_numeric($request->input('invoice_id'))
            ? (int) $request->input('invoice_id')
            : null;

        $html = $this->layouts->previewHtml($tenantId, $layout, $invoiceId);

        if ($request->header('Accept') === 'text/html' || $request->query('format') === 'html') {
            return response($html, Response::HTTP_OK, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        return response()->json([
            'data' => [
                'html' => $html,
            ],
        ]);
    }

    public function uploadTemplate(
        UploadInvoiceBladeTemplateRequest $request,
        InvoiceBladeSecurityValidator $validator,
    ): JsonResponse {
        $file = $request->file('template_file');
        if ($file === null) {
            abort(400, 'Template file is required.');
        }

        $content = (string) file_get_contents($file->getRealPath());
        $validator->validate($content);

        return response()->json([
            'data' => [
                'filename' => $file->getClientOriginalName(),
                'content' => $content,
                'size_bytes' => strlen($content),
            ],
        ]);
    }

    public function export(Request $request, int|string $layout): StreamedResponse
    {
        $tenantId = $this->currentTenantId($request);
        $model = is_numeric($layout)
            ? $this->layouts->find($tenantId, (int) $layout)
            : $this->layouts->findByCode($tenantId, (string) $layout);

        if ($model === null) {
            throw new NotFoundHttpException('Invoice layout not found.');
        }

        $content = '';
        if ($model->isBlade() && ! empty($model->blade_content)) {
            $content = $model->blade_content;
        } else {
            // Load preset view file
            $viewName = $model->view_template ?? 'invoice.layouts.standard';
            $relativeViewPath = str_replace('.', '/', $viewName).'.blade.php';
            $fullPath = resource_path('views/'.$relativeViewPath);
            if (file_exists($fullPath)) {
                $content = (string) file_get_contents($fullPath);
            } else {
                $content = "{{-- Custom Blade Template for {$model->name} --}}\n<div class=\"invoice-sheet\">\n    <h1>{{ \$document['title'] }}</h1>\n    <p>Invoice #{{ \$document['invoice_number'] }}</p>\n</div>";
            }
        }

        $filename = Str::slug($model->code, '_').'.blade.php';

        return response()->streamDownload(
            static function () use ($content): void {
                echo $content;
            },
            $filename,
            [
                'Content-Type' => 'text/x-php; charset=UTF-8',
            ],
        );
    }

    public function setDefault(SetDefaultInvoiceLayoutRequest $request): JsonResponse
    {
        $tenantId = $this->currentTenantId($request);
        $userId = $this->currentUserId($request);
        $scope = $request->scope();
        $printer = $request->printer();
        $organizationUnitId = $request->has('organization_unit_id')
            ? $request->organizationUnitId()
            : ($scope === 'tenant' ? null : $this->currentOrganizationUnitId($request));

        $this->layouts->setDefault(
            $tenantId,
            $organizationUnitId,
            $request->code(),
            $userId,
            $printer,
        );

        return response()->json([
            'data' => [
                'success' => true,
                'code' => $request->code(),
                'scope' => $scope,
                'printer' => $printer,
                'organization_unit_id' => $organizationUnitId,
            ],
        ]);
    }

    public function printerAssignments(Request $request): JsonResponse
    {
        $tenantId = $this->currentTenantId($request);
        $organizationUnitId = $request->has('organization_unit_id')
            ? (int) $request->query('organization_unit_id')
            : $this->currentOrganizationUnitId($request);

        $assignments = $this->layouts->getPrinterAssignments($tenantId, $organizationUnitId);

        return response()->json([
            'data' => [
                'assignments' => $assignments,
                'organization_unit_id' => $organizationUnitId,
            ],
        ]);
    }

    public function deletePrinterAssignment(Request $request, string $printer): JsonResponse
    {
        $tenantId = $this->currentTenantId($request);
        $organizationUnitId = $request->has('organization_unit_id')
            ? (int) $request->input('organization_unit_id')
            : $this->currentOrganizationUnitId($request);

        $this->layouts->removePrinterAssignment($tenantId, $organizationUnitId, $printer);

        return response()->json([
            'data' => [
                'success' => true,
                'printer' => $printer,
            ],
        ]);
    }

    private function currentTenantId(Request $request): int
    {
        $value = $request->attributes->get((string) config('core.current_tenant.id_attribute', 'current_tenant_id'));
        if (! is_numeric($value) || (int) $value < 1) {
            abort(404);
        }

        return (int) $value;
    }

    private function currentOrganizationUnitId(Request $request): ?int
    {
        $value = $request->attributes->get((string) config('core.current_organization_unit.id_attribute', 'current_organization_unit_id'));

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function currentUserId(Request $request): int
    {
        $id = $request->user()?->getAuthIdentifier()
            ?? $request->attributes->get((string) config('core.current_user.id_attribute', 'current_user_id'));

        return is_numeric($id) && (int) $id > 0 ? (int) $id : 1;
    }
}
