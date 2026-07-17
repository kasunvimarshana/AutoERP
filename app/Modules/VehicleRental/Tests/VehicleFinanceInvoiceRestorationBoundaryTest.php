<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use PHPUnit\Framework\TestCase;

final class VehicleFinanceInvoiceRestorationBoundaryTest extends TestCase
{
    public function test_finance_installment_link_is_restored_by_vehicle_rental_owner(): void
    {
        $handler = $this->source('../Services/Invoice/VehicleFinanceInvoiceRestorationHandler.php');
        $provider = $this->source('../Providers/VehicleRentalServiceProvider.php');
        $constants = $this->source('../Constants/VehicleRentalInvoiceSource.php');

        self::assertStringContainsString(
            'VehicleRentalInvoiceSource::VEHICLE_FINANCE_INSTALLMENT',
            $handler,
        );
        self::assertStringContainsString("'invoice_id' => null", $handler);
        self::assertStringContainsString("'row_version' => (int) \$installment->row_version + 1", $handler);
        self::assertStringContainsString('->lockForUpdate()', $handler);
        self::assertStringContainsString(
            'VehicleFinanceInvoiceRestorationHandler::class',
            $provider,
        );
        self::assertStringContainsString(
            "public const VEHICLE_FINANCE_INSTALLMENT = 'vehicle_finance_installment';",
            $constants,
        );
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(__DIR__.'/'.$relativePath);
        self::assertIsString($source);

        return $source;
    }
}
