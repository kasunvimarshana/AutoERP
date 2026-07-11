<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use PHPUnit\Framework\TestCase;

final class RentalDepositReversalContractTest extends TestCase
{
    public function test_deposit_invoice_movements_reverse_only_the_payment_allocation(): void
    {
        $root = dirname(__DIR__, 3);
        $paymentService = file_get_contents($root.'/app/Modules/Payment/Services/PaymentAllocationReversalService.php');
        $paymentReversalService = file_get_contents($root.'/app/Modules/Payment/Services/PaymentReversalService.php');
        $paymentAllocation = file_get_contents($root.'/app/Modules/Payment/Models/PaymentAllocation.php');
        $migration = file_get_contents($root.'/app/Modules/Payment/Database/Migrations/2026_07_11_000001_allow_reversed_payment_allocation_history.php');
        $depositService = file_get_contents($root.'/app/Modules/VehicleRental/Services/RentalDepositService.php');
        $request = file_get_contents($root.'/app/Modules/VehicleRental/Http/Requests/ReverseRentalDepositLinkRequest.php');
        $api = file_get_contents($root.'/resources/js/modules/vehicle-rental/vehicleRentalApi.ts');
        $page = file_get_contents($root.'/resources/js/modules/vehicle-rental/pages/RentalDepositPage.tsx');

        foreach ([$paymentService, $paymentReversalService, $paymentAllocation, $migration, $depositService, $request, $api, $page] as $source) {
            self::assertIsString($source);
        }

        self::assertStringContainsString('reverseForInvoice', $paymentService);
        self::assertStringContainsString('reversePaymentAllocation', $paymentService);
        self::assertStringContainsString("'status' => AllocationStatus::Reversed->value", $paymentService);
        self::assertStringContainsString("'active_identity_slot' => null", $paymentService);
        self::assertStringContainsString('Payment allocation reversal reason is required.', $paymentService);

        self::assertStringContainsString('ACTIVE_IDENTITY_SLOT', $paymentAllocation);
        self::assertStringContainsString("'active_identity_slot' => 'integer'", $paymentAllocation);
        self::assertStringContainsString('payment_allocations_payment_invoice_active_uk', $migration);
        self::assertStringContainsString("['payment_id', 'invoice_id', 'active_identity_slot']", $migration);
        self::assertGreaterThanOrEqual(2, substr_count($paymentReversalService, "'active_identity_slot' => null"));

        self::assertStringContainsString('$this->allocationReversals->reverseForInvoice(', $depositService);
        self::assertStringContainsString('RentalDepositLinkType::AppliedToInvoice', $depositService);
        self::assertStringContainsString('RentalDepositLinkType::Forfeiture', $depositService);
        self::assertStringContainsString('assertReceiptHasNoActiveDependentMovements', $depositService);
        self::assertStringNotContainsString('Reverse the linked payment allocation before reversing this deposit movement.', $depositService);

        self::assertStringContainsString("'expected_payment_version' => ['required'", $request);
        self::assertStringContainsString("'reason' => ['required'", $request);
        self::assertStringContainsString('expected_payment_version: expectedPaymentVersion', $api);
        self::assertStringContainsString('link.payment.row_version', $page);
        self::assertStringContainsString('reversalReason.trim()', $page);
    }
}
