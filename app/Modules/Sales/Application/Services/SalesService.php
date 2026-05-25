<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Sales\Application\Actions\DeleteSalesRecordAction;
use Modules\Sales\Application\Actions\FindSalesRecordAction;
use Modules\Sales\Application\Actions\ListSalesRecordsAction;
use Modules\Sales\Application\Actions\PersistSalesRecordAction;
use Modules\Sales\Application\DTOs\SalesRecordData;
use Modules\Sales\Application\Repositories\GdnHeaderRepositoryInterface;
use Modules\Sales\Application\Repositories\GdnLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesOrderLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesOrderRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesReturnLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesReturnRepositoryInterface;
use Modules\Sales\Domain\Exceptions\SalesIntegrityException;
use Modules\Sales\Domain\Exceptions\SalesRecordNotFoundException;
use Modules\Sales\Domain\Services\SalesDomainService;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;

class SalesService
{
    public function __construct(
        private readonly Container $container,
        private readonly TenantRepositoryInterface $tenants,
        private readonly SalesOrderRepositoryInterface $salesOrders,
        private readonly SalesOrderLineRepositoryInterface $salesOrderLines,
        private readonly GdnHeaderRepositoryInterface $gdnHeaders,
        private readonly GdnLineRepositoryInterface $gdnLines,
        private readonly SalesReturnRepositoryInterface $salesReturns,
        private readonly SalesReturnLineRepositoryInterface $salesReturnLines,
        private readonly SalesDomainService $domain,
        private readonly ListSalesRecordsAction $listRecords,
        private readonly FindSalesRecordAction $findRecord,
        private readonly PersistSalesRecordAction $persistRecord,
        private readonly DeleteSalesRecordAction $deleteRecord,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function definition(string $resource): array
    {
        $key = $this->domain->normalizeResourceKey($resource);
        $definition = config("sales.resources.{$key}");

        if (! is_array($definition)) {
            throw SalesRecordNotFoundException::for('Sales resource', $resource);
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

    public function create(string $resource, SalesRecordData $data): Model
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

    public function update(string $resource, int|string $tenantId, int|string $id, SalesRecordData $data): Model
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

    public function recalculateSalesOrder(int|string $tenantId, int|string $id): Model
    {
        $order = $this->domain->assertTenantSalesOrder($tenantId, $id);

        return $this->salesOrders->transaction(function () use ($tenantId, $order): Model {
            $lines = $this->salesOrderLines->getWhere([
                'tenant_id' => $tenantId,
                'sales_order_id' => $order->getKey(),
            ]);
            $totals = [
                ...$this->domain->calculateDocumentTotals($lines, $order->getAttributes(), false, true),
                'row_version' => $this->domain->nextRowVersion($order),
            ];

            return $this->salesOrders->update($order, $totals);
        });
    }

    public function recalculateGdnHeader(int|string $tenantId, int|string $id): Model
    {
        $gdn = $this->domain->assertTenantGdnHeader($tenantId, $id);

        return $this->gdnHeaders->transaction(function () use ($tenantId, $gdn): Model {
            $lines = $this->gdnLines->getWhere([
                'tenant_id' => $tenantId,
                'gdn_header_id' => $gdn->getKey(),
            ]);
            $totals = [
                ...$this->domain->calculateDocumentTotals($lines, $gdn->getAttributes()),
                'row_version' => $this->domain->nextRowVersion($gdn),
            ];

            return $this->gdnHeaders->update($gdn, $totals);
        });
    }

    public function recalculateSalesReturn(int|string $tenantId, int|string $id): Model
    {
        $salesReturn = $this->domain->assertTenantSalesReturn($tenantId, $id);

        return $this->salesReturns->transaction(function () use ($tenantId, $salesReturn): Model {
            $lines = $this->salesReturnLines->getWhere([
                'tenant_id' => $tenantId,
                'sales_return_id' => $salesReturn->getKey(),
            ]);
            $totals = [
                ...$this->domain->calculateDocumentTotals($lines, $salesReturn->getAttributes(), true),
                'row_version' => $this->domain->nextRowVersion($salesReturn),
            ];

            return $this->salesReturns->update($salesReturn, $totals);
        });
    }

    private function ensureTenantExists(int|string $tenantId): void
    {
        if ($this->tenants->findById($tenantId) === null) {
            throw SalesRecordNotFoundException::for('Tenant', $tenantId);
        }
    }

    private function repository(string $resource): BaseRepositoryInterface
    {
        $definition = $this->definition($resource);
        $repository = $this->container->make($definition['repository']);

        if (! $repository instanceof BaseRepositoryInterface) {
            throw SalesIntegrityException::rule("Repository for [{$definition['key']}] must implement BaseRepositoryInterface.");
        }

        return $repository;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareAttributes(string $resource, array $attributes, int|string $tenantId): array
    {
        foreach (config('sales.calculated_columns', []) as $calculatedColumn) {
            unset($attributes[$calculatedColumn]);
        }

        $attributes = [
            ...$this->normalizeScalars($attributes),
            'tenant_id' => $tenantId,
        ];
        $attributes['metadata'] = $this->domain->normalizeMetadata($attributes['metadata'] ?? null);

        return match ($resource) {
            'sales_orders' => $this->prepareSalesOrderAttributes($attributes),
            'sales_order_lines' => $this->prepareSalesOrderLineAttributes($attributes, $tenantId),
            'gdn_headers' => $this->prepareGdnHeaderAttributes($attributes, $tenantId),
            'gdn_lines' => $this->prepareGdnLineAttributes($attributes, $tenantId),
            'sales_returns' => $this->prepareSalesReturnAttributes($attributes, $tenantId),
            'sales_return_lines' => $this->prepareSalesReturnLineAttributes($attributes, $tenantId),
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

        foreach (config('sales.decimal_columns', []) as $column) {
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
    private function prepareSalesOrderAttributes(array $attributes): array
    {
        $attributes['status'] = $this->domain->normalizeEnum('sales order status', $attributes['status'] ?? null, config('sales.order_statuses', []), config('sales.order_statuses.0', 'draft'));
        $attributes['invoice_status'] = $this->domain->normalizeEnum('invoice status', $attributes['invoice_status'] ?? null, config('sales.invoice_statuses', []), config('sales.invoice_statuses.0', 'not_invoiced'));
        $attributes['header_discount_type'] = $this->domain->normalizeEnum('discount_type', $attributes['header_discount_type'] ?? null, config('sales.discount_types', []), config('sales.discount_types.0', 'fixed'));
        $attributes['exchange_rate'] = $attributes['exchange_rate'] ?? $this->domain->normalizeDecimal(1);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareSalesOrderLineAttributes(array $attributes, int|string $tenantId): array
    {
        $order = $this->domain->assertTenantSalesOrder($tenantId, $attributes['sales_order_id'] ?? null);
        $this->assertRequiredRecord($order, 'Sales order', $attributes['sales_order_id'] ?? null);
        $this->domain->ensureMutable('sales_orders', $order, $this->definition('sales_orders'), true);

        return $this->domain->prepareLineAmounts($attributes, 'ordered_qty');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareGdnHeaderAttributes(array $attributes, int|string $tenantId): array
    {
        $attributes['status'] = $this->domain->normalizeEnum('GDN status', $attributes['status'] ?? null, config('sales.gdn_statuses', []), config('sales.gdn_statuses.0', 'draft'));
        $attributes['invoice_status'] = $this->domain->normalizeEnum('invoice status', $attributes['invoice_status'] ?? null, config('sales.invoice_statuses', []), config('sales.invoice_statuses.0', 'not_invoiced'));
        $attributes['header_discount_type'] = $this->domain->normalizeEnum('discount_type', $attributes['header_discount_type'] ?? null, config('sales.discount_types', []), config('sales.discount_types.0', 'fixed'));
        $attributes['exchange_rate'] = $attributes['exchange_rate'] ?? $this->domain->normalizeDecimal(1);

        $order = $this->domain->assertTenantSalesOrder($tenantId, $attributes['sales_order_id'] ?? null);

        if ($order !== null) {
            $this->assertSameReference($order, 'customer_id', $attributes['customer_id'] ?? null, 'GDN customer must match the sales order customer.');
            $this->assertSameReference($order, 'warehouse_id', $attributes['warehouse_id'] ?? null, 'GDN warehouse must match the sales order warehouse.');
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareGdnLineAttributes(array $attributes, int|string $tenantId): array
    {
        $gdn = $this->domain->assertTenantGdnHeader($tenantId, $attributes['gdn_header_id'] ?? null);
        $this->assertRequiredRecord($gdn, 'GDN header', $attributes['gdn_header_id'] ?? null);
        $this->domain->ensureMutable('gdn_headers', $gdn, $this->definition('gdn_headers'), true);

        $salesOrderLine = $this->domain->assertTenantSalesOrderLine($tenantId, $attributes['sales_order_line_id'] ?? null);

        if ($salesOrderLine !== null) {
            if ($gdn->sales_order_id !== null) {
                $this->domain->assertLineBelongsToHeader(
                    $salesOrderLine,
                    'sales_order_id',
                    $gdn,
                    'sales_order_id',
                    'GDN line sales order reference must belong to the GDN header sales order.',
                );
            }

            $this->assertLineItemCompatible($salesOrderLine, $attributes);
        }

        return $this->domain->prepareLineAmounts($attributes, 'delivered_qty');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareSalesReturnAttributes(array $attributes, int|string $tenantId): array
    {
        $attributes['status'] = $this->domain->normalizeEnum('sales return status', $attributes['status'] ?? null, config('sales.return_statuses', []), config('sales.return_statuses.0', 'draft'));
        $attributes['header_discount_type'] = $this->domain->normalizeEnum('discount_type', $attributes['header_discount_type'] ?? null, config('sales.discount_types', []), config('sales.discount_types.0', 'fixed'));
        $attributes['exchange_rate'] = $attributes['exchange_rate'] ?? $this->domain->normalizeDecimal(1);

        $order = $this->domain->assertTenantSalesOrder($tenantId, $attributes['original_sales_order_id'] ?? null);
        $gdn = $this->domain->assertTenantGdnHeader($tenantId, $attributes['original_gdn_id'] ?? null);
        $this->domain->assertTenantInvoice($tenantId, $attributes['original_invoice_id'] ?? null);

        if ($order !== null) {
            $this->assertSameReference($order, 'customer_id', $attributes['customer_id'] ?? null, 'Sales return customer must match the original sales order customer.');
        }

        if ($gdn !== null) {
            $this->assertSameReference($gdn, 'customer_id', $attributes['customer_id'] ?? null, 'Sales return customer must match the original GDN customer.');

            if ($order !== null) {
                $this->assertSameReference($gdn, 'sales_order_id', $order->getKey(), 'Sales return original GDN must belong to the original sales order.');
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareSalesReturnLineAttributes(array $attributes, int|string $tenantId): array
    {
        $returnHeader = $this->domain->assertTenantSalesReturn($tenantId, $attributes['sales_return_id'] ?? null);
        $this->assertRequiredRecord($returnHeader, 'Sales return', $attributes['sales_return_id'] ?? null);
        $this->domain->ensureMutable('sales_returns', $returnHeader, $this->definition('sales_returns'), true);

        $gdnLine = $this->domain->assertTenantGdnLine($tenantId, $attributes['original_gdn_line_id'] ?? null);
        $salesOrderLine = $this->domain->assertTenantSalesOrderLine($tenantId, $attributes['original_sales_order_line_id'] ?? null);
        $this->domain->assertSalesReturnLineReferences($returnHeader, $gdnLine, $salesOrderLine);

        if ($gdnLine !== null) {
            $this->assertLineItemCompatible($gdnLine, $attributes);
        }

        if ($salesOrderLine !== null) {
            $this->assertLineItemCompatible($salesOrderLine, $attributes);
        }

        $attributes['condition'] = $this->domain->normalizeEnum('return condition', $attributes['condition'] ?? null, config('sales.return_conditions', []), null);
        $attributes['disposition'] = $this->domain->normalizeEnum('return disposition', $attributes['disposition'] ?? null, config('sales.return_dispositions', []), null);

        return $this->domain->prepareLineAmounts($attributes, 'return_qty');
    }

    private function recalculateForResourceChange(string $resource, Model $record, int|string $tenantId): void
    {
        match ($resource) {
            'sales_orders' => $this->recalculateSalesOrder($tenantId, $record->getKey()),
            'sales_order_lines' => $this->recalculateSalesOrder($tenantId, $record->sales_order_id),
            'gdn_headers' => $this->recalculateGdnHeader($tenantId, $record->getKey()),
            'gdn_lines' => $this->recalculateGdnHeader($tenantId, $record->gdn_header_id),
            'sales_returns' => $this->recalculateSalesReturn($tenantId, $record->getKey()),
            'sales_return_lines' => $this->recalculateSalesReturn($tenantId, $record->sales_return_id),
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
            'sales_order_lines' => ['resource' => 'sales_orders', 'id' => $record->sales_order_id],
            'gdn_lines' => ['resource' => 'gdn_headers', 'id' => $record->gdn_header_id],
            'sales_return_lines' => ['resource' => 'sales_returns', 'id' => $record->sales_return_id],
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
            'sales_orders' => $this->recalculateSalesOrder($tenantId, $parent['id']),
            'gdn_headers' => $this->recalculateGdnHeader($tenantId, $parent['id']),
            'sales_returns' => $this->recalculateSalesReturn($tenantId, $parent['id']),
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
            throw SalesIntegrityException::rule($message);
        }
    }

    private function assertRequiredRecord(?Model $record, string $resource, int|string|null $id): void
    {
        if ($record === null) {
            throw SalesRecordNotFoundException::for($resource, $id);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function assertLineItemCompatible(Model $source, array $attributes): void
    {
        foreach (['item_id', 'variant_id', 'uom_id'] as $column) {
            if (($attributes[$column] ?? null) !== null && (string) $source->{$column} !== (string) $attributes[$column]) {
                throw SalesIntegrityException::rule("Referenced line {$column} must match the new line {$column}.");
            }
        }
    }
}

