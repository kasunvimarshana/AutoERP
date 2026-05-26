<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SSOContextMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $providerKey = $request->input('provider_key') ?? $request->headers->get('X-Provider-Key');
        $clientKey = $request->input('client_key') ?? $request->headers->get('X-Client-Key');

        if (is_scalar($providerKey) && trim((string) $providerKey) !== '') {
            $request->attributes->set('auth_sso_provider_key', trim((string) $providerKey));
        }

        if (is_scalar($clientKey) && trim((string) $clientKey) !== '') {
            $request->attributes->set('auth_sso_client_key', trim((string) $clientKey));
        }

        return $next($request);
    }
}
