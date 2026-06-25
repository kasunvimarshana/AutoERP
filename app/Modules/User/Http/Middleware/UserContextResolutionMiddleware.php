<?php

declare(strict_types=1);

namespace Modules\User\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\Contracts\AuthenticatedUserProviderInterface;
use Symfony\Component\HttpFoundation\Response;

final class UserContextResolutionMiddleware
{
    public function __construct(private readonly AuthenticatedUserProviderInterface $users) {}

    public function handle(Request $request, Closure $next): Response
    {
        $record = $this->users->currentUserRecord();
        if ($record === null) {
            return new JsonResponse(['message' => 'Authenticated user context is not available.'], 401);
        }

        $request->attributes->set($this->configString('request_attribute', 'current_user_record'), $record->toArray());
        $request->attributes->set($this->configString('id_attribute', 'current_user_record_id'), (int) $record->id());
        $request->attributes->set(
            $this->configString('tenant_id_attribute', 'current_user_record_tenant_id'),
            (int) $record->get('tenant_id'),
        );
        $request->attributes->set(
            $this->configString('organization_unit_id_attribute', 'current_user_record_organization_unit_id'),
            $record->get('organization_unit_id') !== null ? (int) $record->get('organization_unit_id') : null,
        );

        return $next($request);
    }

    private function configString(string $key, string $fallback): string
    {
        $value = (string) config('user.context.'.$key, $fallback);

        return $value !== '' ? $value : $fallback;
    }
}
