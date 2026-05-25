<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Modules\Core\Application\Contracts\CurrentUserContextResolverInterface;
use Modules\Core\Application\DTO\CurrentUserContext;
use Modules\User\Application\Repositories\UserTenantRepositoryInterface;
use Throwable;

final class CurrentUserContextResolver implements CurrentUserContextResolverInterface
{
    public function __construct(
        private readonly AuthFactory $authFactory,
        private readonly UserTenantRepositoryInterface $userTenants,
    ) {
    }

    public function resolve(Request $request): ?CurrentUserContext
    {
        $tokenPayloadAttribute = $this->configString('token_payload_attribute', 'auth_access_token');
        $tokenPayload = $request->attributes->get($tokenPayloadAttribute);
        $tokenPayload = is_array($tokenPayload) ? $tokenPayload : [];

        foreach ($this->candidateGuards($request) as $guardName) {
            $user = $this->authFactory->guard($guardName)->user();
            if (! $user instanceof Authenticatable) {
                continue;
            }

            $userId = (string) $user->getAuthIdentifier();
            if ($userId === '') {
                continue;
            }

            $provider = $this->guardProvider($guardName);

            return new CurrentUserContext(
                $user,
                $userId,
                $guardName,
                $provider,
                $this->toNullableInt(data_get($user, 'tenant_id')),
                $this->toNullableInt(data_get($user, 'organization_unit_id')),
                $this->resolveApplicationId($request, $tokenPayload),
                $tokenPayload,
            );
        }

        return null;
    }

    public function resolveRequestedTenantId(Request $request): ?int
    {
        foreach ($this->configArray('tenant_input_keys', ['tenant_id']) as $key) {
            $value = $request->input($key);
            $tenantId = $this->toNullableInt($value);
            if ($tenantId !== null) {
                return $tenantId;
            }
        }

        foreach ($this->configArray('tenant_route_keys', ['tenant_id', 'tenant']) as $key) {
            $value = $request->route($key);
            $tenantId = $this->toNullableInt($value);
            if ($tenantId !== null) {
                return $tenantId;
            }
        }

        foreach ($this->configArray('tenant_header_keys', ['X-Tenant-Id', 'X-Tenant']) as $key) {
            $tenantId = $this->toNullableInt($request->headers->get($key));
            if ($tenantId !== null) {
                return $tenantId;
            }
        }

        return null;
    }

    public function hasTenantAccess(CurrentUserContext $context, int $tenantId): bool
    {
        if ($tenantId <= 0) {
            return false;
        }

        if ($context->tenantId() === $tenantId) {
            return true;
        }

        return $this->userTenants->existsForTenantAndUser($tenantId, $context->userIdAsInt());
    }

    /**
     * @return list<string>
     */
    private function candidateGuards(Request $request): array
    {
        $guards = [];

        $route = $request->route();
        if ($route !== null) {
            foreach ($route->gatherMiddleware() as $middleware) {
                if (! is_string($middleware) || ! str_starts_with($middleware, 'auth')) {
                    continue;
                }

                $segments = explode(':', $middleware, 2);
                if (count($segments) < 2 || trim($segments[1]) === '') {
                    continue;
                }

                foreach (explode(',', $segments[1]) as $guardName) {
                    $guardName = trim($guardName);
                    if ($guardName !== '') {
                        $guards[] = $guardName;
                    }
                }
            }
        }

        $contextGuardAttribute = $this->configString('guard_attribute', 'current_user_guard');
        $contextGuard = $request->attributes->get($contextGuardAttribute);
        if (is_string($contextGuard) && trim($contextGuard) !== '') {
            $guards[] = trim($contextGuard);
        }

        $defaultGuard = $this->configStringFromPath('auth.defaults.guard', 'web');
        if ($defaultGuard !== '') {
            $guards[] = $defaultGuard;
        }

        foreach (array_keys($this->configArrayFromPath('auth.guards', [])) as $guardName) {
            if (is_string($guardName) && trim($guardName) !== '') {
                $guards[] = trim($guardName);
            }
        }

        /** @var list<string> $uniqueGuards */
        $uniqueGuards = array_values(array_unique($guards));

        return $uniqueGuards;
    }

    /**
     * @param array<string, mixed> $tokenPayload
     */
    private function resolveApplicationId(Request $request, array $tokenPayload): ?string
    {
        foreach ($this->configArray('application_input_keys', ['application_id', 'app_id', 'client_id']) as $key) {
            $value = $request->input($key);
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        $applicationHeaderKeys = $this->configArray(
            'application_header_keys',
            ['X-Application-Id', 'X-App-Id', 'X-Client-Id'],
        );

        foreach ($applicationHeaderKeys as $key) {
            $value = $request->headers->get($key);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        foreach (['application_id', 'app_id', 'client_id'] as $key) {
            $value = $tokenPayload[$key] ?? null;
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function guardProvider(string $guardName): ?string
    {
        $provider = $this->configStringFromPath('auth.guards.' . $guardName . '.provider', '');

        return $provider !== '' ? $provider : null;
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private function configString(string $key, string $fallback): string
    {
        return $this->configStringFromPath('core.current_user.' . $key, $fallback);
    }

    /**
     * @param list<string> $fallback
     * @return list<string>
     */
    private function configArray(string $key, array $fallback): array
    {
        $resolved = $this->configArrayFromPath('core.current_user.' . $key, $fallback);
        $values = [];

        foreach ($resolved as $value) {
            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $values[] = trim($value);
        }

        /** @var list<string> $values */
        return array_values(array_unique($values));
    }

    private function configStringFromPath(string $path, string $fallback): string
    {
        if (! function_exists('config')) {
            return $fallback;
        }

        try {
            return (string) config($path, $fallback);
        } catch (Throwable) {
            return $fallback;
        }
    }

    /**
     * @param array<string, mixed> $fallback
     * @return array<string, mixed>
     */
    private function configArrayFromPath(string $path, array $fallback): array
    {
        if (! function_exists('config')) {
            return $fallback;
        }

        try {
            $resolved = config($path, $fallback);
        } catch (Throwable) {
            return $fallback;
        }

        return is_array($resolved) ? $resolved : $fallback;
    }
}
