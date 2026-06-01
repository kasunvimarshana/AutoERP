<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Finance\Application\Contracts\Services\TaxCalculationServiceInterface;
use Modules\Purchase\Application\Contracts\Services\PurchaseManagementServiceInterface;
use Modules\Purchase\Application\Repositories\GrnHeaderRepositoryInterface;
use Modules\Purchase\Application\Repositories\GrnLineRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseDocumentLinkRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseOrderLineRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseOrderRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchasePaymentAllocationRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseReturnLineRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseReturnRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseSettingRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseStatusHistoryRepositoryInterface;
use Modules\Purchase\Domain\Constants\PurchaseErrorCode;
use Throwable;

final class PurchaseManagementService implements PurchaseManagementServiceInterface
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private readonly PurchaseOrderLineRepositoryInterface $purchaseOrderLineRepository,
        private readonly GrnHeaderRepositoryInterface $grnHeaderRepository,
        private readonly GrnLineRepositoryInterface $grnLineRepository,
        private readonly PurchaseReturnRepositoryInterface $purchaseReturnRepository,
        private readonly PurchaseReturnLineRepositoryInterface $purchaseReturnLineRepository,
        private readonly PurchaseSettingRepositoryInterface $purchaseSettingRepository,
        private readonly PurchaseStatusHistoryRepositoryInterface $purchaseStatusHistoryRepository,
        private readonly PurchaseDocumentLinkRepositoryInterface $purchaseDocumentLinkRepository,
        private readonly PurchasePaymentAllocationRepositoryInterface $purchasePaymentAllocationRepository,
        private readonly TaxCalculationServiceInterface $taxCalculationService,
    ) {}

    public function upsertPurchaseOrderWithLines(?int $id, array $payload): Result
    {
        try {
            return $this->purchaseOrderRepository->transaction(function () use ($id, $payload): Result {
                $headerPayload = $this->extractHeaderPayload($payload);
                $header = $id === null
                    ? $this->purchaseOrderRepository->create($this->withDefaultRowVersion($headerPayload))
                    : $this->purchaseOrderRepository->update($id, $headerPayload);

                if (is_array($payload['lines'] ?? null)) {
                    $sync = $this->syncPurchaseOrderLines((int) $header->id(), [
                        'tenant_id' => $header->get('tenant_id'),
                        'organization_unit_id' => $header->get('organization_unit_id'),
                        'lines' => $payload['lines'],
                    ]);
                    if ($sync->isFailure()) {
                        return $sync;
                    }
                } else {
                    $this->recalculatePurchaseOrderTotals((int) $header->id(), (int) $header->get('tenant_id'));
                }

                $reloaded = $this->purchaseOrderRepository->findById((int) $header->id());
                if (! $reloaded instanceof DataRecord) {
                    return Result::failure(new Error(PurchaseErrorCode::NOT_FOUND, 'Purchase order not found.'));
                }

                return Result::success($reloaded);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function syncPurchaseOrderLines(int $purchaseOrderId, array $payload): Result
    {
        try {
            return $this->purchaseOrderRepository->transaction(function () use ($purchaseOrderId, $payload): Result {
                $header = $this->purchaseOrderRepository->findById($purchaseOrderId);
                if (! $header instanceof DataRecord) {
                    return Result::failure(new Error(PurchaseErrorCode::NOT_FOUND, 'Purchase order not found.'));
                }

                $tenantId = (int) ($payload['tenant_id'] ?? $header->get('tenant_id', 0));
                $organizationUnitId = $payload['organization_unit_id'] ?? $header->get('organization_unit_id');
                $lines = is_array($payload['lines'] ?? null) ? $payload['lines'] : [];

                foreach ($lines as $linePayload) {
                    if (! is_array($linePayload)) {
                        continue;
                    }

                    $lineId = isset($linePayload['id']) ? (int) $linePayload['id'] : null;
                    $deleteRequested = (bool) ($linePayload['_delete'] ?? false);
                    if ($deleteRequested && $lineId !== null) {
                        $this->purchaseOrderLineRepository->delete($lineId);

                        continue;
                    }

                    $upsert = $this->normalizePurchaseOrderLinePayload(
                        $linePayload,
                        $tenantId,
                        $organizationUnitId,
                        $purchaseOrderId,
                    );

                    if ($lineId === null) {
                        $this->purchaseOrderLineRepository->create($this->withDefaultRowVersion($upsert));
                    } else {
                        $this->purchaseOrderLineRepository->update($lineId, $upsert);
                    }
                }

                $this->recalculatePurchaseOrderTotals($purchaseOrderId, $tenantId);

                return Result::success([
                    'purchase_order_id' => $purchaseOrderId,
                    'synced' => true,
                ]);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function upsertGrnWithLines(?int $id, array $payload): Result
    {
        try {
            return $this->grnHeaderRepository->transaction(function () use ($id, $payload): Result {
                $headerPayload = $this->extractHeaderPayload($payload);
                $header = $id === null
                    ? $this->grnHeaderRepository->create($this->withDefaultRowVersion($headerPayload))
                    : $this->grnHeaderRepository->update($id, $headerPayload);

                if (is_array($payload['lines'] ?? null)) {
                    $sync = $this->syncGrnLines((int) $header->id(), [
                        'tenant_id' => $header->get('tenant_id'),
                        'organization_unit_id' => $header->get('organization_unit_id'),
                        'lines' => $payload['lines'],
                    ]);
                    if ($sync->isFailure()) {
                        return $sync;
                    }
                } else {
                    $this->recalculateGrnTotals((int) $header->id(), (int) $header->get('tenant_id'));
                }

                $reloaded = $this->grnHeaderRepository->findById((int) $header->id());
                if (! $reloaded instanceof DataRecord) {
                    return Result::failure(new Error(PurchaseErrorCode::NOT_FOUND, 'GRN not found.'));
                }

                return Result::success($reloaded);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function syncGrnLines(int $grnHeaderId, array $payload): Result
    {
        try {
            return $this->grnHeaderRepository->transaction(function () use ($grnHeaderId, $payload): Result {
                $header = $this->grnHeaderRepository->findById($grnHeaderId);
                if (! $header instanceof DataRecord) {
                    return Result::failure(new Error(PurchaseErrorCode::NOT_FOUND, 'GRN not found.'));
                }

                $tenantId = (int) ($payload['tenant_id'] ?? $header->get('tenant_id', 0));
                $organizationUnitId = $payload['organization_unit_id'] ?? $header->get('organization_unit_id');
                $lines = is_array($payload['lines'] ?? null) ? $payload['lines'] : [];

                foreach ($lines as $linePayload) {
                    if (! is_array($linePayload)) {
                        continue;
                    }

                    $lineId = isset($linePayload['id']) ? (int) $linePayload['id'] : null;
                    $deleteRequested = (bool) ($linePayload['_delete'] ?? false);
                    if ($deleteRequested && $lineId !== null) {
                        $this->grnLineRepository->delete($lineId);

                        continue;
                    }

                    $upsert = $this->normalizeGrnLinePayload(
                        $linePayload,
                        $tenantId,
                        $organizationUnitId,
                        $grnHeaderId,
                    );
                    if ($lineId === null) {
                        $this->grnLineRepository->create($this->withDefaultRowVersion($upsert));
                    } else {
                        $this->grnLineRepository->update($lineId, $upsert);
                    }
                }

                $this->recalculateGrnTotals($grnHeaderId, $tenantId);

                return Result::success([
                    'grn_header_id' => $grnHeaderId,
                    'synced' => true,
                ]);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function upsertPurchaseReturnWithLines(?int $id, array $payload): Result
    {
        try {
            return $this->purchaseReturnRepository->transaction(function () use ($id, $payload): Result {
                $headerPayload = $this->extractHeaderPayload($payload);
                $header = $id === null
                    ? $this->purchaseReturnRepository->create($this->withDefaultRowVersion($headerPayload))
                    : $this->purchaseReturnRepository->update($id, $headerPayload);

                if (is_array($payload['lines'] ?? null)) {
                    $sync = $this->syncPurchaseReturnLines((int) $header->id(), [
                        'tenant_id' => $header->get('tenant_id'),
                        'organization_unit_id' => $header->get('organization_unit_id'),
                        'lines' => $payload['lines'],
                    ]);
                    if ($sync->isFailure()) {
                        return $sync;
                    }
                } else {
                    $this->recalculatePurchaseReturnTotals((int) $header->id(), (int) $header->get('tenant_id'));
                }

                $reloaded = $this->purchaseReturnRepository->findById((int) $header->id());
                if (! $reloaded instanceof DataRecord) {
                    return Result::failure(new Error(PurchaseErrorCode::NOT_FOUND, 'Purchase return not found.'));
                }

                return Result::success($reloaded);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function syncPurchaseReturnLines(int $purchaseReturnId, array $payload): Result
    {
        try {
            return $this->purchaseReturnRepository->transaction(function () use ($purchaseReturnId, $payload): Result {
                $header = $this->purchaseReturnRepository->findById($purchaseReturnId);
                if (! $header instanceof DataRecord) {
                    return Result::failure(new Error(PurchaseErrorCode::NOT_FOUND, 'Purchase return not found.'));
                }

                $tenantId = (int) ($payload['tenant_id'] ?? $header->get('tenant_id', 0));
                $organizationUnitId = $payload['organization_unit_id'] ?? $header->get('organization_unit_id');
                $lines = is_array($payload['lines'] ?? null) ? $payload['lines'] : [];

                foreach ($lines as $linePayload) {
                    if (! is_array($linePayload)) {
                        continue;
                    }

                    $lineId = isset($linePayload['id']) ? (int) $linePayload['id'] : null;
                    $deleteRequested = (bool) ($linePayload['_delete'] ?? false);
                    if ($deleteRequested && $lineId !== null) {
                        $this->purchaseReturnLineRepository->delete($lineId);

                        continue;
                    }

                    $upsert = $this->normalizePurchaseReturnLinePayload(
                        $linePayload,
                        $tenantId,
                        $organizationUnitId,
                        $purchaseReturnId,
                    );

                    if ($lineId === null) {
                        $this->purchaseReturnLineRepository->create($this->withDefaultRowVersion($upsert));
                    } else {
                        $this->purchaseReturnLineRepository->update($lineId, $upsert);
                    }
                }

                $this->recalculatePurchaseReturnTotals($purchaseReturnId, $tenantId);

                return Result::success([
                    'purchase_return_id' => $purchaseReturnId,
                    'synced' => true,
                ]);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function getStatusHistory(string $entityType, int $entityId, int $tenantId): Result
    {
        try {
            return Result::success($this->purchaseStatusHistoryRepository->list([
                'tenant_id' => $tenantId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ]));
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function getPurchaseSettings(int $tenantId, ?int $organizationUnitId): Result
    {
        try {
            $records = $organizationUnitId !== null
                ? $this->purchaseSettingRepository->list([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'is_active' => true,
                ])
                : [];

            if ($records === []) {
                $records = $this->purchaseSettingRepository->list([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => null,
                    'is_active' => true,
                ]);
            }

            return Result::success($records[0] ?? null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function upsertPurchaseSettings(array $payload): Result
    {
        try {
            $tenantId = (int) ($payload['tenant_id'] ?? 0);
            if ($tenantId < 1) {
                return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, 'tenant_id is required.'));
            }

            $organizationUnitId = isset($payload['organization_unit_id'])
                ? (int) $payload['organization_unit_id']
                : null;

            $existing = $this->purchaseSettingRepository->list([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
            ]);

            if ($existing !== []) {
                $updated = $this->purchaseSettingRepository->update((int) $existing[0]->id(), $payload);

                return Result::success($updated);
            }

            return Result::success($this->purchaseSettingRepository->create($this->withDefaultRowVersion($payload)));
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function initializePurchaseSettings(array $payload): Result
    {
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        if ($tenantId < 1) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, 'tenant_id is required.'));
        }

        $organizationUnitId = isset($payload['organization_unit_id'])
            ? (int) $payload['organization_unit_id']
            : null;

        $existing = $this->purchaseSettingRepository->list([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
        ]);

        if ($existing !== []) {
            return Result::success($existing[0]);
        }

        return $this->upsertPurchaseSettings(array_merge([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'is_active' => true,
            'allow_direct_grn' => true,
            'allow_direct_purchase_document' => true,
            'allow_return_without_original' => true,
            'allow_negative_stock_on_return' => false,
            'allow_header_discount' => true,
            'allow_line_discount' => true,
            'tax_calculation_level' => 'line',
            'header_discount_allocation_method' => 'proportional',
            'default_advance_payment_account_id' => $payload['default_advance_payment_account_id'] ?? null,
            'default_refund_account_id' => $payload['default_refund_account_id'] ?? null,
            'default_po_status' => 'draft',
            'default_grn_status' => 'draft',
            'default_document_status' => 'draft',
            'default_return_status' => 'draft',
            'require_po_before_grn' => false,
            'require_grn_before_invoice' => false,
        ], $payload));
    }

    public function getAvailablePurchaseOrderLinesForGrn(int $tenantId, int $purchaseOrderId): Result
    {
        try {
            $lines = $this->purchaseOrderLineRepository->list([
                'tenant_id' => $tenantId,
                'purchase_order_id' => $purchaseOrderId,
            ]);

            $result = [];
            foreach ($lines as $line) {
                $ordered = (float) $line->get('ordered_qty', 0);
                $received = (float) $line->get('received_qty', 0);
                $available = round($ordered - $received, 4);
                if ($available <= 0) {
                    continue;
                }

                $row = $line->toArray();
                $row['available_receive_qty'] = $available;
                $result[] = $row;
            }

            return Result::success($result);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function getAvailableGrnLinesForDocument(int $tenantId, int $grnHeaderId): Result
    {
        try {
            $lines = $this->grnLineRepository->list([
                'tenant_id' => $tenantId,
                'grn_header_id' => $grnHeaderId,
            ]);

            $result = [];
            foreach ($lines as $line) {
                $accepted = (float) $line->get('accepted_qty', $line->get('received_qty', 0));
                $documented = (float) $line->get('documented_qty', 0);
                $available = round($accepted - $documented, 4);
                if ($available <= 0) {
                    continue;
                }

                $row = $line->toArray();
                $row['available_document_qty'] = $available;
                $result[] = $row;
            }

            return Result::success($result);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function getReturnableLines(string $sourceType, int $sourceId, int $tenantId): Result
    {
        try {
            $records = [];
            if ($sourceType === 'grn_header') {
                $lines = $this->grnLineRepository->list([
                    'tenant_id' => $tenantId,
                    'grn_header_id' => $sourceId,
                ]);
                foreach ($lines as $line) {
                    $baseQty = (float) $line->get('accepted_qty', $line->get('received_qty', 0));
                    $returned = (float) $line->get('returned_qty', 0);
                    $available = round($baseQty - $returned, 4);
                    if ($available <= 0) {
                        continue;
                    }

                    $row = $line->toArray();
                    $row['available_return_qty'] = $available;
                    $row['source_type'] = 'grn_line';
                    $records[] = $row;
                }
            }

            if ($sourceType === 'purchase_order') {
                $lines = $this->purchaseOrderLineRepository->list([
                    'tenant_id' => $tenantId,
                    'purchase_order_id' => $sourceId,
                ]);
                foreach ($lines as $line) {
                    $received = (float) $line->get('received_qty', 0);
                    $returned = (float) $line->get('returned_qty', 0);
                    $available = round($received - $returned, 4);
                    if ($available <= 0) {
                        continue;
                    }

                    $row = $line->toArray();
                    $row['available_return_qty'] = $available;
                    $row['source_type'] = 'purchase_order_line';
                    $records[] = $row;
                }
            }

            return Result::success($records);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function getPayableDocuments(int $tenantId, ?int $supplierId): Result
    {
        try {
            $criteria = [
                'tenant_id' => $tenantId,
                'status' => 'active',
            ];
            if ($supplierId !== null) {
                $criteria['source_type'] = 'purchase_order';
            }

            $links = $this->purchaseDocumentLinkRepository->list($criteria);
            $allocations = $this->purchasePaymentAllocationRepository->list([
                'tenant_id' => $tenantId,
                'status' => 'active',
            ]);

            $linkedByDocument = [];
            foreach ($links as $link) {
                $documentId = (int) $link->get('document_id', 0);
                if ($documentId < 1) {
                    continue;
                }

                $linkedByDocument[$documentId] = ($linkedByDocument[$documentId] ?? 0.0)
                    + (float) $link->get('linked_amount', 0);
            }

            $allocatedByDocument = [];
            foreach ($allocations as $allocation) {
                $documentId = (int) $allocation->get('document_id', 0);
                if ($documentId < 1) {
                    continue;
                }

                $allocatedByDocument[$documentId] = ($allocatedByDocument[$documentId] ?? 0.0)
                    + (float) $allocation->get('allocated_amount', 0);
            }

            $result = [];
            foreach ($linkedByDocument as $documentId => $linkedTotal) {
                $allocatedTotal = $allocatedByDocument[$documentId] ?? 0.0;
                $outstanding = round(max(0.0, $linkedTotal - $allocatedTotal), 4);
                if ($outstanding <= 0.0) {
                    continue;
                }

                $result[] = [
                    'document_id' => $documentId,
                    'linked_amount' => round($linkedTotal, 4),
                    'allocated_amount' => round($allocatedTotal, 4),
                    'outstanding_amount' => $outstanding,
                ];
            }

            return Result::success($result);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function calculateInvoicePreview(array $payload): Result
    {
        try {
            $lines = is_array($payload['lines'] ?? null) ? $payload['lines'] : [];

            $subtotal = 0.0;
            $taxTotal = 0.0;
            $discountTotal = 0.0;
            $linePreviews = [];

            foreach ($lines as $line) {
                if (! is_array($line)) {
                    continue;
                }

                $qty = round((float) ($line['quantity'] ?? $line['linked_quantity'] ?? 0), 4);
                $unitPrice = round((float) ($line['unit_price'] ?? 0), 4);
                $lineGross = round($qty * $unitPrice, 4);

                $discountAmount = $this->resolveDiscountAmount(
                    $lineGross,
                    (string) ($line['discount_type'] ?? ''),
                    round((float) ($line['discount_value'] ?? 0), 4),
                );
                $lineNet = round(max(0.0, $lineGross - $discountAmount), 4);
                $lineTax = $this->resolveTaxAmount(
                    (int) ($payload['tenant_id'] ?? $line['tenant_id'] ?? 0),
                    isset($line['tax_group_id']) ? (int) $line['tax_group_id'] : null,
                    $lineNet,
                    $payload['posting_date'] ?? null,
                );

                $subtotal += $lineGross;
                $discountTotal += $discountAmount;
                $taxTotal += $lineTax;

                $linePreviews[] = [
                    'line_id' => $line['id'] ?? null,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'gross_amount' => $lineGross,
                    'discount_amount' => $discountAmount,
                    'tax_amount' => $lineTax,
                    'line_total' => $lineNet,
                    'line_total_with_tax' => round($lineNet + $lineTax, 4),
                ];
            }

            $subtotal = round($subtotal, 4);
            $discountTotal = round($discountTotal, 4);
            $taxTotal = round($taxTotal, 4);
            $grandTotal = round(max(0.0, $subtotal - $discountTotal + $taxTotal), 4);

            return Result::success([
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'lines' => $linePreviews,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /** @return array<string, mixed> */
    private function extractHeaderPayload(array $payload): array
    {
        unset($payload['lines']);
        unset(
            $payload['subtotal'],
            $payload['line_tax_total'],
            $payload['line_discount_total'],
            $payload['header_discount_amount'],
            $payload['header_tax_amount'],
            $payload['discount_total'],
            $payload['tax_total'],
            $payload['grand_total'],
            $payload['balance'],
        );

        return $payload;
    }

    /** @return array<string, mixed> */
    private function withDefaultRowVersion(array $payload): array
    {
        if (! array_key_exists('row_version', $payload)) {
            $payload['row_version'] = 1;
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    private function normalizePurchaseOrderLinePayload(
        array $line,
        int $tenantId,
        mixed $organizationUnitId,
        int $purchaseOrderId,
    ): array {
        $orderedQty = round((float) ($line['ordered_qty'] ?? 0), 4);
        $unitPrice = round((float) ($line['unit_price'] ?? 0), 4);

        $line = array_merge($line, [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $line['organization_unit_id'] ?? $organizationUnitId,
            'purchase_order_id' => $purchaseOrderId,
            'ordered_qty' => $orderedQty,
            'unit_price' => $unitPrice,
        ]);

        return $this->hydrateLineTotals($line, $orderedQty, $unitPrice);
    }

    /** @return array<string, mixed> */
    private function normalizeGrnLinePayload(
        array $line,
        int $tenantId,
        mixed $organizationUnitId,
        int $grnHeaderId,
    ): array {
        $receivedQty = round((float) ($line['received_qty'] ?? 0), 4);
        $unitPrice = round((float) ($line['unit_price'] ?? 0), 4);
        $acceptedQty = array_key_exists('accepted_qty', $line)
            ? round((float) ($line['accepted_qty'] ?? 0), 4)
            : max(0.0, $receivedQty - round((float) ($line['rejected_qty'] ?? 0), 4));

        $line = array_merge($line, [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $line['organization_unit_id'] ?? $organizationUnitId,
            'grn_header_id' => $grnHeaderId,
            'received_qty' => $receivedQty,
            'accepted_qty' => $acceptedQty,
            'unit_price' => $unitPrice,
        ]);

        return $this->hydrateLineTotals($line, $receivedQty, $unitPrice);
    }

    /** @return array<string, mixed> */
    private function normalizePurchaseReturnLinePayload(
        array $line,
        int $tenantId,
        mixed $organizationUnitId,
        int $purchaseReturnId,
    ): array {
        $returnQty = round((float) ($line['return_qty'] ?? 0), 4);
        $unitPrice = round((float) ($line['unit_price'] ?? 0), 4);

        $line = array_merge($line, [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $line['organization_unit_id'] ?? $organizationUnitId,
            'purchase_return_id' => $purchaseReturnId,
            'return_qty' => $returnQty,
            'unit_price' => $unitPrice,
        ]);

        return $this->hydrateLineTotals($line, $returnQty, $unitPrice);
    }

    /** @param array<string, mixed> $line */
    private function hydrateLineTotals(array $line, float $quantity, float $unitPrice): array
    {
        $grossAmount = round($quantity * $unitPrice, 4);
        $discountType = (string) ($line['discount_type'] ?? '');
        $discountValue = round((float) ($line['discount_value'] ?? 0), 4);
        $discountAmount = $this->resolveDiscountAmount($grossAmount, $discountType, $discountValue);
        $lineTotal = round(max(0.0, $grossAmount - $discountAmount), 4);
        $taxAmount = $this->resolveTaxAmount(
            (int) ($line['tenant_id'] ?? 0),
            isset($line['tax_group_id']) ? (int) $line['tax_group_id'] : null,
            $lineTotal,
            $line['posting_date'] ?? null,
        );

        $line['gross_amount'] = $grossAmount;
        $line['discount_amount'] = $discountAmount;
        $line['tax_amount'] = $taxAmount;
        $line['line_total'] = $lineTotal;
        $line['line_total_with_tax'] = round($lineTotal + $taxAmount, 4);

        return $line;
    }

    private function resolveDiscountAmount(float $grossAmount, string $discountType, float $discountValue): float
    {
        if ($discountValue <= 0) {
            return 0.0;
        }

        $type = strtolower(trim($discountType));
        if ($type === 'percentage') {
            return round(min($grossAmount, ($grossAmount * $discountValue) / 100), 4);
        }

        return round(min($grossAmount, $discountValue), 4);
    }

    private function resolveTaxAmount(int $tenantId, ?int $taxGroupId, float $taxableAmount, mixed $postingDate = null): float
    {
        if ($tenantId < 1 || $taxGroupId === null || $taxGroupId < 1 || $taxableAmount <= 0) {
            return 0.0;
        }

        $result = $this->taxCalculationService->calculate(
            $tenantId,
            $taxGroupId,
            $taxableAmount,
            $postingDate !== null ? (string) $postingDate : null,
        );

        if ($result->isFailure()) {
            return 0.0;
        }

        $tax = $result->valueOrFail();

        return round((float) ($tax['tax_amount'] ?? 0), 4);
    }

    private function resolveHeaderDiscountAmount(DataRecord $header, float $discountableAmount): float
    {
        return $this->resolveDiscountAmount(
            max(0.0, $discountableAmount),
            (string) $header->get('header_discount_type', ''),
            round((float) $header->get('header_discount_value', 0), 4),
        );
    }

    private function recalculatePurchaseOrderTotals(int $purchaseOrderId, int $tenantId): void
    {
        $lines = $this->purchaseOrderLineRepository->list([
            'tenant_id' => $tenantId,
            'purchase_order_id' => $purchaseOrderId,
        ]);

        $this->applyHeaderTotals($lines, function (array $totals) use ($purchaseOrderId): void {
            $header = $this->purchaseOrderRepository->findById($purchaseOrderId);
            if (! $header instanceof DataRecord) {
                return;
            }

            $headerDiscountAmount = $this->resolveHeaderDiscountAmount(
                $header,
                $totals['subtotal'] - $totals['line_discount_total'],
            );
            $headerTaxAmount = $this->resolveTaxAmount(
                (int) $header->get('tenant_id', 0),
                $header->get('header_tax_group_id') !== null ? (int) $header->get('header_tax_group_id') : null,
                max(0.0, $totals['subtotal'] - $totals['line_discount_total'] - $headerDiscountAmount),
                $header->get('order_date') ?? $header->get('document_date') ?? null,
            );
            $debitNoteTotal = round((float) $header->get('debit_note_total', 0), 4);
            $creditNoteTotal = round((float) $header->get('credit_note_total', 0), 4);
            $paidAmount = round((float) $header->get('paid_amount', 0), 4);

            $discountTotal = round($totals['line_discount_total'] + $headerDiscountAmount, 4);
            $taxTotal = round($totals['line_tax_total'] + $headerTaxAmount, 4);
            $grandTotal = round(
                $totals['subtotal'] - $discountTotal + $taxTotal + $debitNoteTotal - $creditNoteTotal,
                4,
            );
            $balance = round($grandTotal - $paidAmount, 4);

            $this->purchaseOrderRepository->update($purchaseOrderId, [
                'subtotal' => $totals['subtotal'],
                'line_tax_total' => $totals['line_tax_total'],
                'line_discount_total' => $totals['line_discount_total'],
                'header_discount_amount' => $headerDiscountAmount,
                'header_tax_amount' => $headerTaxAmount,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'balance' => $balance,
            ]);
        });
    }

    private function recalculateGrnTotals(int $grnHeaderId, int $tenantId): void
    {
        $lines = $this->grnLineRepository->list([
            'tenant_id' => $tenantId,
            'grn_header_id' => $grnHeaderId,
        ]);

        $this->applyHeaderTotals($lines, function (array $totals) use ($grnHeaderId): void {
            $header = $this->grnHeaderRepository->findById($grnHeaderId);
            if (! $header instanceof DataRecord) {
                return;
            }

            $headerDiscountAmount = $this->resolveHeaderDiscountAmount(
                $header,
                $totals['subtotal'] - $totals['line_discount_total'],
            );
            $headerTaxAmount = $this->resolveTaxAmount(
                (int) $header->get('tenant_id', 0),
                $header->get('header_tax_group_id') !== null ? (int) $header->get('header_tax_group_id') : null,
                max(0.0, $totals['subtotal'] - $totals['line_discount_total'] - $headerDiscountAmount),
                $header->get('grn_date') ?? $header->get('document_date') ?? null,
            );
            $debitNoteTotal = round((float) $header->get('debit_note_total', 0), 4);
            $creditNoteTotal = round((float) $header->get('credit_note_total', 0), 4);

            $discountTotal = round($totals['line_discount_total'] + $headerDiscountAmount, 4);
            $taxTotal = round($totals['line_tax_total'] + $headerTaxAmount, 4);
            $grandTotal = round(
                $totals['subtotal'] - $discountTotal + $taxTotal + $debitNoteTotal - $creditNoteTotal,
                4,
            );

            $this->grnHeaderRepository->update($grnHeaderId, [
                'subtotal' => $totals['subtotal'],
                'line_tax_total' => $totals['line_tax_total'],
                'line_discount_total' => $totals['line_discount_total'],
                'header_discount_amount' => $headerDiscountAmount,
                'header_tax_amount' => $headerTaxAmount,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
            ]);
        });
    }

    private function recalculatePurchaseReturnTotals(int $purchaseReturnId, int $tenantId): void
    {
        $lines = $this->purchaseReturnLineRepository->list([
            'tenant_id' => $tenantId,
            'purchase_return_id' => $purchaseReturnId,
        ]);

        $this->applyHeaderTotals($lines, function (array $totals) use ($purchaseReturnId): void {
            $header = $this->purchaseReturnRepository->findById($purchaseReturnId);
            if (! $header instanceof DataRecord) {
                return;
            }

            $headerDiscountAmount = $this->resolveHeaderDiscountAmount(
                $header,
                $totals['subtotal'] - $totals['line_discount_total'],
            );
            $headerTaxAmount = $this->resolveTaxAmount(
                (int) $header->get('tenant_id', 0),
                $header->get('header_tax_group_id') !== null ? (int) $header->get('header_tax_group_id') : null,
                max(0.0, $totals['subtotal'] - $totals['line_discount_total'] - $headerDiscountAmount),
                $header->get('return_date') ?? $header->get('document_date') ?? null,
            );
            $debitNoteTotal = round((float) $header->get('debit_note_total', 0), 4);
            $creditNoteTotal = round((float) $header->get('credit_note_total', 0), 4);
            $lineRestockingTotal = round((float) $totals['line_restocking_total'], 4);

            $discountTotal = round($totals['line_discount_total'] + $headerDiscountAmount, 4);
            $taxTotal = round($totals['line_tax_total'] + $headerTaxAmount, 4);
            $grandTotal = round(
                $totals['subtotal']
                - $discountTotal
                + $taxTotal
                + $debitNoteTotal
                - $creditNoteTotal
                - $lineRestockingTotal,
                4,
            );

            $this->purchaseReturnRepository->update($purchaseReturnId, [
                'subtotal' => $totals['subtotal'],
                'line_tax_total' => $totals['line_tax_total'],
                'line_discount_total' => $totals['line_discount_total'],
                'line_restocking_total' => $lineRestockingTotal,
                'header_discount_amount' => $headerDiscountAmount,
                'header_tax_amount' => $headerTaxAmount,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
            ]);
        });
    }

    /** @param list<DataRecord> $lines */
    private function applyHeaderTotals(array $lines, callable $applier): void
    {
        $totals = [
            'subtotal' => 0.0,
            'line_tax_total' => 0.0,
            'line_discount_total' => 0.0,
            'line_restocking_total' => 0.0,
        ];

        foreach ($lines as $line) {
            $totals['subtotal'] += (float) $line->get('gross_amount', 0);
            $totals['line_tax_total'] += (float) $line->get('tax_amount', 0);
            $totals['line_discount_total'] += (float) $line->get('discount_amount', 0);
            $totals['line_restocking_total'] += (float) $line->get('restocking_fee', 0);
        }

        $totals['subtotal'] = round($totals['subtotal'], 4);
        $totals['line_tax_total'] = round($totals['line_tax_total'], 4);
        $totals['line_discount_total'] = round($totals['line_discount_total'], 4);
        $totals['line_restocking_total'] = round($totals['line_restocking_total'], 4);

        $applier($totals);
    }
}
