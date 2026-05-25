<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\Services;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Invoice\Application\DTOs\InvoiceRecordData;
use Modules\Invoice\Application\Repositories\InvoiceLineRepositoryInterface;
use Modules\Invoice\Application\Repositories\InvoiceReferenceRepositoryInterface;
use Modules\Invoice\Application\Repositories\InvoiceRepositoryInterface;
use Modules\Invoice\Domain\Exceptions\InvoiceIntegrityException;
use Modules\Invoice\Domain\Exceptions\InvoiceRecordNotFoundException;
use Modules\Invoice\Domain\Services\InvoiceDomainService;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;

class InvoiceService
{
    public function __construct(
        private readonly Container $container,
        private readonly TenantRepositoryInterface $tenants,
        private readonly InvoiceRepositoryInterface $invoices,
        private readonly InvoiceReferenceRepositoryInterface $references,
        private readonly InvoiceLineRepositoryInterface $lines,
        private readonly InvoiceDomainService $domain,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function definition(string $resource): array
    {
        $key = $this->domain->normalizeResourceKey($resource);
        $definition = config("invoice.resources.{$key}");

        if (! is_array($definition)) {
            throw InvoiceRecordNotFoundException::for('Invoice resource', $resource);
        }

        return ['key' => $key, ...$definition];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(string $resource, int|string $tenantId, array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->ensureTenantExists($tenantId);
        $repository = $this->repository($resource);
        $criteria = ['tenant_id' => $tenantId, ...$filters];

        return $perPage === null
            ? $repository->getWhere($criteria)
            : $repository->paginateWhere($criteria, $perPage);
    }

    public function find(string $resource, int|string $tenantId, int|string $id): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = method_exists($repository, 'findForTenantById')
            ? $repository->findForTenantById($tenantId, $id)
            : $repository->getWhere(['tenant_id' => $tenantId, 'id' => $id])->first();

        if ($record === null) {
            throw InvoiceRecordNotFoundException::for($definition['label'] ?? $resource, $id);
        }

        return $record;
    }

    public function create(string $resource, InvoiceRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $this->ensureTenantExists($data->tenantId);

        return $repository->transaction(function () use ($definition, $repository, $data): Model {
            $record = $repository->create($this->prepareAttributes($definition['key'], $data->attributes, $data->tenantId));
            $this->recalculateForResourceChange($definition['key'], $record, $data->tenantId);

            return $this->reloadRecord($definition['key'], $data->tenantId, $record->getKey());
        });
    }

    public function update(string $resource, int|string $tenantId, int|string $id, InvoiceRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = $this->find($resource, $tenantId, $id);

        $this->domain->ensureMutable($definition['key'], $record, $definition, true);
        $this->domain->assertRowVersion($data->rowVersion, $record);

        return $repository->transaction(function () use ($definition, $repository, $record, $data, $tenantId): Model {
            $attributes = [
                ...$this->prepareAttributes($definition['key'], $data->attributes, $tenantId),
                'row_version' => $this->domain->nextRowVersion($record),
            ];
            $updated = $repository->update($record, $attributes);
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
            $invoiceId = $record->invoice_id ?? ($definition['key'] === 'invoices' ? $record->getKey() : null);
            $referenceId = $record->invoice_reference_id ?? ($definition['key'] === 'references' ? $record->getKey() : null);
            $deleted = $repository->delete($record);

            if (! $deleted) {
                return false;
            }

            if ($definition['key'] === 'lines') {
                if ($referenceId !== null) {
                    $this->recalculateReference($tenantId, $referenceId);
                }

                if ($invoiceId !== null) {
                    $this->recalculateInvoice($tenantId, $invoiceId);
                }
            }

            if ($definition['key'] === 'references' && $invoiceId !== null) {
                $this->recalculateInvoice($tenantId, $invoiceId);
            }

            return true;
        });
    }

    public function recalculateInvoice(int|string $tenantId, int|string $invoiceId): Model
    {
        $invoice = $this->domain->assertTenantInvoice($tenantId, $invoiceId);

        return $this->invoices->transaction(function () use ($tenantId, $invoice): Model {
            $references = $this->references->getWhere([
                'tenant_id' => $tenantId,
                'invoice_id' => $invoice->getKey(),
            ]);

            foreach ($references as $reference) {
                $this->recalculateReference($tenantId, $reference->getKey());
            }

            $lines = $this->lines->getWhere([
                'tenant_id' => $tenantId,
                'invoice_id' => $invoice->getKey(),
            ]);
            $totals = $this->domain->calculateDocumentTotals($lines, $invoice->getAttributes());
            $totals['status'] = $this->domain->paymentStatus($invoice, [
                ...$invoice->getAttributes(),
                ...$totals,
            ]);
            $totals['row_version'] = $this->domain->nextRowVersion($invoice);

            return $this->invoices->update($invoice, $totals);
        });
    }

    public function recalculateReference(int|string $tenantId, int|string $referenceId): Model
    {
        $reference = $this->domain->assertTenantReference($tenantId, $referenceId);

        return $this->references->transaction(function () use ($tenantId, $reference): Model {
            $lines = $this->lines->getWhere([
                'tenant_id' => $tenantId,
                'invoice_reference_id' => $reference->getKey(),
            ]);
            $totals = [
                ...$this->domain->calculateDocumentTotals($lines, $reference->getAttributes()),
                'row_version' => $this->domain->nextRowVersion($reference),
            ];

            return $this->references->update($reference, $totals);
        });
    }

    private function ensureTenantExists(int|string $tenantId): void
    {
        if ($this->tenants->findById($tenantId) === null) {
            throw InvoiceRecordNotFoundException::for('Tenant', $tenantId);
        }
    }

    private function repository(string $resource): BaseRepositoryInterface
    {
        $definition = $this->definition($resource);
        $repository = $this->container->make($definition['repository']);

        if (! $repository instanceof BaseRepositoryInterface) {
            throw InvoiceIntegrityException::rule("Repository for [{$definition['key']}] must implement BaseRepositoryInterface.");
        }

        return $repository;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareAttributes(string $resource, array $attributes, int|string $tenantId): array
    {
        foreach ([
            'row_version',
            'discount_total',
            'tax_total',
            'grand_total',
            'balance',
            'paid_amount',
        ] as $calculatedColumn) {
            unset($attributes[$calculatedColumn]);
        }

        $attributes = [
            ...$this->normalizeScalars($attributes),
            'tenant_id' => $tenantId,
        ];
        $attributes['metadata'] = $this->domain->normalizeMetadata($attributes['metadata'] ?? null);

        return match ($resource) {
            'invoices' => $this->prepareInvoiceAttributes($attributes),
            'references' => $this->prepareReferenceAttributes($attributes, $tenantId),
            'lines' => $this->prepareLineAttributes($attributes, $tenantId),
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

        foreach ([
            'exchange_rate',
            'subtotal',
            'line_tax_total',
            'line_discount_total',
            'header_discount_value',
            'header_discount_amount',
            'header_tax_amount',
            'debit_note_total',
            'credit_note_total',
            'paid_amount',
            'quantity',
            'unit_price',
            'discount_value',
            'discount_amount',
            'tax_amount',
        ] as $column) {
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
    private function prepareInvoiceAttributes(array $attributes): array
    {
        $attributes['direction'] = $this->domain->normalizeEnum('direction', $attributes['direction'] ?? null, config('invoice.directions', []), config('invoice.directions.0', 'inbound'));
        $attributes['invoice_type'] = $this->domain->normalizeEnum('invoice_type', $attributes['invoice_type'] ?? null, config('invoice.invoice_types', []));
        $attributes['status'] = $this->domain->normalizeEnum('status', $attributes['status'] ?? null, config('invoice.statuses', []), config('invoice.statuses.0', 'draft'));
        $attributes['header_discount_type'] = $this->domain->normalizeEnum('discount_type', $attributes['header_discount_type'] ?? null, config('invoice.discount_types', []), config('invoice.discount_types.0', 'fixed'));
        $attributes['exchange_rate'] = $attributes['exchange_rate'] ?? $this->domain->normalizeDecimal(1);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareReferenceAttributes(array $attributes, int|string $tenantId): array
    {
        $invoice = $this->domain->assertTenantInvoice($tenantId, $attributes['invoice_id'] ?? null);
        if ($invoice === null) {
            throw InvoiceRecordNotFoundException::for('Invoice', $attributes['invoice_id'] ?? null);
        }
        $this->domain->ensureMutable('invoices', $invoice, $this->definition('invoices'), true);

        $attributes['header_discount_type'] = $this->domain->normalizeEnum('discount_type', $attributes['header_discount_type'] ?? null, config('invoice.discount_types', []), config('invoice.discount_types.0', 'fixed'));
        $attributes['exchange_rate'] = $attributes['exchange_rate'] ?? $this->domain->normalizeDecimal(1);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareLineAttributes(array $attributes, int|string $tenantId): array
    {
        $invoice = $this->domain->assertTenantInvoice($tenantId, $attributes['invoice_id'] ?? null);
        if ($invoice === null) {
            throw InvoiceRecordNotFoundException::for('Invoice', $attributes['invoice_id'] ?? null);
        }
        $this->domain->ensureMutable('invoices', $invoice, $this->definition('invoices'), true);

        $reference = $this->domain->assertTenantReference($tenantId, $attributes['invoice_reference_id'] ?? null);

        if ($reference !== null) {
            $this->domain->assertReferenceBelongsToInvoice($reference, $attributes['invoice_id']);
        }

        return $this->domain->prepareLineAmounts($attributes);
    }

    private function recalculateForResourceChange(string $resource, Model $record, int|string $tenantId): void
    {
        if ($resource === 'lines') {
            if ($record->invoice_reference_id !== null) {
                $this->recalculateReference($tenantId, $record->invoice_reference_id);
            }

            $this->recalculateInvoice($tenantId, $record->invoice_id);
        }

        if ($resource === 'references') {
            $this->recalculateReference($tenantId, $record->getKey());
            $this->recalculateInvoice($tenantId, $record->invoice_id);
        }

        if ($resource === 'invoices') {
            $this->recalculateInvoice($tenantId, $record->getKey());
        }
    }

    private function reloadRecord(string $resource, int|string $tenantId, int|string $id): Model
    {
        return $this->find($resource, $tenantId, $id);
    }
}

