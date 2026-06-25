<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenant\Services\Platform\TenantSchemaCompatibilityService;
use Tests\TestCase;

final class TenantSchemaCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_migrated_schema_matches_tenant_runtime_contract(): void
    {
        $result = app(TenantSchemaCompatibilityService::class)->inspect();

        self::assertTrue($result['compatible']);
        self::assertSame([], $result['missing_tables']);
        self::assertSame([], $result['missing_columns']);
    }
}
