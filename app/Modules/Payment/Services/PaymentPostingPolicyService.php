<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use InvalidArgumentException;
use Modules\Payment\Constants\PaymentPostingMetadata;
use Modules\Payment\DTOs\PaymentPostingPolicyData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentPostingProfile;
use Modules\Payment\Enums\PaymentPostingRole;
use Modules\Payment\Enums\PaymentSourceType;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;

final class PaymentPostingPolicyService
{
    public function resolve(Payment $payment): PaymentPostingPolicyData
    {
        $type = $payment->payment_type instanceof PaymentType
            ? $payment->payment_type
            : PaymentType::from((string) $payment->payment_type);

        return match ($type) {
            PaymentType::CustomerReceipt,
            PaymentType::ServiceReceipt,
            PaymentType::RentalReceipt => $this->customerSettlement(),
            PaymentType::SupplierPayment => $this->supplierSettlement(),
            PaymentType::Advance => $this->advance($payment),
            PaymentType::Refund => $this->refund($payment),
            PaymentType::Manual => $this->manual($payment),
        };
    }

    private function customerSettlement(): PaymentPostingPolicyData
    {
        return new PaymentPostingPolicyData(
            PaymentPostingProfile::CustomerSettlement->value,
            PaymentPostingRole::Receivable,
            PaymentPostingRole::CustomerAdvance,
        );
    }

    private function supplierSettlement(): PaymentPostingPolicyData
    {
        return new PaymentPostingPolicyData(
            PaymentPostingProfile::SupplierSettlement->value,
            PaymentPostingRole::Payable,
            PaymentPostingRole::SupplierAdvance,
        );
    }

    private function advance(Payment $payment): PaymentPostingPolicyData
    {
        $direction = $this->direction($payment);
        $partyType = trim((string) $payment->party_type);
        $sourceType = trim((string) $payment->source_type);

        if ($sourceType === PaymentSourceType::RentalDepositRequirement->value) {
            if ($direction !== PaymentDirection::Inbound || $partyType !== 'customer') {
                throw new InvalidArgumentException('Rental deposit payments must be inbound customer advances.');
            }

            return new PaymentPostingPolicyData(
                PaymentPostingProfile::RentalDeposit->value,
                PaymentPostingRole::CustomerDeposit,
                PaymentPostingRole::CustomerDeposit,
            );
        }

        if ($direction === PaymentDirection::Inbound && $partyType === 'customer') {
            return new PaymentPostingPolicyData(
                PaymentPostingProfile::CustomerAdvance->value,
                PaymentPostingRole::Receivable,
                PaymentPostingRole::CustomerAdvance,
            );
        }

        if ($direction === PaymentDirection::Outbound && $partyType === 'supplier') {
            return new PaymentPostingPolicyData(
                PaymentPostingProfile::SupplierAdvance->value,
                PaymentPostingRole::Payable,
                PaymentPostingRole::SupplierAdvance,
            );
        }

        throw new InvalidArgumentException('Advance payment accounting requires an inbound customer or outbound supplier party.');
    }

    private function refund(Payment $payment): PaymentPostingPolicyData
    {
        if ($payment->original_payment_id === null) {
            throw new InvalidArgumentException('Refund payment accounting requires the original payment.');
        }

        $original = Payment::query()->findOrFail((int) $payment->original_payment_id);
        $originalType = $original->payment_type instanceof PaymentType
            ? $original->payment_type
            : PaymentType::from((string) $original->payment_type);
        if ($originalType === PaymentType::Refund) {
            throw new InvalidArgumentException('A refund payment cannot be refunded again.');
        }
        if ((int) $original->tenant_id !== (int) $payment->tenant_id
            || $original->organization_unit_id !== $payment->organization_unit_id
            || $original->party_type !== $payment->party_type
            || $original->party_id !== $payment->party_id
        ) {
            throw new InvalidArgumentException('Refund payment scope and party must match the original payment.');
        }
        if ($this->direction($payment) === $this->direction($original)) {
            throw new InvalidArgumentException('Refund payment direction must reverse the original payment direction.');
        }

        $originalPolicy = $this->resolve($original);

        return new PaymentPostingPolicyData(
            $originalPolicy->postingProfileCode,
            $originalPolicy->unappliedRole,
            $originalPolicy->unappliedRole,
        );
    }

    private function manual(Payment $payment): PaymentPostingPolicyData
    {
        $metadata = is_array($payment->metadata) ? $payment->metadata : [];
        $profileCode = trim((string) ($metadata[PaymentPostingMetadata::PROFILE_CODE] ?? ''));
        $role = PaymentPostingRole::tryFrom(trim((string) ($metadata[PaymentPostingMetadata::COUNTERPARTY_ROLE] ?? '')));
        if ($profileCode === '' || ! $role instanceof PaymentPostingRole) {
            throw new InvalidArgumentException(
                'Manual payment posting requires posting_profile_code and a supported counterparty_profile_key in metadata.',
            );
        }

        return new PaymentPostingPolicyData($profileCode, $role, $role);
    }

    private function direction(Payment $payment): PaymentDirection
    {
        return $payment->direction instanceof PaymentDirection
            ? $payment->direction
            : PaymentDirection::from((string) $payment->direction);
    }
}
