<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use InvalidArgumentException;
use Modules\Payment\Constants\PaymentPostingMetadata;
use Modules\Payment\DTOs\PaymentPostingPolicyData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentPostingProfile;
use Modules\Payment\Enums\PaymentPostingRole;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;

final class PaymentPostingPolicyService
{
    public function __construct(private readonly PaymentRefundPolicyService $refundPolicy) {}

    public function resolve(Payment $payment): PaymentPostingPolicyData
    {
        $type = $payment->payment_type instanceof PaymentType
            ? $payment->payment_type
            : PaymentType::from((string) $payment->payment_type);

        return match ($type) {
            PaymentType::CustomerReceipt,
            PaymentType::ServiceReceipt => $this->customerSettlement(),
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
            PaymentPostingRole::Receivable,
        );
    }

    private function supplierSettlement(): PaymentPostingPolicyData
    {
        return new PaymentPostingPolicyData(
            PaymentPostingProfile::SupplierSettlement->value,
            PaymentPostingRole::Payable,
            PaymentPostingRole::SupplierAdvance,
            PaymentPostingRole::Payable,
        );
    }

    private function advance(Payment $payment): PaymentPostingPolicyData
    {
        $direction = $this->direction($payment);
        $partyType = trim((string) $payment->party_type);

        if ($direction === PaymentDirection::Inbound && $partyType === 'customer') {
            return new PaymentPostingPolicyData(
                PaymentPostingProfile::CustomerAdvance->value,
                PaymentPostingRole::Receivable,
                PaymentPostingRole::CustomerAdvance,
                PaymentPostingRole::Receivable,
            );
        }

        if ($direction === PaymentDirection::Outbound && $partyType === 'supplier') {
            return new PaymentPostingPolicyData(
                PaymentPostingProfile::SupplierAdvance->value,
                PaymentPostingRole::Payable,
                PaymentPostingRole::SupplierAdvance,
                PaymentPostingRole::Payable,
            );
        }

        throw new InvalidArgumentException('Advance payment accounting requires an inbound customer or outbound supplier party.');
    }

    private function refund(Payment $payment): PaymentPostingPolicyData
    {
        $original = $this->refundPolicy->originalForPayment($payment);
        $originalPolicy = $this->resolve($original);

        return new PaymentPostingPolicyData(
            $originalPolicy->postingProfileCode,
            $originalPolicy->unappliedRole,
            $originalPolicy->unappliedRole,
            $originalPolicy->allocationTargetRole,
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

        return new PaymentPostingPolicyData($profileCode, $role, $role, $role);
    }

    private function direction(Payment $payment): PaymentDirection
    {
        return $payment->direction instanceof PaymentDirection
            ? $payment->direction
            : PaymentDirection::from((string) $payment->direction);
    }
}