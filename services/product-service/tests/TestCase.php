<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected string $tenantId = 'tenant-test-001';

    protected function setUp(): void
    {
        parent::setUp();
        app()->instance('tenant_id', $this->tenantId);
    }

    /**
     * Build a minimal JWT-claims stub and attach it to the request attributes.
     * Avoids Keycloak network calls in tests.
     */
    protected function withAuthHeaders(array $roles = ['products.read', 'products.write', 'products.delete', 'categories.write', 'categories.delete']): array
    {
        return [
            'Authorization' => 'Bearer test-token',
            'X-Tenant-ID'   => $this->tenantId,
        ];
    }

    /**
     * Bypass auth + tenant middleware by seeding request attributes directly.
     */
    protected function actingAsTenant(array $roles = []): static
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\KeycloakAuthMiddleware::class,
            \App\Http\Middleware\TenantMiddleware::class,
            \App\Http\Middleware\RBACMiddleware::class,
        ]);

        app()->instance('tenant_id', $this->tenantId);

        return $this;
    }
}
