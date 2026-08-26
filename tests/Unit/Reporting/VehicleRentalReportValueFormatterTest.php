<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use Modules\Core\Services\DecimalMath;
use Modules\Reporting\Services\VehicleRentalReportValueFormatter;
use Tests\TestCase;

final class VehicleRentalReportValueFormatterTest extends TestCase
{
    public function test_unavailable_measurement_remains_null_while_zero_remains_real(): void
    {
        $formatter = new VehicleRentalReportValueFormatter(new DecimalMath());

        self::assertNull($formatter->decimal(null));
        self::assertSame('0.000000', $formatter->decimal('0'));
        self::assertSame('12.500000', $formatter->nullableDecimal('12.5'));
    }
}
