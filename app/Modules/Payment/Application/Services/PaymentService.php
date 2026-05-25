<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Services;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Invoice\Application\Repositories\InvoiceReferenceRepositoryInterface;
use Modules\Invoice\Application\Repositories\InvoiceRepositoryInterface;
use Modules\Invoice\Application\Services\InvoiceService;
use Modules\Payment\Application\DTOs\PaymentRecordData;
use Modules\Payment\Application\Repositories\AdvancePaymentAllocationRepositoryInterface;
use Modules\Payment\Application\Repositories\AdvancePaymentRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentAllocationRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Domain\Exceptions\PaymentIntegrityException;
use Modules\Payment\Domain\Exceptions\PaymentRecordNotFoundException;
use Modules\Payment\Domain\Services\PaymentDomainService;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;

class PaymentService
{
    public function __construct(
        private readonly Container $container,
        private readonly TenantRepositoryInterface $tenants,
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentAllocationRepositoryInterface $paymentAllocations,
        private readonly AdvancePaymentRepositoryInterface $advancePayments,
        private readonly AdvancePaymentAllocationRepositoryInterface $advancePaymentAllocations,
        private readonly PaymentDomainService $domain,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function definition(string $resource): array
    {
        $key = $this->domain->normalizeResourceKey($resource);
        $definition = config("payment.resources.{$key}");

        if (! is_array($definition)) {
            throw PaymentRecordNotFoundException::for('Payment resource', $resource);
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
            throw PaymentRecordNotFoundException::for($definition['label'] ?? $resource, $id);
        }

        return $record;
    }

    public function create(string $resource, PaymentRecordData $data): Model
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

    public function update(string $resource, int|string $tenantId, int|string $id, PaymentRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = $this->find($resource, $tenantId, $id);

        $this->domain->ensureMutable($definition['key'], $record, $definition, true);
        $this->domain->assertRowVersion($data->rowVersion, $record);

        return $repository->transaction(function () use ($definition, $repository, $record, $data, $tenantId): Model {
            $originalDocument = $this->documentReference($definition['key'], $record);
            $updated = $repository->update($record, [
                ...$this->prepareAttributes($definition['key'], $data->attributes, $tenantId),
                'row_version' => $this->domain->nextRowVersion($record),
            ]);
            $this->recalculateDocumentReference($tenantId, $originalDocument);
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
            $advancePaymentId = $record->advance_payment_id ?? null;
            $document = $this->documentReference($definition['key'], $record);
            $deleted = $repository->delete($record);

            if ($deleted && $definition['key'] === 'advance_payment_allocations' && $advancePaymentId !== null) {
                $this->recalculateAdvancePayment($tenantId, $advancePaymentId);
            }

            if ($deleted) {
                $this->recalculateDocumentReference($tenantId, $document);
            }

            return $deleted;
        });
    }

    public function postPayment(int|string $tenantId, int|string $id): Model
    {
        $payment = $this->find('payments', $tenantId, $id);
        $this->domain->ensureMutable('payments', $payment, $this->definition('payments'), true);
        $this->domain->assertPaymentCanAcceptAllocation($payment);

        return $this->payments->transaction(function () use ($tenantId, $payment): Model {
            $updated = $this->payments->update($payment, [
                'status' => config('payment.payment_statuses.1', 'posted'),
                'row_version' => $this->domain->nextRowVersion($payment),
            ]);

            foreach ($this->paymentAllocations->getWhere(['tenant_id' => $tenantId, 'payment_id' => $payment->getKey()]) as $allocation) {
                $this->recalculateInvoiceDocumentPaidAmount($tenantId, $allocation->document_type, $allocation->document_id);
            }

            return $updated;
        });
    }

    public function recalculateAdvancePayment(int|string $tenantId, int|string $advancePaymentId): Model
    {
        $advance = $this->domain->assertTenantAdvancePayment($tenantId, $advancePaymentId);
        $this->domain->assertAdvanceCanAcceptAllocation($advance);
        $remaining = $this->domain->remainingAdvanceAmount($advance);

        return $this->advancePayments->transaction(fn (): Model => $this->advancePayments->update($advance, [
            'remaining_amount' => $remaining,
            'status' => $this->domain->advanceStatus($advance, $remaining),
            'row_version' => $this->domain->nextRowVersion($advance),
        ]));
    }

    private function ensureTenantExists(int|string $tenantId): void
    {
        if ($this->tenants->findById($tenantId) === null) {
            throw PaymentRecordNotFoundException::for('Tenant', $tenantId);
        }
    }

    private function repository(string $resource): BaseRepositoryInterface
    {
        $definition = $this->definition($resource);
        $repository = $this->container->make($definition['repository']);

        if (! $repository instanceof BaseRepositoryInterface) {
            throw PaymentIntegrityException::rule("Repository for [{$definition['key']}] must implement BaseRepositoryInterface.");
        }

        return $repository;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareAttributes(string $resource, array $attributes, int|string $tenantId): array
    {
        unset($attributes['row_version']);

        $attributes = [
            ...$this->normalizeScalars($attributes),
            'tenant_id' => $tenantId,
        ];
        $attributes['metadata'] = $this->domain->normalizeMetadata($attributes['metadata'] ?? null);

        return match ($resource) {
            'payment_methods' => $this->preparePaymentMethodAttributes($attributes),
            'payments' => $this->preparePaymentAttributes($attributes),
            'payment_allocations' => $this->preparePaymentAllocationAttributes($attributes, $tenantId),
            'checks' => $this->prepareCheckAttributes($attributes),
            'advance_payments' => $this->prepareAdvancePaymentAttributes($attributes),
            'advance_payment_allocations' => $this->prepareAdvanceAllocationAttributes($attributes, $tenantId),
            'cash_registers' => $this->prepareCashRegisterAttributes($attributes),
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

        foreach (['amount', 'allocated_amount', 'remaining_amount', 'opening_balance', 'current_balance', 'exchange_rate', 'base_amount'] as $column) {
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
    private function preparePaymentMethodAttributes(array $attributes): array
    {
        $attributes['type'] = $this->domain->normalizeEnum('payment method type', $attributes['type'] ?? null, config('payment.payment_method_types', []), config('payment.payment_method_types.1', 'bank_transfer'));
        $attributes['is_active'] = $attributes['is_active'] ?? true;

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function preparePaymentAttributes(array $attributes): array
    {
        $attributes['direction'] = $this->domain->normalizeEnum('direction', $attributes['direction'] ?? null, config('payment.directions', []), config('payment.directions.0', 'inbound'));
        $attributes['status'] = $this->domain->normalizeEnum('payment status', $attributes['status'] ?? null, config('payment.payment_statuses', []), config('payment.payment_statuses.0', 'draft'));

        return $this->domain->preparePaymentAmounts($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function preparePaymentAllocationAttributes(array $attributes, int|string $tenantId): array
    {
        $payment = $this->domain->assertTenantPayment($tenantId, $attributes['payment_id'] ?? null);
        if ((string) $payment->status !== config('payment.payment_statuses.0', 'draft')) {
            throw PaymentIntegrityException::rule('Payment allocations can only be changed while the payment is draft.');
        }

        $this->assertSupportedInvoiceDocument($tenantId, $attributes['document_type'] ?? null, $attributes['document_id'] ?? null);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareCheckAttributes(array $attributes): array
    {
        $attributes['type'] = $this->domain->normalizeEnum('check type', $attributes['type'] ?? null, config('payment.check_types', []));
        $attributes['status'] = $this->domain->normalizeEnum('check status', $attributes['status'] ?? null, config('payment.check_statuses', []), config('payment.check_statuses.0', 'pending'));

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareAdvancePaymentAttributes(array $attributes): array
    {
        $attributes['type'] = $this->domain->normalizeEnum('advance payment type', $attributes['type'] ?? null, config('payment.advance_types', []), null);
        $attributes['status'] = $this->domain->normalizeEnum('advance payment status', $attributes['status'] ?? null, config('payment.advance_statuses', []), config('payment.advance_statuses.0', 'open'));

        return $this->domain->prepareAdvanceAmounts($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareAdvanceAllocationAttributes(array $attributes, int|string $tenantId): array
    {
        $advance = $this->domain->assertTenantAdvancePayment($tenantId, $attributes['advance_payment_id'] ?? null);
        if ((string) $advance->status === config('payment.advance_statuses.3', 'refunded')) {
            throw PaymentIntegrityException::rule('Refunded advance payments cannot be allocated.');
        }

        $this->assertSupportedInvoiceDocument($tenantId, $attributes['document_type'] ?? null, $attributes['document_id'] ?? null);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareCashRegisterAttributes(array $attributes): array
    {
        $attributes['opening_balance'] = $attributes['opening_balance'] ?? $this->domain->normalizeDecimal(0);
        $attributes['current_balance'] = $attributes['current_balance'] ?? $attributes['opening_balance'];
        $attributes['is_active'] = $attributes['is_active'] ?? true;

        return $attributes;
    }

    private function recalculateForResourceChange(string $resource, Model $record, int|string $tenantId): void
    {
        if ($resource === 'payment_allocations') {
            $payment = $this->domain->assertTenantPayment($tenantId, $record->payment_id);
            $this->domain->assertPaymentCanAcceptAllocation($payment);
            $this->recalculateInvoiceDocumentPaidAmount($tenantId, $record->document_type, $record->document_id);
        }

        if ($resource === 'advance_payment_allocations') {
            $this->recalculateAdvancePayment($tenantId, $record->advance_payment_id);
            $this->recalculateInvoiceDocumentPaidAmount($tenantId, $record->document_type, $record->document_id);
        }
    }

    private function reloadRecord(string $resource, int|string $tenantId, int|string $id): Model
    {
        return $this->find($resource, $tenantId, $id);
    }

    /**
     * @return array{type: string, id: int|string}|null
     */
    private function documentReference(string $resource, Model $record): ?array
    {
        if (! in_array($resource, ['payment_allocations', 'advance_payment_allocations'], true)) {
            return null;
        }

        return [
            'type' => $record->document_type,
            'id' => $record->document_id,
        ];
    }

    /**
     * @param  array{type: string, id: int|string}|null  $document
     */
    private function recalculateDocumentReference(int|string $tenantId, ?array $document): void
    {
        if ($document === null) {
            return;
        }

        $this->recalculateInvoiceDocumentPaidAmount($tenantId, $document['type'], $document['id']);
    }

    private function assertSupportedInvoiceDocument(int|string $tenantId, mixed $documentType, mixed $documentId): void
    {
        $resource = $this->invoiceResourceForDocumentType($documentType);

        if ($resource === null) {
            throw PaymentIntegrityException::rule("Unsupported allocation document type [{$documentType}].");
        }

        $this->container->make(InvoiceService::class)->find($resource, $tenantId, $documentId);
    }

    private function recalculateInvoiceDocumentPaidAmount(int|string $tenantId, mixed $documentType, mixed $documentId): void
    {
        $resource = $this->invoiceResourceForDocumentType($documentType);

        if ($resource === null || $documentId === null) {
            return;
        }

        $paidAmount = $this->domain->normalizeDecimal(
            $this->postedPaymentAllocationTotal($tenantId, $documentType, $documentId)
            + $this->advanceAllocationTotal($tenantId, $documentType, $documentId)
        );

        $repository = $resource === 'invoices'
            ? $this->container->make(InvoiceRepositoryInterface::class)
            : $this->container->make(InvoiceReferenceRepositoryInterface::class);
        $record = $repository->findForTenantById($tenantId, $documentId);

        if ($record === null) {
            return;
        }

        $repository->update($record, [
            'paid_amount' => $paidAmount,
            'row_version' => $this->domain->nextRowVersion($record),
        ]);

        $invoices = $this->container->make(InvoiceService::class);

        if ($resource === 'invoices') {
            $invoices->recalculateInvoice($tenantId, $documentId);

            return;
        }

        $invoices->recalculateReference($tenantId, $documentId);
        $reloaded = $repository->findForTenantById($tenantId, $documentId);

        if ($reloaded !== null) {
            $invoices->recalculateInvoice($tenantId, $reloaded->invoice_id);
        }
    }

    private function postedPaymentAllocationTotal(int|string $tenantId, mixed $documentType, mixed $documentId): float
    {
        $total = 0.0;
        $postedStatuses = [
            config('payment.payment_statuses.1', 'posted'),
            config('payment.payment_statuses.2', 'reconciled'),
        ];

        foreach ($this->invoiceDocumentTypeAliases($documentType) as $alias) {
            foreach ($this->paymentAllocations->getWhere([
                'tenant_id' => $tenantId,
                'document_type' => $alias,
                'document_id' => $documentId,
            ]) as $allocation) {
                $payment = $this->domain->assertTenantPayment($tenantId, $allocation->payment_id);

                if (in_array((string) $payment->status, $postedStatuses, true)) {
                    $total += (float) $allocation->allocated_amount;
                }
            }
        }

        return $total;
    }

    private function advanceAllocationTotal(int|string $tenantId, mixed $documentType, mixed $documentId): float
    {
        $total = 0.0;

        foreach ($this->invoiceDocumentTypeAliases($documentType) as $alias) {
            $total += (float) $this->advancePaymentAllocations->getWhere([
                'tenant_id' => $tenantId,
                'document_type' => $alias,
                'document_id' => $documentId,
            ])
                ->sum(fn (Model $allocation): float => (float) $allocation->allocated_amount);
        }

        return $total;
    }

    private function invoiceResourceForDocumentType(mixed $documentType): ?string
    {
        return match (strtolower((string) $documentType)) {
            'invoice', 'invoices' => 'invoices',
            'invoice_reference', 'invoice-reference', 'invoice_references', 'invoice-references', 'reference', 'references' => 'references',
            default => null,
        };
    }

    /**
     * @return array<int, string>
     */
    private function invoiceDocumentTypeAliases(mixed $documentType): array
    {
        return match ($this->invoiceResourceForDocumentType($documentType)) {
            'invoices' => ['invoice', 'invoices'],
            'references' => ['invoice_reference', 'invoice-reference', 'invoice_references', 'invoice-references', 'reference', 'references'],
            default => [(string) $documentType],
        };
    }
}

