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

        $nestedError = is_array($payload['error'] ?? null) ? $payload['error'] : [];
        $message = $this->nonEmptyString($nestedError['message'] ?? $payload['message'] ?? null)
            ?? 'API request failed.';
        $code = $this->nonEmptyString($nestedError['code'] ?? $payload['code'] ?? null);
        $type = $this->nonEmptyString($nestedError['type'] ?? $payload['type'] ?? null);
        $details = is_array($nestedError['details'] ?? null)
            ? $nestedError['details']
            : (is_array($payload['details'] ?? null) ? $payload['details'] : []);
        if (is_array($nestedError['context'] ?? null)) {
            $details = array_merge($details, $nestedError['context']);
        }
        if (is_array($payload['errors'] ?? null)) {
            $details['fields'] = $payload['errors'];
        }

        $status = $response->getStatusCode();
        $normalized = $code !== null && $type !== null
            ? $this->responses->make($code, $message, $status, $type, $details)
            : ($code !== null
                ? $this->responses->make($code, $message, $status, $this->typeForStatus($status), $details)
                : $this->responses->forStatus($status, $message, $details));

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
            && $this->nonEmptyString($error['code'] ?? null) !== null
            && $this->nonEmptyString($error['type'] ?? null) !== null
            && $this->nonEmptyString($error['message'] ?? null) !== null;
    }

    private function shouldNormalize(Request $request, Response $response): bool
    {
        return $response->getStatusCode() >= 400
            && ($request->is('api/*') || $request->expectsJson())
            && str_contains((string) $response->headers->get('Content-Type'), 'json');
    }

    private function nonEmptyString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function typeForStatus(int $status): string
    {
        return match ($status) {
            401 => 'authentication',
            403 => 'authorization',
            404 => 'not_found',
            409 => 'conflict',
            422 => 'validation',
            default => $status >= 500 ? 'infrastructure' : 'http',
        };
    }
}
