<?php

declare(strict_types=1);

namespace Modules\Tenant\Tests;

use Illuminate\Validation\ValidationException;
use Modules\Tenant\Services\Plans\TenantPlanSchema;
use Tests\TestCase;

final class TenantPlanSchemaTest extends TestCase
{
    public function test_it_normalizes_unique_plan_modules_and_positive_limits(): void
    {
        $schema = new TenantPlanSchema();

        self::assertSame(
            ['enabled_modules' => ['inventory', 'sales']],
            $schema->normalizeFeatures([
                'enabled_modules' => [' Inventory ', 'sales', 'inventory'],
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
            TenantPlanSchema::SUPPORTED_MODULES,
        )));
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
