<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Http\Responses\ApiErrorResponseFactory;
use Symfony\Component\HttpFoundation\Response;

/** Ensures every API JSON error follows the platform error contract. */
final class EnsureApiErrorResponseMiddleware
{
    public function __construct(private readonly ApiErrorResponseFactory $responses) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldNormalize($request, $response)) {
            return $response;
        }

        $payload = json_decode((string) $response->getContent(), true);
        if (! is_array($payload) || $this->isNormalizedPayload($payload)) {
            return $response;
        }

        $message = is_string($payload['message'] ?? null) && trim($payload['message']) !== ''
            ? trim($payload['message'])
            : 'API request failed.';
        $fields = is_array($payload['errors'] ?? null) ? $payload['errors'] : [];
        $details = $fields === [] ? [] : ['fields' => $fields];

        $normalized = $this->responses->forStatus($response->getStatusCode(), $message, $details);

        $normalized->headers->add($response->headers->all());
        $normalized->headers->remove('Content-Length');
        $normalized->headers->set('Content-Type', 'application/json');

        foreach ($response->headers->getCookies() as $cookie) {
            $normalized->headers->setCookie($cookie);
        }

        return $normalized;
    }

    /** @param array<string, mixed> $payload */
    private function isNormalizedPayload(array $payload): bool
    {
        $error = $payload['error'] ?? null;

        return ($payload['success'] ?? null) === false
            && is_array($error)
            && is_string($error['code'] ?? null)
            && trim($error['code']) !== ''
            && is_string($error['type'] ?? null)
            && trim($error['type']) !== ''
            && is_string($error['message'] ?? null)
            && trim($error['message']) !== '';
    }

    private function shouldNormalize(Request $request, Response $response): bool
    {
        return $response->getStatusCode() >= 400
            && ($request->is('api/*') || $request->expectsJson())
            && str_contains((string) $response->headers->get('Content-Type'), 'json');
    }
}
