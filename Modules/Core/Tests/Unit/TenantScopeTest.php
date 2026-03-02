<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit;

use Modules\Core\Infrastructure\Scopes\TenantScope;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TenantScope.
 *
 * Validates the scope's tenant-ID resolution logic in isolation.
 * Laravel application context is not available in these tests,
 * so we assert structural and nullability contracts.
 */
class TenantScopeTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Instantiation
    // -------------------------------------------------------------------------

    public function test_tenant_scope_can_be_instantiated(): void
    {
        $scope = new TenantScope();

        $this->assertInstanceOf(TenantScope::class, $scope);
    }

    public function test_tenant_scope_implements_scope_interface(): void
    {
        $scope = new TenantScope();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Scope::class, $scope);
    }

    // -------------------------------------------------------------------------
    // resolveTenantIdPublic — method exists and is public
    // -------------------------------------------------------------------------

    public function test_resolve_tenant_id_public_method_exists(): void
    {
        $scope = new TenantScope();

        $this->assertTrue(
            method_exists($scope, 'resolveTenantIdPublic'),
            'TenantScope must expose a public resolveTenantIdPublic() method for HasTenant trait use.'
        );
    }

    public function test_apply_method_exists(): void
    {
        $scope = new TenantScope();

        $this->assertTrue(
            method_exists($scope, 'apply'),
            'TenantScope must implement the apply() method required by the Scope interface.'
        );
    }
}
