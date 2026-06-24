<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tenant\Services\Hosts\PlatformHostPolicy;
use Symfony\Component\HttpFoundation\Response;

final class RequireCentralHostMiddleware
{
    public function __construct(private readonly PlatformHostPolicy $hosts) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->hosts->isCentralHost($request->getHost())) {
            return new JsonResponse([
                'message' => 'This endpoint is available only through an authorized platform host.',
                'code' => 'PLATFORM_HOST_REQUIRED',
            ], 404);
        }

        return $next($request);
    }
}
