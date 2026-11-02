<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentDocumentLifecycleService;
use Modules\Payment\Services\PaymentMethodService;
use Modules\Payment\Services\PaymentPostingService;
use Modules\Payment\Validators\PaymentValidationService;
use Modules\Purchase\DTOs\PurchasePaymentPreviewData;

final class PurchasePaymentIntegrationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PaymentCreationService $payments,
        private readonly PaymentDocumentLifecycleService $lifecycle,
        private readonly PaymentPostingService $posting,
        private readonly PaymentValidationService $paymentValidator,
        private readonly PaymentMethodService $paymentMethods,
        private readonly InvoiceBalanceProviderInterface $invoiceBalances,
    ) {}

    public function context(int $tenantId, ?int $organizationUnitId, string $search = '', int $limit = 100): array
    {
        $methods = $this->paymentMethods
            ->effectiveActiveForDirection($tenantId, $organizationUnitId, PaymentDirection::Outbound)
            ->filter(static fn ($method): bool => $search === ''
                || str_contains(strtolower((string) $method->code), strtolower($search))
                || str_contains(strtolower((string) $method->name), strtolower($search)))
            ->take(max(1, min(100, $limit)))
            ->map(static fn ($method): array => [
                'id' => (int) $method->getKey(),
                'code' => (string) $method->code,
                'name' => (string) $method->name,
                'method_type' => $method->method_type instanceof \BackedEnum ? $method->method_type->value : (string) $method->method_type,
                'requires_reference' => (bool) $method->requires_reference,
                'requires_instrument_details' => (bool) $method->requires_instrument_details,
            ])
            ->values()
            ->all();

        return ['payment_methods' => $methods];
    }

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
        ?int $createdBy = null,
        ?string $notes = null,
    ): CreatePaymentData {
        $supplierType ??= 'supplier';
        if ($supplierId === null) {
            throw new InvalidArgumentException('Supplier payment requires a supplier.');
        }
        if ($lines === []) {
            throw new InvalidArgumentException('Supplier payment requires at least one payment method line.');
        }
        $this->assertLineTotalMatchesAmount($amount, $lines);
        $resolved = $this->resolveSupplierAllocations(
            $tenantId,
            $organizationUnitId,
            $supplierType,
            $supplierId,
            $currencyId,
            $allocations,
        );

        return new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::SupplierPayment,
            direction: PaymentDirection::Outbound,
            paymentDate: $paymentDate,
            organizationUnitId: $organizationUnitId,
            partyType: $supplierType,
            partyId: $supplierId,
            sourceType: 'purchase',
            currencyId: $resolved['currency_id'],
            exchangeRate: $exchangeRate,
            referenceNumber: $referenceNumber,
            notes: $notes,
            createdBy: $createdBy,
            lines: $lines,
            allocations: $allocations,
        );
    }

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
            $data = $this->prepareSupplierPayment(
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
            );
            $payment = $this->payments->create($data);
            $payment = $this->lifecycle->submit($payment, (int) $payment->row_version, $createdBy);
            $payment = $this->lifecycle->approve($payment, (int) $payment->row_version, $createdBy);

            return $this->posting->post($payment, (int) $payment->row_version, $createdBy);
        });
    }

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
        $data = $this->prepareSupplierPayment(
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
            supplierType: $data->partyType,
            supplierId: (int) $data->partyId,
            currencyId: $data->currencyId,
            exchangeRate: $this->math->normalize($exchangeRate),
            referenceNumber: $referenceNumber,
            lines: $this->paymentLinePreviewRows($data->lines),
            allocations: $this->resolveSupplierAllocations(
                $tenantId,
                $organizationUnitId,
                (string) $data->partyType,
                (int) $data->partyId,
                $data->currencyId,
                $data->allocations,
            )['allocations'],
        );
    }

    private function resolveSupplierAllocations(
        int $tenantId,
        ?int $organizationUnitId,
        string $supplierType,
        int $supplierId,
        ?int $currencyId,
        array $allocations,
    ): array {
        $resolved = [];
        $seen = [];
        $references = $this->invoiceBalances->getInvoiceReferences(array_map(
            static fn (PaymentAllocationData $allocation): int => $allocation->invoiceId,
            $allocations,
        ));
        foreach ($allocations as $allocation) {
            if (! $allocation instanceof PaymentAllocationData) {
                throw new InvalidArgumentException('Supplier payment allocations are invalid.');
            }
            if (isset($seen[$allocation->invoiceId])) {
                throw new InvalidArgumentException('Payment can only allocate once to the same supplier invoice.');
            }
            $seen[$allocation->invoiceId] = true;
            $balance = $this->invoiceBalances->validatePayableState($allocation->invoiceId);
            if ($balance->tenantId !== $tenantId || $balance->organizationUnitId !== $organizationUnitId) {
                throw new InvalidArgumentException('Selected supplier invoice is outside the payment scope.');
            }
            if ($balance->partyType !== $supplierType || $balance->partyId !== $supplierId) {
                throw new InvalidArgumentException('Selected supplier invoice belongs to a different supplier.');
            }
            if ($currencyId === null) {
                $currencyId = $balance->currencyId;
            } elseif ($balance->currencyId !== null && $balance->currencyId !== $currencyId) {
                throw new InvalidArgumentException('Selected supplier invoice currency does not match the payment currency.');
            }
            if ($this->math->compare($allocation->allocatedAmount, $balance->remainingAmount) > 0) {
                throw new InvalidArgumentException('Payment allocation cannot exceed invoice remaining balance.');
            }
            $reference = $references[$allocation->invoiceId] ?? null;
            $resolved[] = [
                'invoice_id' => $allocation->invoiceId,
                'invoice_number' => is_array($reference) ? ($reference['invoice_number'] ?? null) : null,
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

    private function assertLineTotalMatchesAmount(string $amount, array $lines): void
    {
        if ($this->math->compare($this->math->normalize($amount), $this->sumLineAmounts($lines)) !== 0) {
            throw new InvalidArgumentException('Payment method line total must equal payment total.');
        }
    }

    private function sumLineAmounts(array $lines): string
    {
        $total = '0.000000';
        foreach ($lines as $line) {
            if (! $line instanceof PaymentLineData) {
                throw new InvalidArgumentException('Supplier payment lines are invalid.');
            }
            $total = $this->math->add($total, $line->amount);
        }

        return $total;
    }

    private function sumAllocationAmounts(array $allocations): string
    {
        $total = '0.000000';
        foreach ($allocations as $allocation) {
            $total = $this->math->add($total, $allocation->allocatedAmount);
        }

        return $total;
    }

    private function paymentLinePreviewRows(array $lines): array
    {
        return array_map(fn (PaymentLineData $line): array => [
            'amount' => $this->math->normalize($line->amount),
            'payment_method_id' => $line->paymentMethodId,
            'reference_number' => $line->referenceNumber,
            'instrument_direction' => $line->instrumentDirection,
            'external_bank_name' => $line->externalBankName,
            'external_bank_branch' => $line->externalBankBranch,
            'instrument_number' => $line->instrumentNumber,
            'instrument_date' => $line->instrumentDate,
            'notes' => $line->notes,
        ], $lines);
    }
}
