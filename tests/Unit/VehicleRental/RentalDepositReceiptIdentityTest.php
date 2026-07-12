<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use InvalidArgumentException;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalDepositRequirement;
use Modules\VehicleRental\Services\RentalDepositService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class RentalDepositReceiptIdentityTest extends TestCase
{
    public static function invalidReceiptIdentities(): array
    {
        return [
            'non-customer party type' => [['party_type' => 'supplier']],
            'non-advance payment type' => [['payment_type' => PaymentType::CustomerReceipt->value]],
            'outbound payment direction' => [['direction' => PaymentDirection::Outbound->value]],
        ];
    }

    #[DataProvider('invalidReceiptIdentities')]
    public function test_deposit_receipt_identity_rejects_wrong_payment_semantics(array $overrides): void
    {
        [$requirement, $payment] = $this->identityFixture($overrides);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Selected payment is not the expected rental deposit receipt.');

        $this->identityMethod()->invoke(
            (new ReflectionClass(RentalDepositService::class))->newInstanceWithoutConstructor(),
            $requirement,
            $payment,
            6,
        );
    }

    public function test_deposit_receipt_identity_accepts_the_exact_advance_receipt_contract(): void
    {
        [$requirement, $payment] = $this->identityFixture();

        $result = $this->identityMethod()->invoke(
            (new ReflectionClass(RentalDepositService::class))->newInstanceWithoutConstructor(),
            $requirement,
            $payment,
            6,
        );

        self::assertNull($result);
    }

    private function identityFixture(array $overrides = []): array
    {
        $agreement = new RentalAgreement();
        $agreement->forceFill(['customer_id' => 71]);

        $requirement = new RentalDepositRequirement();
        $requirement->forceFill([
            'id' => 41,
            'tenant_id' => 3,
            'organization_unit_id' => 4,
            'currency_id' => 5,
        ]);
        $requirement->setRelation('agreement', $agreement);

        $payment = new Payment();
        $payment->forceFill(array_merge([
            'id' => 91,
            'row_version' => 6,
            'tenant_id' => 3,
            'organization_unit_id' => 4,
            'party_type' => 'customer',
            'party_id' => 71,
            'currency_id' => 5,
            'payment_type' => PaymentType::Advance->value,
            'direction' => PaymentDirection::Inbound->value,
            'source_type' => 'rental_deposit_requirement',
            'source_id' => 41,
        ], $overrides));

        return [$requirement, $payment];
    }

    private function identityMethod(): ReflectionMethod
    {
        $method = new ReflectionMethod(RentalDepositService::class, 'assertReceiptIdentity');
        $method->setAccessible(true);

        return $method;
    }
}
