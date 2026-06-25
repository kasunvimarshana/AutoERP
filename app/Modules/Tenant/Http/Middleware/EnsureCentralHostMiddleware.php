<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class EnsureCentralHostMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $this->normalizeHost($request->getHost());
        if ($host === null || ! in_array($host, $this->allowedHosts(), true)) {
            throw new NotFoundHttpException('Platform endpoint not found for this host.');
        }

        return $next($request);
    }

    /** @return list<string> */
    private function allowedHosts(): array
    {
        $configured = config('tenant.resolution.central_hosts', []);
        $hosts = is_array($configured) ? $configured : [];

        if (app()->environment(['local', 'testing'])) {
            $hosts = [...$hosts, 'localhost', '127.0.0.1', '::1'];
        }

        return array_values(array_unique(array_filter(array_map(
            fn (mixed $value): ?string => $this->normalizeHost($value),
            $hosts,
        ))));
    }

    private function normalizeHost(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $host = strtolower(rtrim(trim((string) $value), '.'));

        return $host === '' ? null : $host;
    }
}
