<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;
use Modules\Core\DTOs\CurrentOrganizationUnitContext;
use Modules\Core\DTOs\CurrentTenantContext;
use Modules\Core\DTOs\CurrentUserContext;
use Modules\Core\DTOs\DataRecord;
use PHPUnit\Framework\TestCase;

final class CurrentContextTest extends TestCase
{
    public function test_contexts_require_matching_positive_identifiers(): void
    {
        $tenant = new CurrentTenantContext(
            new DataRecord(['id' => 10]),
            10,
            'AUTOERP',
            '2bdccf93-b5aa-4c59-b71c-bff3aa1e0eb1',
            'tenant-10',
            'example.test',
            'active',
            true,
            null,
            'request_host',
        );

        $organizationUnit = new CurrentOrganizationUnitContext(
            new DataRecord(['id' => 20, 'tenant_id' => 10]),
            20,
            10,
            'MAIN',
            '/main',
            'Main',
            true,
            null,
            'authenticated_user',
        );

        self::assertSame(10, $tenant->tenantId());
        self::assertSame(20, $organizationUnit->organizationUnitId());
    }

    public function test_user_context_rejects_an_identifier_that_does_not_match_the_user(): void
    {
        $user = $this->createMock(Authenticatable::class);
        $user->method('getAuthIdentifier')->willReturn(99);

        $this->expectException(InvalidArgumentException::class);

        new CurrentUserContext($user, 100, 'auth-api', 'users', null);
    }
}
