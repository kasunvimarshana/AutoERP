<?php

declare(strict_types=1);

namespace Modules\Tenant\Tests;

use Illuminate\Validation\ValidationException;
use Modules\Core\Tenancy\TenantFeature;
use Modules\Tenant\Services\Plans\TenantPlanSchema;
use Tests\TestCase;

final class TenantPlanSchemaTest extends TestCase
{
    private const RETIRED_VEHICLE_RENTAL_MODULE = 'vehicle-rental';

    public function test_it_normalizes_unique_plan_modules_and_positive_limits(): void
    {
        $schema = new TenantPlanSchema();

        self::assertSame(
            ['enabled_modules' => ['inventory', 'purchase', TenantFeature::HR]],
            $schema->normalizeFeatures([
                'enabled_modules' => [' Inventory ', 'purchase', 'inventory', ' HR '],
            ]),
        );
        self::assertSame(
            ['max_users' => 25, 'max_storage_mb' => 1024],
            $schema->normalizeLimits([
                'max_users' => '25',
                'max_storage_mb' => 1024,
            ]),
        );
        self::assertSame('1250.500000', $schema->normalizePrice('1250.5'));
    }

    public function test_foundation_and_plan_controlled_catalogues_do_not_overlap(): void
    {
        self::assertSame([], array_values(array_intersect(
            TenantPlanSchema::ALWAYS_ON_MODULES,
            array_keys(TenantPlanSchema::SUPPORTED_MODULES),
        )));
    }

    public function test_hr_is_a_plan_controlled_commercial_module(): void
    {
        $schema = new TenantPlanSchema();

        self::assertContains(TenantFeature::HR, $schema->supportedModuleCodes());
        self::assertArrayHasKey(TenantFeature::HR, TenantPlanSchema::SUPPORTED_MODULES);
        self::assertNotContains(TenantFeature::HR, TenantPlanSchema::ALWAYS_ON_MODULES);
    }

    public function test_vehicle_rental_is_retired_from_existing_plan_snapshots_and_new_catalogues(): void
    {
        $schema = new TenantPlanSchema();
        $features = ['enabled_modules' => ['inventory', self::RETIRED_VEHICLE_RENTAL_MODULE]];

        self::assertSame(3, TenantPlanSchema::SCHEMA_VERSION);
        self::assertSame(
            ['enabled_modules' => ['inventory']],
            $schema->normalizePersistedFeatures($features, 1),
        );
        self::assertSame(
            ['enabled_modules' => ['inventory']],
            $schema->normalizePersistedFeatures($features, 2),
        );
        self::assertNotContains(self::RETIRED_VEHICLE_RENTAL_MODULE, $schema->supportedModuleCodes());

        $this->expectException(ValidationException::class);
        $schema->normalizeFeatures(['enabled_modules' => [self::RETIRED_VEHICLE_RENTAL_MODULE]]);
    }

    public function test_unknown_modules_are_rejected_instead_of_silently_enabled(): void
    {
        $this->expectException(ValidationException::class);

        (new TenantPlanSchema())->normalizeFeatures([
            'enabled_modules' => ['inventory', 'unknown-module'],
        ]);
    }

    public function test_zero_or_negative_limits_are_rejected(): void
    {
        $this->expectException(ValidationException::class);

        (new TenantPlanSchema())->normalizeLimits(['max_users' => 0]);
    }
}
