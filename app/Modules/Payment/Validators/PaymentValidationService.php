<?php

declare(strict_types=1);

namespace Modules\Payment\Validators;

use InvalidArgumentException;
use Modules\Configuration\Models\CurrencyModel;
use Modules\Core\Services\DecimalMath;
use Modules\Customer\Models\Customer;
use Modules\Finance\Models\FinanceAccount;
use Modules\Invoice\DTOs\BalanceResultData;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Services\PaymentMethodService;
use Modules\Supplier\Models\Supplier;

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

        $this->validateTypeDirectionParty($data);
        $this->validateCurrency($data->currencyId, $data->exchangeRate);
        $this->validateBankAccount($data->bankAccountId, $data->tenantId, $data->organizationUnitId, requireBank: false);

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
            $this->validateBankAccount($line->internalBankAccountId, $data->tenantId, $data->organizationUnitId, requireBank: true);
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

        if ($payment->party_type !== $invoiceBalance->partyType || (int) $payment->party_id !== (int) $invoiceBalance->partyId) {
            throw new InvalidArgumentException('Payment invoice party must match payment party.');
        }
        if ($payment->currency_id !== null && $invoiceBalance->currencyId !== null && (int) $payment->currency_id !== $invoiceBalance->currencyId) {
            throw new InvalidArgumentException('Cross-currency payment allocation is not supported.');
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

    private function validateTypeDirectionParty(CreatePaymentData $data): void
    {
        $expectedDirection = match ($data->paymentType) {
            PaymentType::CustomerReceipt, PaymentType::ServiceReceipt, PaymentType::RentalReceipt => PaymentDirection::Inbound,
            PaymentType::SupplierPayment => PaymentDirection::Outbound,
            PaymentType::Refund, PaymentType::Advance, PaymentType::Manual => $data->direction,
        };

        if ($data->direction !== $expectedDirection) {
            throw new InvalidArgumentException('Payment type is not valid for the selected direction.');
        }

        $expectedParty = match ($data->paymentType) {
            PaymentType::CustomerReceipt, PaymentType::ServiceReceipt => 'customer',
            PaymentType::SupplierPayment => 'supplier',
            default => $data->partyType,
        };

        if ($expectedParty !== null && $data->partyType !== $expectedParty) {
            throw new InvalidArgumentException('Payment party type is not valid for the selected payment type.');
        }

        if ($data->partyType !== null || $data->partyId !== null) {
            if ($data->partyType === null || $data->partyId === null) {
                throw new InvalidArgumentException('Payment party type and party id must be provided together.');
            }
            $this->validateParty($data->partyType, $data->partyId, $data->tenantId, $data->organizationUnitId);
        }
    }

    private function validateParty(string $partyType, int $partyId, int $tenantId, ?int $organizationUnitId): void
    {
        $model = match ($partyType) {
            'customer' => Customer::query()->find($partyId),
            'supplier' => Supplier::query()->find($partyId),
            default => throw new InvalidArgumentException('Unsupported payment party type.'),
        };

        if ($model === null || (int) $model->tenant_id !== $tenantId) {
            throw new InvalidArgumentException('Payment party was not found in the active tenant.');
        }

        if ($model->organization_unit_id !== null && (int) $model->organization_unit_id !== (int) $organizationUnitId) {
            throw new InvalidArgumentException('Payment party organization unit must match payment organization unit.');
        }
    }

    private function validateCurrency(?int $currencyId, string $exchangeRate): void
    {
        if ($this->math->isNegative($exchangeRate) || $this->math->isZero($exchangeRate)) {
            throw new InvalidArgumentException('Payment exchange rate must be greater than zero.');
        }

        if ($currencyId === null) {
            return;
        }

        $currency = CurrencyModel::query()->find($currencyId);
        if (! $currency instanceof CurrencyModel || ! (bool) $currency->is_active) {
            throw new InvalidArgumentException('Payment currency must be active.');
        }
    }

    private function validateBankAccount(?int $accountId, int $tenantId, ?int $organizationUnitId, bool $requireBank): void
    {
        if ($accountId === null) {
            return;
        }

        $account = FinanceAccount::query()->find($accountId);
        if (! $account instanceof FinanceAccount
            || (int) $account->tenant_id !== $tenantId
            || ($account->organization_unit_id !== null && (int) $account->organization_unit_id !== (int) $organizationUnitId)
            || ! (bool) $account->is_active
            || ! (bool) $account->is_posting_account
            || ($requireBank && ! (bool) $account->is_bank_account)
        ) {
            throw new InvalidArgumentException('Payment bank account is not available for this tenant and organization.');
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
