<?php

declare(strict_types=1);

namespace Modules\Core\Http\Responses;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Middleware\RequestCorrelationIdMiddleware;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

final class ApiErrorResponseFactory
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function make(
        string $code,
        string $message,
        int $status,
        string $type,
        array $details = [],
    ): JsonResponse {
        $code = trim($code);
        $message = trim($message);
        $type = trim($type);

        if ($code === '' || $message === '' || $type === '') {
            throw new InvalidArgumentException('API error code, type and message are required.');
        }

        if ($status < Response::HTTP_BAD_REQUEST || $status > 599) {
            throw new InvalidArgumentException('API error status must be between 400 and 599.');
        }

        $correlationId = $this->correlationId();
        if ($correlationId !== null) {
            $details['correlation_id'] = $correlationId;
        }

        $payload = [
            'success' => false,
            'message' => $message,
            'error' => [
                'code' => $code,
                'type' => $type,
                'message' => $message,
                'details' => (object) $details,
            ],
        ];

        if (isset($details['fields']) && is_array($details['fields'])) {
            $payload['errors'] = $details['fields'];
        }

        $response = new JsonResponse($payload, $status);
        if ($correlationId !== null) {
            $response->headers->set(RequestCorrelationIdMiddleware::HEADER, $correlationId);
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public function forStatus(int $status, string $message, array $details = []): JsonResponse
    {
        $hasValidationFields = isset($details['fields']) && is_array($details['fields']);
        $type = $hasValidationFields ? 'validation' : $this->typeForStatus($status);

        return $this->make($this->codeForStatus($status, $type), $message, $status, $type, $details);
    }

    private function correlationId(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        $value = request()->attributes->get(RequestCorrelationIdMiddleware::ATTRIBUTE);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function typeForStatus(int $status): string
    {
        return match ($status) {
            Response::HTTP_UNAUTHORIZED => 'authentication',
            Response::HTTP_FORBIDDEN => 'authorization',
            Response::HTTP_NOT_FOUND => 'not_found',
            Response::HTTP_CONFLICT => 'conflict',
            Response::HTTP_UNPROCESSABLE_ENTITY => 'domain',
            default => $status >= Response::HTTP_INTERNAL_SERVER_ERROR ? 'infrastructure' : 'http',
        };
    }

    private function codeForStatus(int $status, string $type): string
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
