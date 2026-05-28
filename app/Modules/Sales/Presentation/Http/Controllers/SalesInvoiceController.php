<?php

declare(strict_types=1);

namespace Modules\Sales\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Results\Result;
use Modules\Sales\Application\Contracts\Services\SalesIntegrationServiceInterface;
use Modules\Sales\Application\Contracts\Services\SalesManagementServiceInterface;
use Modules\Sales\Presentation\Http\Requests\CalculateSalesInvoiceRequest;

final class SalesInvoiceController extends Controller
{
    public function __construct(
        private readonly SalesIntegrationServiceInterface $integration,
        private readonly SalesManagementServiceInterface $management,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $payload = $this->withContext($request);

        $sourceType = (string) ($payload['source_type'] ?? '');
        $sourceId = (int) ($payload['source_id'] ?? 0);
        if ($sourceType === '' || $sourceId < 1) {
            return response()->json([
                'message' => 'source_type and source_id are required for invoice listing.',
            ], 422);
        }

        return $this->respond($this->integration->listSourceDocuments($sourceType, $sourceId, $payload));
    }

    public function show(Request $request, int $documentId): JsonResponse
    {
        $payload = $this->withContext($request);
        $sourceType = (string) ($payload['source_type'] ?? '');
        $sourceId = (int) ($payload['source_id'] ?? 0);
        if ($sourceType === '' || $sourceId < 1) {
            return response()->json([
                'message' => 'source_type and source_id are required for invoice lookup.',
            ], 422);
        }

        return $this->respond($this->integration->showSourceDocument($sourceType, $sourceId, $documentId, $payload));
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->withContext($request);
        $sourceType = (string) ($payload['source_type'] ?? '');
        $sourceId = (int) ($payload['source_id'] ?? 0);
        if ($sourceType === '' || $sourceId < 1) {
            return response()->json([
                'message' => 'source_type and source_id are required to create invoice.',
            ], 422);
        }

        return $this->respond($this->integration->createSourceDocument($sourceType, $sourceId, $payload));
    }

    public function storeFromSo(Request $request): JsonResponse
    {
        $payload = $this->withContext($request);
        $salesOrderId = (int) ($payload['sales_order_id'] ?? 0);
        if ($salesOrderId < 1) {
            return response()->json(['message' => 'sales_order_id is required.'], 422);
        }

        return $this->respond($this->integration->createSourceDocument('sales_order', $salesOrderId, $payload));
    }

    public function storeFromGdn(Request $request): JsonResponse
    {
        $payload = $this->withContext($request);
        $gdnHeaderId = (int) ($payload['gdn_header_id'] ?? 0);
        if ($gdnHeaderId < 1) {
            return response()->json(['message' => 'gdn_header_id is required.'], 422);
        }

        return $this->respond($this->integration->createSourceDocument('gdn_header', $gdnHeaderId, $payload));
    }

    public function storeFromMultipleGdns(Request $request): JsonResponse
    {
        $payload = $this->withContext($request);
        $gdnHeaderIds = is_array($payload['gdn_header_ids'] ?? null) ? $payload['gdn_header_ids'] : [];
        if ($gdnHeaderIds === []) {
            return response()->json(['message' => 'gdn_header_ids is required.'], 422);
        }

        $first = (int) ($gdnHeaderIds[0] ?? 0);
        if ($first < 1) {
            return response()->json(['message' => 'gdn_header_ids must contain valid ids.'], 422);
        }

        $payload['metadata'] = array_merge(
            is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
            ['multi_gdn_source_ids' => $gdnHeaderIds],
        );

        return $this->respond($this->integration->createSourceDocument('gdn_header', $first, $payload));
    }

    public function update(Request $request, int $documentId): JsonResponse
    {
        $payload = $this->withContext($request);
        $sourceType = (string) ($payload['source_type'] ?? '');
        $sourceId = (int) ($payload['source_id'] ?? 0);
        if ($sourceType === '' || $sourceId < 1) {
            return response()->json(['message' => 'source_type and source_id are required.'], 422);
        }

        $lineChanges = [];
        $lines = is_array($payload['lines'] ?? null) ? $payload['lines'] : [];
        if ($lines !== []) {
            foreach ($lines as $line) {
                if (! is_array($line)) {
                    continue;
                }

                $linePayload = array_merge($payload, $line);
                $action = strtolower(trim((string) ($linePayload['action'] ?? 'upsert')));
                $linkId = (int) ($linePayload['link_id'] ?? 0);

                if ($action === 'delete' || ($linePayload['deleted'] ?? false) === true) {
                    $unmatch = $this->integration->unmatchSourceDocumentLine($sourceType, $sourceId, $documentId, [
                        'link_id' => $linkId,
                        'source_line_id' => $linePayload['source_line_id'] ?? null,
                        'document_line_id' => $linePayload['document_line_id'] ?? null,
                        'actor_id' => $payload['actor_id'] ?? null,
                        'metadata' => $payload['metadata'] ?? null,
                    ]);
                    if ($unmatch->isFailure()) {
                        return $this->respond($unmatch);
                    }

                    $lineChanges[] = [
                        'action' => 'delete',
                        'result' => $unmatch->valueOrFail(),
                    ];
                    continue;
                }

                if ($linkId > 0) {
                    $unmatch = $this->integration->unmatchSourceDocumentLine($sourceType, $sourceId, $documentId, [
                        'link_id' => $linkId,
                        'actor_id' => $payload['actor_id'] ?? null,
                        'metadata' => $payload['metadata'] ?? null,
                    ]);
                    if ($unmatch->isFailure()) {
                        return $this->respond($unmatch);
                    }
                }

                $match = $this->integration->matchSourceDocumentLine($sourceType, $sourceId, $documentId, $linePayload);
                if ($match->isFailure()) {
                    return $this->respond($match);
                }

                $lineChanges[] = [
                    'action' => $linkId > 0 ? 'replace' : 'create',
                    'result' => $match->valueOrFail(),
                ];
            }
        }

        if (isset($payload['status'])) {
            $statusResult = $this->respond(
                $this->integration->changeSourceDocumentStatus($sourceType, $sourceId, $documentId, $payload),
            );

            if ($lineChanges === []) {
                return $statusResult;
            }

            $body = $statusResult->getData(true);

            return response()->json([
                'data' => [
                    'status_transition' => $body['data'] ?? null,
                    'line_changes' => $lineChanges,
                ],
            ], $statusResult->getStatusCode());
        }

        if ($lineChanges !== []) {
            return response()->json([
                'data' => [
                    'line_changes' => $lineChanges,
                ],
            ]);
        }

        return response()->json([
            'message' => 'Invoice draft field updates must be done through document module update workflow.',
        ], 422);
    }

    public function destroy(Request $request, int $documentId): JsonResponse
    {
        $payload = $this->withContext($request);
        $payload['status'] = 'cancelled';

        $sourceType = (string) ($payload['source_type'] ?? '');
        $sourceId = (int) ($payload['source_id'] ?? 0);
        if ($sourceType === '' || $sourceId < 1) {
            return response()->json(['message' => 'source_type and source_id are required.'], 422);
        }

        return $this->respond(
            $this->integration->changeSourceDocumentStatus($sourceType, $sourceId, $documentId, $payload),
        );
    }

    public function post(Request $request, int $documentId): JsonResponse
    {
        $payload = $this->withContext($request);
        $payload['status'] = 'posted';

        return $this->transitionInvoice($documentId, $payload);
    }

    public function cancel(Request $request, int $documentId): JsonResponse
    {
        $payload = $this->withContext($request);
        $payload['status'] = 'cancelled';

        return $this->transitionInvoice($documentId, $payload);
    }

    public function reverse(Request $request, int $documentId): JsonResponse
    {
        $payload = $this->withContext($request);
        $payload['status'] = 'reversed';

        return $this->transitionInvoice($documentId, $payload);
    }

    public function lines(Request $request, int $documentId): JsonResponse
    {
        $payload = $this->withContext($request);
        $sourceType = (string) ($payload['source_type'] ?? '');
        $sourceId = (int) ($payload['source_id'] ?? 0);
        if ($sourceType === '' || $sourceId < 1) {
            return response()->json(['message' => 'source_type and source_id are required.'], 422);
        }

        $result = $this->integration->showSourceDocument($sourceType, $sourceId, $documentId, $payload);
        if ($result->isFailure()) {
            return $this->respond($result);
        }

        $data = $result->valueOrFail();
        $lines = is_array($data['line_links'] ?? null) ? $data['line_links'] : [];

        return response()->json(['data' => $lines]);
    }

    public function createLine(Request $request, int $documentId): JsonResponse
    {
        $payload = $this->withContext($request);
        $sourceType = (string) ($payload['source_type'] ?? '');
        $sourceId = (int) ($payload['source_id'] ?? 0);
        if ($sourceType === '' || $sourceId < 1) {
            return response()->json(['message' => 'source_type and source_id are required.'], 422);
        }

        return $this->respond(
            $this->integration->matchSourceDocumentLine($sourceType, $sourceId, $documentId, $payload),
        );
    }

    public function updateLine(Request $request, int $linkId): JsonResponse
    {
        $payload = $this->withContext($request);
        $sourceType = (string) ($payload['source_type'] ?? '');
        $sourceId = (int) ($payload['source_id'] ?? 0);
        $documentId = (int) ($payload['document_id'] ?? 0);
        if ($sourceType === '' || $sourceId < 1 || $documentId < 1) {
            return response()->json(['message' => 'source_type, source_id and document_id are required.'], 422);
        }

        $unmatch = $this->integration->unmatchSourceDocumentLine($sourceType, $sourceId, $documentId, [
            'link_id' => $linkId,
            'actor_id' => $payload['actor_id'] ?? null,
            'metadata' => $payload['metadata'] ?? null,
        ]);
        if ($unmatch->isFailure()) {
            return $this->respond($unmatch);
        }

        return $this->respond(
            $this->integration->matchSourceDocumentLine($sourceType, $sourceId, $documentId, $payload),
        );
    }

    public function deleteLine(Request $request, int $linkId): JsonResponse
    {
        $payload = $this->withContext($request);
        $sourceType = (string) ($payload['source_type'] ?? '');
        $sourceId = (int) ($payload['source_id'] ?? 0);
        $documentId = (int) ($payload['document_id'] ?? 0);
        if ($sourceType === '' || $sourceId < 1 || $documentId < 1) {
            return response()->json(['message' => 'source_type, source_id and document_id are required.'], 422);
        }

        return $this->respond($this->integration->unmatchSourceDocumentLine($sourceType, $sourceId, $documentId, [
            'link_id' => $linkId,
            'actor_id' => $payload['actor_id'] ?? null,
            'metadata' => $payload['metadata'] ?? null,
        ]));
    }

    public function availableSoLinesForInvoice(Request $request): JsonResponse
    {
        $payload = $this->withContext($request);
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        $salesOrderId = (int) ($payload['sales_order_id'] ?? 0);
        if ($tenantId < 1 || $salesOrderId < 1) {
            return response()->json(['message' => 'tenant_id and sales_order_id are required.'], 422);
        }

        return $this->respond($this->management->getAvailableSalesOrderLinesForInvoice($tenantId, $salesOrderId));
    }

    public function availableGdnLinesForInvoice(Request $request): JsonResponse
    {
        $payload = $this->withContext($request);
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        $gdnHeaderId = (int) ($payload['gdn_header_id'] ?? 0);
        if ($tenantId < 1 || $gdnHeaderId < 1) {
            return response()->json(['message' => 'tenant_id and gdn_header_id are required.'], 422);
        }

        return $this->respond($this->management->getAvailableGdnLinesForDocument($tenantId, $gdnHeaderId));
    }

    public function calculateInvoice(CalculateSalesInvoiceRequest $request): JsonResponse
    {
        $payload = array_merge($this->withContext($request), $request->validated());

        return $this->respond($this->management->calculateInvoicePreview($payload));
    }

    public function validateUom(Request $request): JsonResponse
    {
        $payload = $this->withContext($request);
        $quantity = round((float) ($payload['quantity'] ?? 0), 4);
        $conversionRate = round((float) ($payload['conversion_rate'] ?? 0), 6);

        if ($quantity <= 0 || $conversionRate <= 0) {
            return response()->json([
                'message' => 'quantity and conversion_rate must be greater than zero.',
            ], 422);
        }

        return response()->json([
            'data' => [
                'valid' => true,
                'base_quantity' => round($quantity * $conversionRate, 4),
            ],
        ]);
    }

    private function transitionInvoice(int $documentId, array $payload): JsonResponse
    {
        $sourceType = (string) ($payload['source_type'] ?? '');
        $sourceId = (int) ($payload['source_id'] ?? 0);
        if ($sourceType === '' || $sourceId < 1) {
            return response()->json(['message' => 'source_type and source_id are required.'], 422);
        }

        return $this->respond(
            $this->integration->changeSourceDocumentStatus($sourceType, $sourceId, $documentId, $payload),
        );
    }

    /** @return array<string, mixed> */
    private function withContext(Request $request): array
    {
        $payload = $request->all();

        $tenantId = $request->attributes->get((string) config('core.current_tenant.id_attribute', 'current_tenant_id'));
        if (! isset($payload['tenant_id']) && is_int($tenantId)) {
            $payload['tenant_id'] = $tenantId;
        }

        $organizationUnitId = $request->attributes->get(
            (string) config('core.current_organization_unit.id_attribute', 'current_organization_unit_id')
        );
        if (! isset($payload['organization_unit_id']) && is_int($organizationUnitId)) {
            $payload['organization_unit_id'] = $organizationUnitId;
        }

        $currentUserId = $request->attributes->get(
            (string) config('core.current_user.id_attribute', 'current_user_id')
        );
        if (! isset($payload['actor_id']) && is_int($currentUserId)) {
            $payload['actor_id'] = $currentUserId;
        }

        return $payload;
    }

    private function respond(Result $result): JsonResponse
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $statusCode = $error->code === 'SALES_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message, 'code' => $error->code], $statusCode);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }
}
