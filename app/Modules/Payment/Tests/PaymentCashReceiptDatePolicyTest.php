<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Payment\Constants\PaymentPermission;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentLine;
use Modules\Payment\Services\PaymentCashReceiptDatePolicy;
use Tests\TestCase;

final class PaymentCashReceiptDatePolicyTest extends TestCase
{
    public function test_current_day_cash_receipt_is_allowed_without_special_permission(): void
    {
        $this->expectNotToPerformAssertions();
        $this->policy(false)->assertCanPost($this->receipt(CarbonImmutable::now('Asia/Colombo')->toDateString()), 5);
    }

    public function test_older_cash_receipt_requires_permission_and_reason(): void
    {
        $yesterday = CarbonImmutable::now('Asia/Colombo')->subDay()->toDateString();
        $this->expectException(AuthorizationException::class);

        $this->policy(false)->assertCanPost($this->receipt($yesterday, 'Correction'), 5);
    }

    public function test_authorized_older_cash_receipt_requires_a_reason(): void
    {
        $yesterday = CarbonImmutable::now('Asia/Colombo')->subDay()->toDateString();
        $this->expectException(ValidationException::class);

        $this->policy(true)->assertCanPost($this->receipt($yesterday), 5);
    }

    public function test_authorized_older_cash_receipt_with_reason_is_allowed(): void
    {
        $yesterday = CarbonImmutable::now('Asia/Colombo')->subDay()->toDateString();
        $this->policy(true)->assertCanPost($this->receipt($yesterday, 'Correction approved'), 5);
    }

    private function policy(bool $canBackdate): PaymentCashReceiptDatePolicy
    {
        $configuration = $this->createMock(ConfigurationResolverInterface::class);
        $configuration->method('value')->willReturn('Asia/Colombo');
        $permissions = $this->createMock(PermissionCheckerInterface::class);
        $permissions->method('allows')->with(5, 1, PaymentPermission::PAYMENTS_BACKDATE_CASH)->willReturn($canBackdate);

        return new PaymentCashReceiptDatePolicy($configuration, $permissions);
    }

    private function receipt(string $date, ?string $notes = null): Payment
    {
        $payment = (new Payment)->forceFill([
            'tenant_id' => 1,
            'direction' => 'inbound',
            'payment_type' => 'service_receipt',
            'payment_date' => $date,
            'notes' => 'Received from service job',
            'metadata' => $notes === null ? [] : ['backdate_reason' => $notes],
        ]);
        $payment->setRelation('lines', collect([(new PaymentLine)->forceFill(['payment_method_type_snapshot' => 'cash'])]));

        return $payment;
    }
}
