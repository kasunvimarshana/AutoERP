<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use InvalidArgumentException;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentPostingProfile;
use Modules\Payment\Enums\PaymentPostingRole;
use Modules\Payment\Enums\PaymentSourceType;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentPostingPolicyService;
use Modules\Payment\Services\PaymentRefundPolicyService;
use PHPUnit\Framework\TestCase;

final class PaymentPostingPolicyTest extends TestCase
{
    public function test_customer_receipt_splits_settlement_and_unapplied_advance_roles(): void
    {
        $policy = $this->policy()->resolve($this->payment(
            PaymentType::CustomerReceipt,
            PaymentDirection::Inbound,
            'customer',
        ));

        self::assertSame(PaymentPostingProfile::CustomerSettlement->value, $policy->postingProfileCode);
        self::assertSame(PaymentPostingRole::Receivable, $policy->allocatedRole);
        self::assertSame(PaymentPostingRole::CustomerAdvance, $policy->unappliedRole);
    }

    public function test_rental_deposit_uses_receivable_for_invoice_application_and_customer_deposit_for_unapplied_balance(): void
    {
        $policy = $this->policy()->resolve($this->payment(
            PaymentType::Advance,
            PaymentDirection::Inbound,
            'customer',
            PaymentSourceType::RentalDepositRequirement->value,
        ));

        self::assertSame(PaymentPostingProfile::RentalDeposit->value, $policy->postingProfileCode);
        self::assertSame(PaymentPostingRole::Receivable, $policy->allocatedRole);
        self::assertSame(PaymentPostingRole::CustomerDeposit, $policy->unappliedRole);
    }

    public function test_supplier_advance_uses_supplier_advance_asset(): void
    {
        $policy = $this->policy()->resolve($this->payment(
            PaymentType::Advance,
            PaymentDirection::Outbound,
            'supplier',
        ));

        self::assertSame(PaymentPostingProfile::SupplierAdvance->value, $policy->postingProfileCode);
        self::assertSame(PaymentPostingRole::Payable, $policy->allocatedRole);
        self::assertSame(PaymentPostingRole::SupplierAdvance, $policy->unappliedRole);
    }

    public function test_advance_without_supported_party_semantics_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Advance payment accounting requires an inbound customer or outbound supplier party.');

        $this->policy()->resolve($this->payment(
            PaymentType::Advance,
            PaymentDirection::Inbound,
            null,
        ));
    }

    private function policy(): PaymentPostingPolicyService
    {
        return new PaymentPostingPolicyService(new PaymentRefundPolicyService());
    }

    private function payment(
        PaymentType $type,
        PaymentDirection $direction,
        ?string $partyType,
        ?string $sourceType = null,
    ): Payment {
        $payment = new Payment();
        $payment->forceFill([
            'tenant_id' => 1,
            'organization_unit_id' => null,
            'payment_type' => $type->value,
            'direction' => $direction->value,
            'party_type' => $partyType,
            'party_id' => $partyType === null ? null : 1,
            'source_type' => $sourceType,
        ]);

        return $payment;
    }
}
