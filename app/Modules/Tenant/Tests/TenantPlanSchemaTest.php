<?php

declare(strict_types=1);

namespace Modules\Tenant\Tests;

use Illuminate\Validation\ValidationException;
use Modules\Tenant\Services\Plans\TenantModuleCatalogue;
use Modules\Tenant\Services\Plans\TenantPlanSchema;
use Tests\TestCase;

final class TenantPlanSchemaTest extends TestCase
{
    public function test_it_normalizes_unique_plan_modules_and_positive_limits(): void
    {
        $schema = new TenantPlanSchema(new TenantModuleCatalogue());

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
            (new TenantModuleCatalogue())->foundationCodes(),
            (new TenantModuleCatalogue())->planControlledCodes(),
        )));
    }

    public function test_catalogue_covers_foundation_and_plan_controlled_modules(): void
    {
        $catalogue = new TenantModuleCatalogue();

        self::assertContains('uom', $catalogue->foundationCodes());
        self::assertContains('sequence', $catalogue->foundationCodes());
        self::assertContains('hr', $catalogue->planControlledCodes());
        self::assertContains('tax', $catalogue->planControlledCodes());
        self::assertContains('voucher', $catalogue->planControlledCodes());
        self::assertCount(count(array_unique($catalogue->allCodes())), $catalogue->allCodes());
    }

    public function test_unknown_modules_are_rejected_instead_of_silently_enabled(): void
    {
        $this->expectException(ValidationException::class);

        (new TenantPlanSchema(new TenantModuleCatalogue()))->normalizeFeatures([
            'enabled_modules' => ['inventory', 'unknown-module'],
        ]);
    }

    public function test_zero_or_negative_limits_are_rejected(): void
    {
        $this->expectException(ValidationException::class);

        (new TenantPlanSchema(new TenantModuleCatalogue()))->normalizeLimits(['max_users' => 0]);
    }
}
