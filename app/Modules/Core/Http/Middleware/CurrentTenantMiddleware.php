<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Resolves tenant context and enforces authenticated tenant access. */
final class CurrentTenantMiddleware
{
    public function __construct(
        private readonly ResolveCurrentTenantMiddleware $context,
        private readonly RequireCurrentTenantAccessMiddleware $access,
    ) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        return $this->context->handle(
            $request,
            fn (Request $request): Response => $this->access->handle($request, $next),
        );
    }
}
