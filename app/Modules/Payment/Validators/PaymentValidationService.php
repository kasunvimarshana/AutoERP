<?php

declare(strict_types=1);

namespace Modules\Payment\Validators;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\DTOs\BalanceResultData;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Services\PaymentMethodService;

final class PaymentValidationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PaymentMethodService $paymentMethods,
    ) {}

    public function validateForCreation(CreatePaymentData $data): void
    {
        if ($data->tenantId < 1) {
            throw new InvalidArgumentException('Payment tenant is required.');
        }

        if ($data->organizationUnitId !== null && $data->organizationUnitId < 1) {
            throw new InvalidArgumentException('Payment organization unit must be a positive id.');
        }

        if (trim($data->paymentDate) === '') {
            throw new InvalidArgumentException('Payment date is required.');
        }

        if ($this->math->isNegative($data->exchangeRate) || $this->math->isZero($data->exchangeRate)) {
            throw new InvalidArgumentException('Payment exchange rate must be greater than zero.');
        }

        if ($data->lines === []) {
            throw new InvalidArgumentException('Payment requires at least one payment line.');
        }

        foreach ($data->lines as $line) {
            if (! $line instanceof PaymentLineData) {
                throw new InvalidArgumentException('Payment lines must be PaymentLineData instances.');
            }

            $this->assertPositive($line->amount, 'Payment line amount');
            $this->assertNonNegative($line->clearedAmount, 'Payment line cleared amount');

            $method = $this->resolvePaymentMethod($line->paymentMethodId);
            $this->validatePaymentMethod(
                $method,
                $data->tenantId,
                $data->organizationUnitId,
                $data->direction,
                $line->referenceNumber ?? $data->referenceNumber,
                $this->lineBankAccountId($line, $data->bankAccountId),
            );
        }

        foreach ($data->allocations as $allocation) {
            if (! $allocation instanceof PaymentAllocationData) {
                throw new InvalidArgumentException('Payment allocations must be PaymentAllocationData instances.');
            }
            $this->assertPositive($allocation->allocatedAmount, 'Payment allocation amount');
        }
    }

    public function validateInvoiceAllocation(Payment $payment, BalanceResultData $invoiceBalance, PaymentAllocationData $allocation): void
    {
        if ((int) $payment->tenant_id !== $invoiceBalance->tenantId) {
            throw new InvalidArgumentException('Payment invoice tenant must match payment tenant.');
        }

        if ($payment->organization_unit_id !== $invoiceBalance->organizationUnitId) {
            throw new InvalidArgumentException('Payment invoice organization unit must match payment organization unit.');
        }

        $this->assertPositive($allocation->allocatedAmount, 'Payment allocation amount');
    }

    public function validatePaymentMethod(
        ?PaymentMethod $method,
        int $tenantId,
        ?int $organizationUnitId,
        PaymentDirection|string $direction,
        ?string $referenceNumber,
        ?int $bankAccountId,
    ): void {
        $this->paymentMethods->assertUsable(
            $method,
            $direction,
            $referenceNumber,
            $tenantId,
            $organizationUnitId,
            $bankAccountId,
        );
    }

    public function assertPositive(string $amount, string $label): void
    {
        if ($this->math->isNegative($amount) || $this->math->isZero($amount)) {
            throw new InvalidArgumentException($label.' must be greater than zero.');
        }
    }

    public function assertNonNegative(string $amount, string $label): void
    {
        if ($this->math->isNegative($amount)) {
            throw new InvalidArgumentException($label.' cannot be negative.');
        }
    }

    private function resolvePaymentMethod(?int $paymentMethodId): ?PaymentMethod
    {
        if ($paymentMethodId === null) {
            return null;
        }

        $method = PaymentMethod::query()->find($paymentMethodId);
        if (! $method instanceof PaymentMethod) {
            throw new InvalidArgumentException('Payment method was not found.');
        }

        return $method;
    }

    private function lineBankAccountId(PaymentLineData $line, ?int $headerBankAccountId): ?int
    {
        if ($line->internalBankAccountId !== null) {
            return $line->internalBankAccountId;
        }

        $metadataValue = is_array($line->metadata) ? ($line->metadata['bank_account_id'] ?? null) : null;

        return is_numeric($metadataValue) ? (int) $metadataValue : $headerBankAccountId;
    }
}
