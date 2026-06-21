<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Contracts\CurrentUserContextResolverInterface;
use Modules\Core\Http\Responses\ApiErrorResponseFactory;
use Symfony\Component\HttpFoundation\Response;

final class CurrentUserMiddleware
{
    public function __construct(
        private readonly CurrentUserContextResolverInterface $resolver,
        private readonly ApiErrorResponseFactory $responses,
    ) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $context = $this->resolver->resolve($request);

        if ($context === null) {
            if ($this->currentUserRequired()) {
                return $this->responses->forStatus(
                    Response::HTTP_UNAUTHORIZED,
                    'Unable to resolve authenticated user context.',
                );
            }

            return $next($request);
        }

        $request->setUserResolver(static fn () => $context->user());
        $request->attributes->set($this->configString('request_attribute', 'current_user'), $context);
        $request->attributes->set($this->configString('id_attribute', 'current_user_id'), $context->userId());
        $request->attributes->set($this->configString('guard_attribute', 'current_user_guard'), $context->guard());
        $request->attributes->set(
            $this->configString('provider_attribute', 'current_user_provider'),
            $context->provider(),
        );
        $request->attributes->set(
            $this->configString('application_attribute', 'current_application_id'),
            $context->applicationId(),
        );

        return $next($request);
    }

    private function currentUserRequired(): bool
    {
        return (bool) config('core.current_user.required', true);
    }

    private function configString(string $key, string $fallback): string
    {
        return (string) config('core.current_user.'.$key, $fallback);
    }
}
