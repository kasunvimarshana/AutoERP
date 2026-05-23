<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Payment\Application\Repositories\AdvancePaymentAllocationRepositoryInterface;
use Modules\Payment\Application\Repositories\AdvancePaymentRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentAllocationRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Domain\Exceptions\PaymentIntegrityException;
use Modules\Payment\Domain\Exceptions\PaymentRecordNotFoundException;

class PaymentDomainService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentAllocationRepositoryInterface $paymentAllocations,
        private readonly AdvancePaymentRepositoryInterface $advancePayments,
        private readonly AdvancePaymentAllocationRepositoryInterface $advancePaymentAllocations,
    ) {}

    public function normalizeResourceKey(string $resource): string
    {
        return match (strtolower(trim($resource))) {
            'payment-methods', 'methods' => 'payment_methods',
            'payment-groups', 'groups' => 'payment_groups',
            'payment-allocations', 'allocations' => 'payment_allocations',
            'cash-registers', 'registers' => 'cash_registers',
            'advance-payments', 'advances' => 'advance_payments',
            'advance-payment-allocations', 'advance-allocations' => 'advance_payment_allocations',
            'write-offs', 'writeoffs' => 'write_offs',
            default => str_replace('-', '_', strtolower(trim($resource))),
        };
    }

    public function normalizeText(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>|null
     */
    public function normalizeMetadata(?array $metadata): ?array
    {
        return $metadata === [] ? null : $metadata;
    }

    public function normalizeDecimal(string|int|float|null $value): string
    {
        return number_format((float) ($value ?? 0), (int) config('payment.precision.scale', 4), '.', '');
    }

    /**
     * @param  array<int, string>  $allowed
     */
    public function normalizeEnum(string $family, mixed $value, array $allowed, mixed $default = null): mixed
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $normalized = strtolower((string) $value);

        if (! in_array($normalized, $allowed, true)) {
            throw PaymentIntegrityException::rule("Unsupported {$family} value [{$value}].");
        }

        return $normalized;
    }

    public function assertRowVersion(?int $expected, Model $record): void
    {
        if ($expected === null) {
            return;
        }

        if ((int) $record->row_version !== $expected) {
            throw PaymentIntegrityException::conflict("Record version conflict. Expected [{$expected}], current [{$record->row_version}].");
        }
    }

    public function nextRowVersion(Model $record): int
    {
        return ((int) $record->row_version) + 1;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function ensureMutable(string $resource, Model $record, array $definition, bool $updating = false): void
    {
        $immutable = config("payment.immutable.{$resource}", []);

        if (($immutable['after_create'] ?? false) && $updating) {
            throw PaymentIntegrityException::rule("{$definition['label']} records cannot be modified after creation.");
        }

        $statusColumn = $immutable['status_column'] ?? null;

        if ($statusColumn !== null && in_array((string) $record->{$statusColumn}, $immutable['statuses'] ?? [], true)) {
            throw PaymentIntegrityException::rule("{$definition['label']} is locked in status [{$record->{$statusColumn}}].");
        }
    }

    public function assertTenantPayment(int|string $tenantId, int|string|null $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        $payment = $this->payments->findForTenantById($tenantId, $id);

        if ($payment === null) {
            throw PaymentRecordNotFoundException::for('Payment', $id);
        }

        return $payment;
    }

    public function assertTenantAdvancePayment(int|string $tenantId, int|string|null $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        $advance = $this->advancePayments->findForTenantById($tenantId, $id);

        if ($advance === null) {
            throw PaymentRecordNotFoundException::for('Advance payment', $id);
        }

        return $advance;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function preparePaymentAmounts(array $attributes): array
    {
        $amount = (float) ($attributes['amount'] ?? 0);
        $exchangeRate = (float) ($attributes['exchange_rate'] ?? 1);

        $attributes['amount'] = $this->normalizeDecimal($amount);
        $attributes['exchange_rate'] = $this->normalizeDecimal($exchangeRate);
        $attributes['base_amount'] = $this->normalizeDecimal($attributes['base_amount'] ?? ($amount * $exchangeRate));

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function prepareAdvanceAmounts(array $attributes): array
    {
        $amount = (float) ($attributes['amount'] ?? 0);

        $attributes['amount'] = $this->normalizeDecimal($amount);
        $attributes['remaining_amount'] = $this->normalizeDecimal($attributes['remaining_amount'] ?? $amount);

        return $attributes;
    }

    public function assertPaymentCanAcceptAllocation(Model $payment, int|string|null $ignoreAllocationId = null): void
    {
        if ((bool) config('payment.allocation.allow_over_allocation', false)) {
            return;
        }

        $allocated = $this->sumAllocations($this->paymentAllocations->getWhere([
            'tenant_id' => $payment->tenant_id,
            'payment_id' => $payment->getKey(),
        ]), $ignoreAllocationId);

        if ($allocated > (float) $payment->amount) {
            throw PaymentIntegrityException::rule('Payment allocations cannot exceed the payment amount.');
        }
    }

    public function assertAdvanceCanAcceptAllocation(Model $advance, int|string|null $ignoreAllocationId = null): void
    {
        if ((bool) config('payment.allocation.allow_over_allocation', false)) {
            return;
        }

        $allocated = $this->sumAllocations($this->advancePaymentAllocations->getWhere([
            'tenant_id' => $advance->tenant_id,
            'advance_payment_id' => $advance->getKey(),
        ]), $ignoreAllocationId);

        if ($allocated > (float) $advance->amount) {
            throw PaymentIntegrityException::rule('Advance payment allocations cannot exceed the advance amount.');
        }
    }

    public function remainingAdvanceAmount(Model $advance): string
    {
        $allocated = $this->sumAllocations($this->advancePaymentAllocations->getWhere([
            'tenant_id' => $advance->tenant_id,
            'advance_payment_id' => $advance->getKey(),
        ]));

        return $this->normalizeDecimal(max((float) $advance->amount - $allocated, 0));
    }

    public function advanceStatus(Model $advance, string|int|float $remaining): string
    {
        $current = (string) ($advance->status ?? config('payment.advance_statuses.0', 'open'));

        if ($current === config('payment.advance_statuses.3', 'refunded')) {
            return $current;
        }

        if ((float) $remaining <= 0) {
            return config('payment.advance_statuses.2', 'fully_applied');
        }

        if ((float) $remaining < (float) $advance->amount) {
            return config('payment.advance_statuses.1', 'partially_applied');
        }

        return config('payment.advance_statuses.0', 'open');
    }

    private function sumAllocations(Collection $allocations, int|string|null $ignoreAllocationId = null): float
    {
        return (float) $allocations
            ->reject(fn (Model $allocation): bool => $ignoreAllocationId !== null && (string) $allocation->getKey() === (string) $ignoreAllocationId)
            ->sum(fn (Model $allocation): float => (float) $allocation->allocated_amount);
    }
}
