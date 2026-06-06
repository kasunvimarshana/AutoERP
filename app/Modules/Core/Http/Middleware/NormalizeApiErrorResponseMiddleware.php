<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class NormalizeApiErrorResponseMiddleware
{
    /**
     * Normalize legacy controller-level error payloads into the platform API error contract.
     *
     * Preconditions: downstream handlers may return any Symfony response.
     * Postconditions: API JSON error responses always include a stable `error` object.
     * Invariant: successful responses and already-normalized error responses are not modified.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldNormalize($request, $response)) {
            return $response;
        }

        $payload = json_decode((string) $response->getContent(), true);
        if (! is_array($payload) || array_key_exists('error', $payload)) {
            return $response;
        }

        $status = $response->getStatusCode();
        $message = isset($payload['message']) && is_string($payload['message'])
            ? $payload['message']
            : 'API request failed.';
        $fields = isset($payload['errors']) && is_array($payload['errors'])
            ? $payload['errors']
            : [];
        $type = $this->errorType($status, $fields !== []);
        $code = $this->errorCode($status, $type);
        $details = $fields !== [] ? ['fields' => $fields] : [];

        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'error' => [
                'code' => $code,
                'type' => $type,
                'message' => $message,
                'details' => (object) $details,
            ],
            ...($fields !== [] ? ['errors' => $fields] : []),
        ], $status);
    }

    private function shouldNormalize(Request $request, Response $response): bool
    {
        if ($response->getStatusCode() < 400) {
            return false;
        }

        if (! ($request->is('api/*') || $request->expectsJson())) {
            return false;
        }

        return str_contains((string) $response->headers->get('Content-Type'), 'json');
    }

    private function errorType(int $status, bool $hasValidationFields): string
    {
        if ($hasValidationFields) {
            return 'validation';
        }

        return match ($status) {
            401 => 'authentication',
            403 => 'authorization',
            404 => 'not_found',
            409 => 'conflict',
            422 => 'domain',
            default => $status >= 500 ? 'infrastructure' : 'http',
        };
    }

    private function errorCode(int $status, string $type): string
    {
        return match ($type) {
            'authentication' => 'AUTHENTICATION_FAILED',
            'authorization' => 'AUTHORIZATION_DENIED',
            'validation' => 'VALIDATION_FAILED',
            'not_found' => 'RESOURCE_NOT_FOUND',
            'conflict' => 'CONFLICT',
            'domain' => 'DOMAIN_RULE_FAILED',
            'infrastructure' => 'UNEXPECTED_ERROR',
            default => 'HTTP_'.(string) $status,
        };
    }
}
