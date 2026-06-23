<?php

declare(strict_types=1);

namespace Tests\Unit\ReferenceData;

use PHPUnit\Framework\TestCase;

final class ReferenceDataLookupBoundaryTest extends TestCase
{
    public function test_global_reference_lookups_accept_tenant_and_platform_guards_without_tenant_context(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3).'/app/Modules/ReferenceData/Routes/api.php',
        );

        self::assertStringContainsString('$authenticatedLookupGuards', $source);
        self::assertStringContainsString("'auth:'.\$authenticatedLookupGuards", $source);
        self::assertStringContainsString("Route::get(\$path.'/lookup'", $source);

        $lookupGroupStart = strpos($source, '// Reference catalogues are global SaaS data.');
        $mutationGroupStart = strpos($source, "Route::prefix('api/v1')", $lookupGroupStart + 1);
        self::assertNotFalse($lookupGroupStart);
        self::assertNotFalse($mutationGroupStart);

        $lookupGroup = substr($source, $lookupGroupStart, $mutationGroupStart - $lookupGroupStart);
        self::assertStringNotContainsString('$currentTenant', $lookupGroup);
    }

    public function test_lookup_request_does_not_require_a_tenant_scoped_request(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3).'/app/Modules/ReferenceData/Http/Requests/LookupReferenceDataRequest.php',
        );

        self::assertStringContainsString('extends QueryRequest', $source);
        self::assertStringNotContainsString('extends TenantScopedRequest', $source);
        self::assertStringContainsString('return $this->user() !== null;', $source);
    }
}
