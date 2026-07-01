<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\DTOs\CurrentOrganizationUnitContext;
use Modules\Core\DTOs\CurrentTenantContext;
use Modules\Core\DTOs\CurrentUserContext;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\User\Models\UserModel;

abstract class TestCase extends BaseTestCase
{
    protected function trustTenantScopedRequestContextFromPayload(): void
    {
        $this->app->resolving(TenantScopedRequest::class, function (TenantScopedRequest $request): void {
            $request->attributes->set(
                (string) config('core.current_tenant.id_attribute', 'current_tenant_id'),
                $this->positiveIntOrNull($request->input('tenant_id')),
            );
            $request->attributes->set(
                (string) config('core.current_organization_unit.id_attribute', 'current_organization_unit_id'),
                $this->positiveIntOrNull($request->input('organization_unit_id')),
            );
            $request->attributes->set(
                (string) config('core.current_user.id_attribute', 'current_user_id'),
                $this->positiveIntOrNull(auth()->id()),
            );
        });
    }

    protected function tenantGetJson(int $tenantId, string $uri, array $headers = []): TestResponse
    {
        return $this->withTenantExecutionContext(
            $tenantId,
            fn (): TestResponse => $this->getJson($uri, $headers),
        );
    }

    protected function tenantPostJson(int $tenantId, string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->withTenantExecutionContext(
            $tenantId,
            fn (): TestResponse => $this->postJson($uri, $data, $headers),
        );
    }

    protected function tenantPatchJson(int $tenantId, string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->withTenantExecutionContext(
            $tenantId,
            fn (): TestResponse => $this->patchJson($uri, $data, $headers),
        );
    }

    protected function tenantDeleteJson(int $tenantId, string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->withTenantExecutionContext(
            $tenantId,
            fn (): TestResponse => $this->deleteJson($uri, $data, $headers),
        );
    }

    protected function withTenantExecutionContext(int $tenantId, callable $callback): mixed
    {
        return $this->app->make(TenantExecutionContextInterface::class)
            ->runForTenant($tenantId, $callback);
    }

    protected function withTenantRequestContext(
        int $tenantId,
        int $userId,
        callable $callback,
        ?int $organizationUnitId = null,
    ): mixed {
        return $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $userId, $callback, $organizationUnitId): mixed {
            $request = $this->app->make(Request::class);
            $user = UserModel::query()->findOrFail($userId);
            $guard = (string) config('module-auth.protected_route_guard', 'auth-api');
            $provider = config("auth.guards.{$guard}.provider");
            $applicationId = null;
            $tokenPayload = [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'application_id' => $applicationId,
            ];

            $tenant = DB::table('tenants')->where('id', $tenantId)->first();
            if ($tenant === null) {
                throw new \RuntimeException('Tenant request context fixture could not find the tenant.');
            }

            $attributes = [
                (string) config('core.current_user.request_attribute', 'current_user') => new CurrentUserContext(
                    $user,
                    $userId,
                    $guard,
                    is_string($provider) && trim($provider) !== '' ? trim($provider) : null,
                    $applicationId,
                    $tokenPayload,
                ),
                (string) config('core.current_user.id_attribute', 'current_user_id') => $userId,
                (string) config('core.current_user.guard_attribute', 'current_user_guard') => $guard,
                (string) config('core.current_user.provider_attribute', 'current_user_provider') => is_string($provider) ? $provider : null,
                (string) config('core.current_user.application_attribute', 'current_application_id') => $applicationId,
                (string) config('module-auth.current_user_context.token_payload_attribute', 'auth_access_token') => $tokenPayload,
                (string) config('core.current_tenant.request_attribute', 'current_tenant') => new CurrentTenantContext(
                    new DataRecord((array) $tenant),
                    $tenantId,
                    (string) $tenant->code,
                    (string) $tenant->uuid,
                    property_exists($tenant, 'primary_domain') && is_scalar($tenant->primary_domain) ? (string) $tenant->primary_domain : null,
                    (string) $tenant->status,
                    $applicationId,
                    'test_request_context',
                ),
                (string) config('core.current_tenant.id_attribute', 'current_tenant_id') => $tenantId,
                (string) config('core.current_tenant.code_attribute', 'current_tenant_code') => (string) $tenant->code,
                (string) config('core.current_tenant.uuid_attribute', 'current_tenant_uuid') => (string) $tenant->uuid,
                (string) config('core.current_tenant.domain_attribute', 'current_tenant_domain') => null,
                (string) config('core.current_tenant.status_attribute', 'current_tenant_status') => (string) $tenant->status,
                (string) config('core.current_tenant.active_attribute', 'current_tenant_is_active') => (string) $tenant->status === 'active',
                (string) config('core.current_tenant.application_attribute', 'current_application_id') => $applicationId,
                (string) config('core.current_tenant.source_attribute', 'current_tenant_source') => 'test_request_context',
            ];

            if ($organizationUnitId !== null) {
                $organizationUnit = DB::table('organization_units')
                    ->where('tenant_id', $tenantId)
                    ->where('id', $organizationUnitId)
                    ->first();
                if ($organizationUnit === null) {
                    throw new \RuntimeException('Tenant request context fixture could not find the organization unit.');
                }

                $attributes = [
                    ...$attributes,
                    (string) config('core.current_organization_unit.request_attribute', 'current_organization_unit') => new CurrentOrganizationUnitContext(
                        new DataRecord((array) $organizationUnit),
                        $organizationUnitId,
                        $tenantId,
                        is_scalar($organizationUnit->code ?? null) ? (string) $organizationUnit->code : null,
                        is_scalar($organizationUnit->path ?? null) ? (string) $organizationUnit->path : null,
                        (string) $organizationUnit->name,
                        (bool) $organizationUnit->is_active,
                        $applicationId,
                        'test_request_context',
                    ),
                    (string) config('core.current_organization_unit.id_attribute', 'current_organization_unit_id') => $organizationUnitId,
                    (string) config('core.current_organization_unit.tenant_id_attribute', 'current_organization_unit_tenant_id') => $tenantId,
                    (string) config('core.current_organization_unit.code_attribute', 'current_organization_unit_code') => is_scalar($organizationUnit->code ?? null) ? (string) $organizationUnit->code : null,
                    (string) config('core.current_organization_unit.path_attribute', 'current_organization_unit_path') => is_scalar($organizationUnit->path ?? null) ? (string) $organizationUnit->path : null,
                    (string) config('core.current_organization_unit.name_attribute', 'current_organization_unit_name') => (string) $organizationUnit->name,
                    (string) config('core.current_organization_unit.active_attribute', 'current_organization_unit_is_active') => (bool) $organizationUnit->is_active,
                    (string) config('core.current_organization_unit.application_attribute', 'current_application_id') => $applicationId,
                    (string) config('core.current_organization_unit.source_attribute', 'current_organization_unit_source') => 'test_request_context',
                ];
            }

            $previousAttributes = [];
            foreach ($attributes as $key => $value) {
                $previousAttributes[$key] = [
                    'exists' => $request->attributes->has($key),
                    'value' => $request->attributes->get($key),
                ];
            }

            $previousUserResolver = $request->getUserResolver();

            try {
                foreach ($attributes as $key => $value) {
                    $request->attributes->set($key, $value);
                }
                $request->setUserResolver(static fn () => $user);

                return $callback();
            } finally {
                foreach ($previousAttributes as $key => $previous) {
                    if ($previous['exists']) {
                        $request->attributes->set($key, $previous['value']);
                    } else {
                        $request->attributes->remove($key);
                    }
                }
                $request->setUserResolver($previousUserResolver);
            }
        });
    }

    private function positiveIntOrNull(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $integer = (int) $value;

        return $integer > 0 ? $integer : null;
    }
}
