<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Responses;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Auth\Http\Resources\AuthPayloadResource;
use Modules\Core\Http\Responses\ApiErrorResponseFactory;

final readonly class AuthResponseFactory
{
    public function __construct(private ApiErrorResponseFactory $errors) {}

    /** @param array<string,mixed> $payload */
    public function payload(array $payload): JsonResponse
    {
        return $this->noStore(response()->json(
            (new AuthPayloadResource($payload))->resolve(request()),
        ));
    }

    public function failure(AuthFailure $failure): JsonResponse
    {
        return $this->noStore($this->errors->make(
            $failure->errorCode,
            $failure->getMessage(),
            $failure->httpStatus,
            $this->errorType($failure->httpStatus),
            $failure->details,
        ));
    }

    public function success(array $payload = ['success' => true]): JsonResponse
    {
        return $this->noStore(response()->json($payload));
    }

    public function noStore(JsonResponse $response): JsonResponse
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    private function errorType(int $status): string
    {
        return match ($status) {
            401 => 'authentication',
            403 => 'authorization',
            409 => 'conflict',
            422 => 'validation',
            default => $status >= 500 ? 'infrastructure' : 'authentication',
        };
    }
}
