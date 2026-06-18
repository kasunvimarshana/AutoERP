<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Models\FinanceAccount;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Services\PaymentCreationService;

final class PurchasePaymentIntegrationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PaymentCreationService $payments,
    ) {}

    /**
     * @return array{payment_methods: list<array<string, mixed>>, payment_accounts: list<array<string, mixed>>}
     */
    public function context(int $tenantId, ?int $organizationUnitId, string $search = '', int $limit = 100): array
    {
        return [
            'payment_methods' => $this->paymentMethodOptions($tenantId, $organizationUnitId, $search, $limit),
            'payment_accounts' => $this->paymentAccountOptions($tenantId, $organizationUnitId, $search, $limit),
        ];
    }

    /**
     * Prepare a supplier payment DTO. Payment owns persistence and invoice settlement.
     *
     * @param  list<PaymentLineData>  $lines
     * @param  list<PaymentAllocationData>  $allocations
     */
    public function prepareSupplierPayment(
        int $tenantId,
        string $paymentDate,
        string $amount,
        ?int $organizationUnitId = null,
        ?string $supplierType = null,
        ?int $supplierId = null,
        ?int $currencyId = null,
        string $exchangeRate = '1.000000',
        ?string $referenceNumber = null,
        array $lines = [],
        array $allocations = [],
        PaymentStatus $status = PaymentStatus::Draft,
        ?int $createdBy = null,
        ?string $notes = null,
        ?int $bankAccountId = null,
        ?array $metadata = null,
    ): CreatePaymentData {
        return new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::SupplierPayment,
            direction: PaymentDirection::Outbound,
            paymentDate: $paymentDate,
            organizationUnitId: $organizationUnitId,
            partyType: $supplierType,
            partyId: $supplierId,
            currencyId: $currencyId,
            exchangeRate: $exchangeRate,
            referenceNumber: $referenceNumber,
            status: $status,
            notes: $notes,
            createdBy: $createdBy,
            lines: $lines === [] ? [new PaymentLineData($amount, referenceNumber: $referenceNumber)] : $lines,
            allocations: $allocations,
            bankAccountId: $bankAccountId,
            metadata: $metadata,
        );
    }

    /**
     * @param  list<PaymentLineData>  $lines
     * @param  list<PaymentAllocationData>  $allocations
     */
    public function createSupplierPayment(
        int $tenantId,
        string $paymentDate,
        string $amount,
        ?int $organizationUnitId = null,
        ?string $supplierType = null,
        ?int $supplierId = null,
        ?int $currencyId = null,
        string $exchangeRate = '1.000000',
        ?string $referenceNumber = null,
        array $lines = [],
        array $allocations = [],
        ?int $createdBy = null,
        ?string $notes = null,
    ): Payment {
        $this->assertLineTotalMatchesAmount($amount, $lines);

        return DB::transaction(function () use (
            $tenantId,
            $paymentDate,
            $amount,
            $organizationUnitId,
            $supplierType,
            $supplierId,
            $currencyId,
            $exchangeRate,
            $referenceNumber,
            $lines,
            $allocations,
            $createdBy,
            $notes,
        ): Payment {
            $currencyId = $this->assertAllocationsBelongToSupplier(
                $tenantId,
                $organizationUnitId,
                $supplierType ?? 'supplier',
                $supplierId,
                $currencyId,
                $allocations,
            );

            $data = $this->prepareSupplierPayment(
                tenantId: $tenantId,
                paymentDate: $paymentDate,
                amount: $amount,
                organizationUnitId: $organizationUnitId,
                supplierType: $supplierType ?? 'supplier',
                supplierId: $supplierId,
                currencyId: $currencyId,
                exchangeRate: $exchangeRate,
                referenceNumber: $referenceNumber,
                lines: $lines,
                allocations: $allocations,
                status: PaymentStatus::Draft,
                createdBy: $createdBy,
                notes: $notes,
                metadata: ['source_module' => 'purchase'],
            );

            return $this->payments->create($data);
        });
    }

    /**
     * @param  list<PaymentLineData>  $lines
     */
    private function assertLineTotalMatchesAmount(string $amount, array $lines): void
    {
        if ($lines === []) {
            return;
        }

        $total = '0.000000';
        foreach ($lines as $line) {
            if (! $line instanceof PaymentLineData) {
                throw new InvalidArgumentException('Supplier payment lines are invalid.');
            }
            $total = $this->math->add($total, $line->amount);
        }

        if ($this->math->compare($this->math->normalize($amount), $total) !== 0) {
            throw new InvalidArgumentException('Payment method line total must equal payment total.');
        }
    }

    /**
     * @param  list<PaymentAllocationData>  $allocations
     */
    private function assertAllocationsBelongToSupplier(
        int $tenantId,
        ?int $organizationUnitId,
        string $supplierType,
        ?int $supplierId,
        ?int $currencyId,
        array $allocations,
    ): ?int {
        if ($supplierId === null) {
            throw new InvalidArgumentException('Supplier payment requires a supplier.');
        }

        if ($allocations === []) {
            return $currencyId;
        }

        $invoiceIds = array_map(static fn (PaymentAllocationData $allocation): int => $allocation->invoiceId, $allocations);
        $invoices = Invoice::query()
            ->whereIn('id', $invoiceIds)
            ->lockForUpdate()
            ->get()
            ->keyBy(fn (Invoice $invoice): int => (int) $invoice->getKey());

        foreach ($allocations as $allocation) {
            if (! $allocation instanceof PaymentAllocationData) {
                throw new InvalidArgumentException('Supplier payment allocations are invalid.');
            }

            $invoice = $invoices->get($allocation->invoiceId);
            if (! $invoice instanceof Invoice) {
                throw new InvalidArgumentException('Selected supplier invoice was not found.');
            }
            if ((int) $invoice->tenant_id !== $tenantId || $invoice->organization_unit_id !== $organizationUnitId) {
                throw new InvalidArgumentException('Selected supplier invoice is outside the payment scope.');
            }
            if ($invoice->invoice_type !== InvoiceType::Purchase || $invoice->direction !== InvoiceDirection::Inbound) {
                throw new InvalidArgumentException('Selected invoice is not a supplier invoice.');
            }
            if ($invoice->party_type !== $supplierType || (int) $invoice->party_id !== $supplierId) {
                throw new InvalidArgumentException('Selected supplier invoice belongs to a different supplier.');
            }

            $invoiceCurrencyId = $invoice->currency_id === null ? null : (int) $invoice->currency_id;
            if ($currencyId === null) {
                $currencyId = $invoiceCurrencyId;
            } elseif ($invoiceCurrencyId !== null && $invoiceCurrencyId !== $currencyId) {
                throw new InvalidArgumentException('Selected supplier invoice currency does not match the payment currency.');
            }
        }

        return $currencyId;
    }

    public function assertPaymentSourceAccount(int $tenantId, ?int $organizationUnitId, int $accountId): FinanceAccount
    {
        $account = FinanceAccount::query()->find($accountId);
        if (! $account instanceof FinanceAccount
            || (int) $account->tenant_id !== $tenantId
            || $account->organization_unit_id !== $organizationUnitId
            || ! (bool) $account->is_active
            || ! (bool) $account->is_posting_account
            || (! (bool) $account->is_cash_account && ! (bool) $account->is_bank_account)
        ) {
            throw new InvalidArgumentException('The selected payment source account is not available.');
        }

        return $account;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function paymentMethodOptions(int $tenantId, ?int $organizationUnitId, string $search, int $limit): array
    {
        return PaymentMethod::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('direction_allowed', 'outbound')->orWhere('direction_allowed', 'both');
            })
            ->where(function ($query) use ($tenantId): void {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->when($organizationUnitId === null, fn ($query) => $query->whereNull('organization_unit_id'), fn ($query) => $query->where(function ($scope) use ($organizationUnitId): void {
                $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId);
            }))
            ->when($search !== '', fn ($query) => $query->where(function ($scope) use ($search): void {
                $scope->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%');
            }))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(max(1, min(100, $limit)))
            ->get(['id', 'code', 'name', 'method_type', 'requires_reference', 'requires_bank_account'])
            ->map(fn (PaymentMethod $method): array => [
                'id' => (int) $method->getKey(),
                'code' => $method->code,
                'name' => $method->name,
                'method_type' => $this->enumValue($method->method_type),
                'requires_reference' => (bool) $method->requires_reference,
                'requires_bank_account' => (bool) $method->requires_bank_account,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function paymentAccountOptions(int $tenantId, ?int $organizationUnitId, string $search, int $limit): array
    {
        return FinanceAccount::query()
            ->where('tenant_id', $tenantId)
            ->when($organizationUnitId === null, fn ($query) => $query->whereNull('organization_unit_id'), fn ($query) => $query->where('organization_unit_id', $organizationUnitId))
            ->where('is_active', true)
            ->where('is_posting_account', true)
            ->where(function ($query): void {
                $query->where('is_cash_account', true)->orWhere('is_bank_account', true);
            })
            ->when($search !== '', fn ($query) => $query->where(function ($scope) use ($search): void {
                $scope->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%');
            }))
            ->orderBy('code')
            ->limit(max(1, min(100, $limit)))
            ->get(['id', 'code', 'name', 'is_cash_account', 'is_bank_account'])
            ->map(fn (FinanceAccount $account): array => [
                'id' => (int) $account->getKey(),
                'code' => $account->code,
                'name' => $account->name,
                'is_cash_account' => (bool) $account->is_cash_account,
                'is_bank_account' => (bool) $account->is_bank_account,
            ])
            ->all();
    }

    private function enumValue(mixed $value): ?string
    {
        return $value instanceof \BackedEnum ? (string) $value->value : ($value === null ? null : (string) $value);
    }
}
