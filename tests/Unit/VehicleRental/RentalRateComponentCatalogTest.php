<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use Modules\VehicleRental\Enums\RentalRateComponentCode;
use Modules\VehicleRental\Enums\RentalRateUnit;
use Modules\VehicleRental\Services\RentalRateComponentCatalog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RentalRateComponentCatalogTest extends TestCase
{
    #[Test]
    public function it_exposes_one_authoritative_definition_for_each_supported_ui_rate_component(): void
    {
        $definitions = app(RentalRateComponentCatalog::class)->definitions();
        $byCode = collect($definitions)->keyBy('code');

        self::assertCount(count($definitions), $byCode);
        self::assertSame(
            RentalRateUnit::Month->value,
            $byCode->get(RentalRateComponentCode::BaseRental->value)['unit'],
        );
        self::assertContains(
            RentalRateUnit::Day->value,
            $byCode->get(RentalRateComponentCode::BaseRental->value)['supported_units'],
        );
        self::assertContains(
            RentalRateUnit::Week->value,
            $byCode->get(RentalRateComponentCode::BaseRental->value)['supported_units'],
        );
        self::assertTrue(
            $byCode->get(RentalRateComponentCode::BaseRental->value)['required'],
        );
        self::assertSame(
            [RentalRateUnit::Kilometre->value],
            $byCode->get(RentalRateComponentCode::ExcessKm->value)['supported_units'],
        );
        self::assertSame(
            [RentalRateUnit::Hour->value, RentalRateUnit::Minute->value],
            $byCode->get(RentalRateComponentCode::NormalOvertime->value)['supported_units'],
        );
        self::assertSame(
            [RentalRateUnit::Fixed->value],
            $byCode->get(RentalRateComponentCode::Repair->value)['supported_units'],
        );
    }
}
