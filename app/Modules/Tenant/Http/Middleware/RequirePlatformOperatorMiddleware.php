<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\PlatformOperatorCheckerInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Symfony\Component\HttpFoundation\Response;

final class RequirePlatformOperatorMiddleware
{
    public function __construct(
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly PlatformOperatorCheckerInterface $platformOperators,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $context = $this->currentUser->current();
        $userId = $context?->userId();
        if (
            $userId === null
            || ! $this->platformOperators->isPlatformOperator($userId)
        ) {
            return new JsonResponse([
                'message' => 'Platform operator access is required.',
                'code' => 'PLATFORM_OPERATOR_REQUIRED',
            ], 403);
        }

        return $this->executionContext->runAsControlPlane(
            static fn (): Response => $next($request),
        );
    }
}
