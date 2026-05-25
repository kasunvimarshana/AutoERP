<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Purchase\Application\Actions\DeletePurchaseRecordAction;
use Modules\Purchase\Application\Actions\FindPurchaseRecordAction;
use Modules\Purchase\Application\Actions\ListPurchaseRecordsAction;
use Modules\Purchase\Application\Actions\PersistPurchaseRecordAction;
use Modules\Purchase\Application\DTOs\PurchaseRecordData;
use Modules\Purchase\Application\Repositories\GrnHeaderRepositoryInterface;
use Modules\Purchase\Application\Repositories\GrnLineRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseOrderLineRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseOrderRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseReturnLineRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseReturnRepositoryInterface;
use Modules\Purchase\Domain\Exceptions\PurchaseIntegrityException;
use Modules\Purchase\Domain\Exceptions\PurchaseRecordNotFoundException;
use Modules\Purchase\Domain\Services\PurchaseDomainService;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;

class PurchaseService
{
    public function __construct(
        private readonly Container $container,
        private readonly TenantRepositoryInterface $tenants,
        private readonly PurchaseOrderRepositoryInterface $purchaseOrders,
        private readonly PurchaseOrderLineRepositoryInterface $purchaseOrderLines,
        private readonly GrnHeaderRepositoryInterface $grnHeaders,
        private readonly GrnLineRepositoryInterface $grnLines,
        private readonly PurchaseReturnRepositoryInterface $purchaseReturns,
        private readonly PurchaseReturnLineRepositoryInterface $purchaseReturnLines,
        private readonly PurchaseDomainService $domain,
        private readonly ListPurchaseRecordsAction $listRecords,
        private readonly FindPurchaseRecordAction $findRecord,
        private readonly PersistPurchaseRecordAction $persistRecord,
        private readonly DeletePurchaseRecordAction $deleteRecord,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function definition(string $resource): array
    {
        $key = $this->domain->normalizeResourceKey($resource);
        $definition = config("purchase.resources.{$key}");

        if (! is_array($definition)) {
            throw PurchaseRecordNotFoundException::for('Purchase resource', $resource);
        }

        return ['key' => $key, ...$definition];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(string $resource, int|string $tenantId, array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->ensureTenantExists($tenantId);

        return $this->listRecords->execute(
            $this->repository($resource),
            ['tenant_id' => $tenantId, ...$filters],
            $perPage,
        );
    }

    public function find(string $resource, int|string $tenantId, int|string $id): Model
    {
        $definition = $this->definition($resource);

        return $this->findRecord->execute(
            $this->repository($resource),
            $definition['label'] ?? $resource,
            $tenantId,
            $id,
        );
    }

    public function create(string $resource, PurchaseRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $this->ensureTenantExists($data->tenantId);

        return $repository->transaction(function () use ($definition, $repository, $data): Model {
            $record = $this->persistRecord->create(
                $repository,
                $this->prepareAttributes($definition['key'], $data->attributes, $data->tenantId),
            );
            $this->recalculateForResourceChange($definition['key'], $record, $data->tenantId);

            return $this->reloadRecord($definition['key'], $data->tenantId, $record->getKey());
        });
    }

    public function update(string $resource, int|string $tenantId, int|string $id, PurchaseRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = $this->find($resource, $tenantId, $id);

        $this->domain->ensureMutable($definition['key'], $record, $definition, true);
        $this->domain->assertRowVersion($data->rowVersion, $record);

        return $repository->transaction(function () use ($definition, $repository, $record, $data, $tenantId): Model {
            $originalParent = $this->parentReference($definition['key'], $record);
            $updated = $this->persistRecord->update($repository, $record, [
                ...$this->prepareAttributes($definition['key'], $data->attributes, $tenantId),
                'row_version' => $this->domain->nextRowVersion($record),
            ]);
            $updatedParent = $this->parentReference($definition['key'], $updated);

            if (! $this->sameParentReference($originalParent, $updatedParent)) {
                $this->recalculateParentReference($tenantId, $originalParent);
            }

            $this->recalculateForResourceChange($definition['key'], $updated, $tenantId);

            return $this->reloadRecord($definition['key'], $tenantId, $updated->getKey());
        });
    }

    public function delete(string $resource, int|string $tenantId, int|string $id): bool
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = $this->find($resource, $tenantId, $id);

        $this->domain->ensureMutable($definition['key'], $record, $definition, true);

        return $repository->transaction(function () use ($definition, $repository, $record, $tenantId): bool {
            $parent = $this->parentReference($definition['key'], $record);
            $deleted = $this->deleteRecord->execute($repository, $record);

            if ($deleted) {
                $this->recalculateParentReference($tenantId, $parent);
            }

            return $deleted;
        });
    }

    public function recalculatePurchaseOrder(int|string $tenantId, int|string $id): Model
    {
        $order = $this->domain->assertTenantPurchaseOrder($tenantId, $id);

        return $this->purchaseOrders->transaction(function () use ($tenantId, $order): Model {
            $lines = $this->purchaseOrderLines->getWhere([
                'tenant_id' => $tenantId,
                'purchase_order_id' => $order->getKey(),
            ]);
            $totals = [
                ...$this->domain->calculateDocumentTotals($lines, $order->getAttributes(), false, true),
                'row_version' => $this->domain->nextRowVersion($order),
            ];

            return $this->purchaseOrders->update($order, $totals);
        });
    }

    public function recalculateGrnHeader(int|string $tenantId, int|string $id): Model
    {
        $grn = $this->domain->assertTenantGrnHeader($tenantId, $id);

        return $this->grnHeaders->transaction(function () use ($tenantId, $grn): Model {
            $lines = $this->grnLines->getWhere([
                'tenant_id' => $tenantId,
                'grn_header_id' => $grn->getKey(),
            ]);
            $totals = [
                ...$this->domain->calculateDocumentTotals($lines, $grn->getAttributes()),
                'row_version' => $this->domain->nextRowVersion($grn),
            ];

            return $this->grnHeaders->update($grn, $totals);
        });
    }

    public function recalculatePurchaseReturn(int|string $tenantId, int|string $id): Model
    {
        $purchaseReturn = $this->domain->assertTenantPurchaseReturn($tenantId, $id);

        return $this->purchaseReturns->transaction(function () use ($tenantId, $purchaseReturn): Model {
            $lines = $this->purchaseReturnLines->getWhere([
                'tenant_id' => $tenantId,
                'purchase_return_id' => $purchaseReturn->getKey(),
            ]);
            $totals = [
                ...$this->domain->calculateDocumentTotals($lines, $purchaseReturn->getAttributes(), true),
                'row_version' => $this->domain->nextRowVersion($purchaseReturn),
            ];

            return $this->purchaseReturns->update($purchaseReturn, $totals);
        });
    }

    private function ensureTenantExists(int|string $tenantId): void
    {
        if ($this->tenants->findById($tenantId) === null) {
            throw PurchaseRecordNotFoundException::for('Tenant', $tenantId);
        }
    }

    private function repository(string $resource): BaseRepositoryInterface
    {
        $definition = $this->definition($resource);
        $repository = $this->container->make($definition['repository']);

        if (! $repository instanceof BaseRepositoryInterface) {
            throw PurchaseIntegrityException::rule("Repository for [{$definition['key']}] must implement BaseRepositoryInterface.");
        }

        return $repository;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareAttributes(string $resource, array $attributes, int|string $tenantId): array
    {
        foreach (config('purchase.calculated_columns', []) as $calculatedColumn) {
            unset($attributes[$calculatedColumn]);
        }

        $attributes = [
            ...$this->normalizeScalars($attributes),
            'tenant_id' => $tenantId,
        ];
        $attributes['metadata'] = $this->domain->normalizeMetadata($attributes['metadata'] ?? null);

        return match ($resource) {
            'purchase_orders' => $this->preparePurchaseOrderAttributes($attributes),
            'purchase_order_lines' => $this->preparePurchaseOrderLineAttributes($attributes, $tenantId),
            'grn_headers' => $this->prepareGrnHeaderAttributes($attributes, $tenantId),
            'grn_lines' => $this->prepareGrnLineAttributes($attributes, $tenantId),
            'purchase_returns' => $this->preparePurchaseReturnAttributes($attributes, $tenantId),
            'purchase_return_lines' => $this->preparePurchaseReturnLineAttributes($attributes, $tenantId),
            default => $attributes,
        };
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalizeScalars(array $attributes): array
    {
        foreach ($attributes as $key => $value) {
            if (is_string($value)) {
                $attributes[$key] = $this->domain->normalizeText($value);
            }
        }

        foreach (config('purchase.decimal_columns', []) as $column) {
            if (array_key_exists($column, $attributes) && $attributes[$column] !== null) {
                $attributes[$column] = $this->domain->normalizeDecimal($attributes[$column]);
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function preparePurchaseOrderAttributes(array $attributes): array
    {
        $attributes['status'] = $this->domain->normalizeEnum('purchase order status', $attributes['status'] ?? null, config('purchase.order_statuses', []), config('purchase.order_statuses.0', 'draft'));
        $attributes['invoice_status'] = $this->domain->normalizeEnum('invoice status', $attributes['invoice_status'] ?? null, config('purchase.invoice_statuses', []), config('purchase.invoice_statuses.0', 'not_invoiced'));
        $attributes['header_discount_type'] = $this->domain->normalizeEnum('discount_type', $attributes['header_discount_type'] ?? null, config('purchase.discount_types', []), config('purchase.discount_types.0', 'fixed'));
        $attributes['exchange_rate'] = $attributes['exchange_rate'] ?? $this->domain->normalizeDecimal(1);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function preparePurchaseOrderLineAttributes(array $attributes, int|string $tenantId): array
    {
        $order = $this->domain->assertTenantPurchaseOrder($tenantId, $attributes['purchase_order_id'] ?? null);
        $this->assertRequiredRecord($order, 'Purchase order', $attributes['purchase_order_id'] ?? null);
        $this->domain->ensureMutable('purchase_orders', $order, $this->definition('purchase_orders'), true);

        return $this->domain->prepareLineAmounts($attributes, 'ordered_qty');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareGrnHeaderAttributes(array $attributes, int|string $tenantId): array
    {
        $attributes['status'] = $this->domain->normalizeEnum('GRN status', $attributes['status'] ?? null, config('purchase.grn_statuses', []), config('purchase.grn_statuses.0', 'draft'));
        $attributes['invoice_status'] = $this->domain->normalizeEnum('invoice status', $attributes['invoice_status'] ?? null, config('purchase.invoice_statuses', []), config('purchase.invoice_statuses.0', 'not_invoiced'));
        $attributes['header_discount_type'] = $this->domain->normalizeEnum('discount_type', $attributes['header_discount_type'] ?? null, config('purchase.discount_types', []), config('purchase.discount_types.0', 'fixed'));
        $attributes['exchange_rate'] = $attributes['exchange_rate'] ?? $this->domain->normalizeDecimal(1);

        $order = $this->domain->assertTenantPurchaseOrder($tenantId, $attributes['purchase_order_id'] ?? null);

        if ($order !== null) {
            $this->assertSameReference($order, 'supplier_id', $attributes['supplier_id'] ?? null, 'GRN supplier must match the purchase order supplier.');
            $this->assertSameReference($order, 'warehouse_id', $attributes['warehouse_id'] ?? null, 'GRN warehouse must match the purchase order warehouse.');
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareGrnLineAttributes(array $attributes, int|string $tenantId): array
    {
        $grn = $this->domain->assertTenantGrnHeader($tenantId, $attributes['grn_header_id'] ?? null);
        $this->assertRequiredRecord($grn, 'GRN header', $attributes['grn_header_id'] ?? null);
        $this->domain->ensureMutable('grn_headers', $grn, $this->definition('grn_headers'), true);

        $purchaseOrderLine = $this->domain->assertTenantPurchaseOrderLine($tenantId, $attributes['purchase_order_line_id'] ?? null);

        if ($purchaseOrderLine !== null) {
            if ($grn->purchase_order_id !== null) {
                $this->domain->assertLineBelongsToHeader(
                    $purchaseOrderLine,
                    'purchase_order_id',
                    $grn,
                    'purchase_order_id',
                    'GRN line purchase order reference must belong to the GRN header purchase order.',
                );
            }

            $this->assertLineItemCompatible($purchaseOrderLine, $attributes);
        }

        return $this->domain->prepareLineAmounts($attributes, 'received_qty');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function preparePurchaseReturnAttributes(array $attributes, int|string $tenantId): array
    {
        $attributes['status'] = $this->domain->normalizeEnum('purchase return status', $attributes['status'] ?? null, config('purchase.return_statuses', []), config('purchase.return_statuses.0', 'draft'));
        $attributes['header_discount_type'] = $this->domain->normalizeEnum('discount_type', $attributes['header_discount_type'] ?? null, config('purchase.discount_types', []), config('purchase.discount_types.0', 'fixed'));
        $attributes['exchange_rate'] = $attributes['exchange_rate'] ?? $this->domain->normalizeDecimal(1);

        $order = $this->domain->assertTenantPurchaseOrder($tenantId, $attributes['original_purchase_order_id'] ?? null);
        $grn = $this->domain->assertTenantGrnHeader($tenantId, $attributes['original_grn_id'] ?? null);
        $this->domain->assertTenantInvoice($tenantId, $attributes['original_invoice_id'] ?? null);

        if ($order !== null) {
            $this->assertSameReference($order, 'supplier_id', $attributes['supplier_id'] ?? null, 'Purchase return supplier must match the original purchase order supplier.');
        }

        if ($grn !== null) {
            $this->assertSameReference($grn, 'supplier_id', $attributes['supplier_id'] ?? null, 'Purchase return supplier must match the original GRN supplier.');

            if ($order !== null) {
                $this->assertSameReference($grn, 'purchase_order_id', $order->getKey(), 'Purchase return original GRN must belong to the original purchase order.');
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function preparePurchaseReturnLineAttributes(array $attributes, int|string $tenantId): array
    {
        $returnHeader = $this->domain->assertTenantPurchaseReturn($tenantId, $attributes['purchase_return_id'] ?? null);
        $this->assertRequiredRecord($returnHeader, 'Purchase return', $attributes['purchase_return_id'] ?? null);
        $this->domain->ensureMutable('purchase_returns', $returnHeader, $this->definition('purchase_returns'), true);

        $grnLine = $this->domain->assertTenantGrnLine($tenantId, $attributes['original_grn_line_id'] ?? null);
        $purchaseOrderLine = $this->domain->assertTenantPurchaseOrderLine($tenantId, $attributes['original_purchase_order_line_id'] ?? null);
        $this->domain->assertPurchaseReturnLineReferences($returnHeader, $grnLine, $purchaseOrderLine);

        if ($grnLine !== null) {
            $this->assertLineItemCompatible($grnLine, $attributes);
        }

        if ($purchaseOrderLine !== null) {
            $this->assertLineItemCompatible($purchaseOrderLine, $attributes);
        }

        $attributes['condition'] = $this->domain->normalizeEnum('return condition', $attributes['condition'] ?? null, config('purchase.return_conditions', []), null);
        $attributes['disposition'] = $this->domain->normalizeEnum('return disposition', $attributes['disposition'] ?? null, config('purchase.return_dispositions', []), null);

        return $this->domain->prepareLineAmounts($attributes, 'return_qty');
    }

    private function recalculateForResourceChange(string $resource, Model $record, int|string $tenantId): void
    {
        match ($resource) {
            'purchase_orders' => $this->recalculatePurchaseOrder($tenantId, $record->getKey()),
            'purchase_order_lines' => $this->recalculatePurchaseOrder($tenantId, $record->purchase_order_id),
            'grn_headers' => $this->recalculateGrnHeader($tenantId, $record->getKey()),
            'grn_lines' => $this->recalculateGrnHeader($tenantId, $record->grn_header_id),
            'purchase_returns' => $this->recalculatePurchaseReturn($tenantId, $record->getKey()),
            'purchase_return_lines' => $this->recalculatePurchaseReturn($tenantId, $record->purchase_return_id),
            default => null,
        };
    }

    private function reloadRecord(string $resource, int|string $tenantId, int|string $id): Model
    {
        return $this->find($resource, $tenantId, $id);
    }

    /**
     * @return array{resource: string, id: int|string}|null
     */
    private function parentReference(string $resource, Model $record): ?array
    {
        return match ($resource) {
            'purchase_order_lines' => ['resource' => 'purchase_orders', 'id' => $record->purchase_order_id],
            'grn_lines' => ['resource' => 'grn_headers', 'id' => $record->grn_header_id],
            'purchase_return_lines' => ['resource' => 'purchase_returns', 'id' => $record->purchase_return_id],
            default => null,
        };
    }

    /**
     * @param  array{resource: string, id: int|string}|null  $parent
     */
    private function recalculateParentReference(int|string $tenantId, ?array $parent): void
    {
        if ($parent === null) {
            return;
        }

        match ($parent['resource']) {
            'purchase_orders' => $this->recalculatePurchaseOrder($tenantId, $parent['id']),
            'grn_headers' => $this->recalculateGrnHeader($tenantId, $parent['id']),
            'purchase_returns' => $this->recalculatePurchaseReturn($tenantId, $parent['id']),
            default => null,
        };
    }

    /**
     * @param  array{resource: string, id: int|string}|null  $left
     * @param  array{resource: string, id: int|string}|null  $right
     */
    private function sameParentReference(?array $left, ?array $right): bool
    {
        if ($left === null || $right === null) {
            return $left === $right;
        }

        return $left['resource'] === $right['resource'] && (string) $left['id'] === (string) $right['id'];
    }

    private function assertSameReference(Model $source, string $column, mixed $value, string $message): void
    {
        if ($value !== null && (string) $source->{$column} !== (string) $value) {
            throw PurchaseIntegrityException::rule($message);
        }
    }

    private function assertRequiredRecord(?Model $record, string $resource, int|string|null $id): void
    {
        if ($record === null) {
            throw PurchaseRecordNotFoundException::for($resource, $id);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function assertLineItemCompatible(Model $source, array $attributes): void
    {
        foreach (['item_id', 'variant_id', 'uom_id'] as $column) {
            if (($attributes[$column] ?? null) !== null && (string) $source->{$column} !== (string) $attributes[$column]) {
                throw PurchaseIntegrityException::rule("Referenced line {$column} must match the new line {$column}.");
            }
        }
    }
}

