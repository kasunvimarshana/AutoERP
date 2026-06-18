<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Models\FinanceAccount;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
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
use Modules\Payment\Validators\PaymentValidationService;
use Modules\Purchase\DTOs\PurchasePaymentPreviewData;

final class PurchasePaymentIntegrationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PaymentCreationService $payments,
        private readonly PaymentValidationService $paymentValidator,
        private readonly InvoiceBalanceProviderInterface $invoiceBalances,
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
     * Build canonical supplier payment creation data. Payment owns persistence and invoice settlement.
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
        $supplierType = $supplierType ?? 'supplier';

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
            $allocationResolution = $this->resolveSupplierAllocations(
                $tenantId,
                $organizationUnitId,
                $supplierType,
                $supplierId,
                $currencyId,
                $allocations,
                true,
            );
            $currencyId = $allocationResolution['currency_id'];

            $data = $this->prepareSupplierPayment(
                tenantId: $tenantId,
                paymentDate: $paymentDate,
                amount: $amount,
                organizationUnitId: $organizationUnitId,
                supplierType: $supplierType,
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
     * Validate and calculate a supplier payment preview without creating payment, allocations,
     * invoice settlements, audit rows, ledger rows, or balance changes.
     *
     * @param  list<PaymentLineData>  $lines
     * @param  list<PaymentAllocationData>  $allocations
     */
    public function previewSupplierPayment(
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
    ): PurchasePaymentPreviewData {
        $this->assertLineTotalMatchesAmount($amount, $lines);

        $supplierType = $supplierType ?? 'supplier';
        $allocationResolution = $this->resolveSupplierAllocations(
            $tenantId,
            $organizationUnitId,
            $supplierType,
            $supplierId,
            $currencyId,
            $allocations,
            false,
        );
        $currencyId = $allocationResolution['currency_id'];

        $data = $this->prepareSupplierPayment(
            tenantId: $tenantId,
            paymentDate: $paymentDate,
            amount: $amount,
            organizationUnitId: $organizationUnitId,
            supplierType: $supplierType,
            supplierId: $supplierId,
            currencyId: $currencyId,
            exchangeRate: $exchangeRate,
            referenceNumber: $referenceNumber,
            lines: $lines,
            allocations: $allocations,
            status: PaymentStatus::Draft,
            createdBy: $createdBy,
            notes: $notes,
            metadata: ['source_module' => 'purchase', 'preview' => true],
        );
        $this->paymentValidator->validateForCreation($data);

        $lineTotal = $this->sumLineAmounts($data->lines);
        $allocationTotal = $this->sumAllocationAmounts($data->allocations);
        if ($this->math->compare($allocationTotal, $lineTotal) > 0) {
            throw new InvalidArgumentException('Payment allocation total cannot exceed payment total.');
        }

        return new PurchasePaymentPreviewData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            paymentDate: $paymentDate,
            amount: $this->math->normalize($amount),
            lineTotal: $lineTotal,
            allocationTotal: $allocationTotal,
            unappliedAmount: $this->math->sub($lineTotal, $allocationTotal),
            supplierType: $supplierType,
            supplierId: (int) $supplierId,
            currencyId: $currencyId,
            exchangeRate: $this->math->normalize($exchangeRate),
            referenceNumber: $referenceNumber,
            lines: $this->paymentLinePreviewRows($data->lines),
            allocations: $allocationResolution['allocations'],
        );
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
     * @return array{currency_id: int|null, allocations: list<array<string, mixed>>}
     */
    private function resolveSupplierAllocations(
        int $tenantId,
        ?int $organizationUnitId,
        string $supplierType,
        ?int $supplierId,
        ?int $currencyId,
        array $allocations,
        bool $lockSources,
    ): array {
        if ($supplierId === null) {
            throw new InvalidArgumentException('Supplier payment requires a supplier.');
        }

        if ($allocations === []) {
            return ['currency_id' => $currencyId, 'allocations' => []];
        }

        $invoiceIds = array_map(static fn (PaymentAllocationData $allocation): int => $allocation->invoiceId, $allocations);
        $query = Invoice::query()->whereIn('id', $invoiceIds);
        if ($lockSources) {
            $query->lockForUpdate();
        }

        $invoices = $query->get()
            ->keyBy(fn (Invoice $invoice): int => (int) $invoice->getKey());

        $seenInvoices = [];
        $resolved = [];
        foreach ($allocations as $allocation) {
            if (! $allocation instanceof PaymentAllocationData) {
                throw new InvalidArgumentException('Supplier payment allocations are invalid.');
            }
            if (isset($seenInvoices[$allocation->invoiceId])) {
                throw new InvalidArgumentException('Payment can only allocate once to the same supplier invoice.');
            }
            $seenInvoices[$allocation->invoiceId] = true;

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

            $balance = $this->invoiceBalances->validatePayableState($allocation->invoiceId);
            if ($balance->tenantId !== $tenantId || $balance->organizationUnitId !== $organizationUnitId) {
                throw new InvalidArgumentException('Selected supplier invoice balance is outside the payment scope.');
            }
            if (! $allocation->allowOverpayment
                && $this->math->compare($allocation->allocatedAmount, $balance->remainingAmount) > 0
            ) {
                throw new InvalidArgumentException('Payment allocation cannot exceed invoice remaining balance.');
            }

            $resolved[] = [
                'invoice_id' => $allocation->invoiceId,
                'invoice_number' => $invoice->invoice_number,
                'invoice_total' => $this->math->normalize($balance->totalAmount),
                'invoice_balance_before' => $this->math->normalize($balance->remainingAmount),
                'allocated_amount' => $this->math->normalize($allocation->allocatedAmount),
                'invoice_balance_after' => $this->math->sub($balance->remainingAmount, $allocation->allocatedAmount),
                'allocation_date' => $allocation->allocationDate,
                'allocation_method' => $allocation->allocationMethod,
            ];
        }

        return ['currency_id' => $currencyId, 'allocations' => $resolved];
    }

    /**
     * @param  list<PaymentLineData>  $lines
     */
    private function sumLineAmounts(array $lines): string
    {
        $total = '0.000000';
        foreach ($lines as $line) {
            $total = $this->math->add($total, $line->amount);
        }

        return $total;
    }

    /**
     * @param  list<PaymentAllocationData>  $allocations
     */
    private function sumAllocationAmounts(array $allocations): string
    {
        $total = '0.000000';
        foreach ($allocations as $allocation) {
            $total = $this->math->add($total, $allocation->allocatedAmount);
        }

        return $total;
    }

    /**
     * @param  list<PaymentLineData>  $lines
     * @return list<array<string, mixed>>
     */
    private function paymentLinePreviewRows(array $lines): array
    {
        return array_map(fn (PaymentLineData $line): array => [
            'amount' => $this->math->normalize($line->amount),
            'payment_method_id' => $line->paymentMethodId,
            'reference_number' => $line->referenceNumber,
            'source_account_id' => $line->metadata['source_account_id'] ?? null,
            'internal_bank_account_id' => $line->internalBankAccountId,
            'instrument_direction' => $line->instrumentDirection,
            'instrument_number' => $line->instrumentNumber,
            'instrument_date' => $line->instrumentDate,
            'notes' => $line->notes,
        ], $lines);
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
