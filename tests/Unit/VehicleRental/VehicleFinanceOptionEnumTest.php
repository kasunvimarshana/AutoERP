<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use Modules\Invoice\Enums\InvoiceType;
use Modules\VehicleRental\Enums\VehicleFinanceInstallmentFrequency;
use Modules\VehicleRental\Enums\VehicleFinanceInterestMethod;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class VehicleFinanceOptionEnumTest extends TestCase
{
    #[Test]
    public function finance_option_sets_and_invoice_classification_are_explicit(): void
    {
        self::assertSame(
            ['flat', 'reducing_balance', 'custom'],
            array_column(VehicleFinanceInterestMethod::cases(), 'value'),
        );
        self::assertSame(
            ['weekly', 'monthly', 'quarterly', 'yearly'],
            array_column(VehicleFinanceInstallmentFrequency::cases(), 'value'),
        );
        self::assertSame('vehicle_finance', InvoiceType::VehicleFinance->value);
    }
}
