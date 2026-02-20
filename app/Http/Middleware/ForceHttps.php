<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ForceHttps Middleware
 *
 * Redirects all non-HTTPS requests to HTTPS when the application is running
 * in a production environment and the FORCE_HTTPS environment variable is
 * set to true.  In local / testing environments the middleware is a no-op so
 * that developer HTTP tooling (Valet, Sail, etc.) continues to work unaffected.
 *
 * Configuration:
 *   FORCE_HTTPS=true   — enforce HTTPS (recommended in production)
 *   FORCE_HTTPS=false  — disable enforcement (default for dev / staging)
 *
 * ADR reference: docs/adr/007-vue3-spa-api-first.md
 */
class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldRedirect($request)) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        /** @var Response $response */
        $response = $next($request);

        // Add HSTS header when already on HTTPS
        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }

    private function shouldRedirect(Request $request): bool
    {
        return (bool) config('app.force_https', false)
            && ! $request->isSecure();
    }
}
