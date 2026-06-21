<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Modules\Core\Contracts\CurrentUserContextResolverInterface;
use Modules\Core\DTOs\CurrentUserContext;

final class CurrentUserContextResolver implements CurrentUserContextResolverInterface
{
    public function __construct(private readonly AuthFactory $authFactory) {}

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

            $userId = $this->toNullableInt($user->getAuthIdentifier());
            if ($userId === null) {
                continue;
            }

            return new CurrentUserContext(
                $user,
                $userId,
                $guardName,
                $this->guardProvider($guardName),
                $this->resolveApplicationId($request, $tokenPayload),
                $tokenPayload,
            );
        }

        return null;
    }

    /** @return list<string> */
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

        $contextGuardAttribute = (string) config('core.current_user.guard_attribute', 'current_user_guard');
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

    /** @param array<string, mixed> $tokenPayload */
    private function resolveApplicationId(Request $request, array $tokenPayload): ?string
    {
        foreach ($this->configArray('application_input_keys', ['application_id', 'app_id', 'client_id']) as $key) {
            $value = $request->input($key);
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        foreach ($this->configArray('application_header_keys', ['X-Application-Id', 'X-App-Id', 'X-Client-Id']) as $key) {
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
        $provider = $this->configStringFromPath('auth.guards.'.$guardName.'.provider', '');

        return $provider !== '' ? $provider : null;
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private function configString(string $key, string $fallback): string
    {
        return (string) config('module-auth.current_user_context.'.$key, $fallback);
    }

    /**
     * @param list<string> $fallback
     * @return list<string>
     */
    private function configArray(string $key, array $fallback): array
    {
        $resolved = config('module-auth.current_user_context.'.$key, $fallback);
        if (! is_array($resolved)) {
            return $fallback;
        }

        $values = [];
        foreach ($resolved as $value) {
            if (is_string($value) && trim($value) !== '') {
                $values[] = trim($value);
            }
        }

        /** @var list<string> $values */
        return array_values(array_unique($values));
    }

    private function configStringFromPath(string $path, string $fallback): string
    {
        return (string) config($path, $fallback);
    }

    /** @param array<string, mixed> $fallback @return array<string, mixed> */
    private function configArrayFromPath(string $path, array $fallback): array
    {
        $resolved = config($path, $fallback);

        return is_array($resolved) ? $resolved : $fallback;
    }
}
