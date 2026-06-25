<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Support;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Responses\ApiErrorResponseFactory;
use Modules\Core\Results\Error;
use Modules\Tenant\Constants\TenantErrorCode;

final class TenantApiResponder
{
    public static function error(Error $error): JsonResponse
    {
        $status = self::status($error->code);

        return (new ApiErrorResponseFactory())->make(
            code: $error->code,
            message: $error->message,
            status: $status,
            type: self::type($status),
            details: $error->context,
        );
    }

    private static function status(string $code): int
    {
        return match ($code) {
            TenantErrorCode::NOT_FOUND => 404,
            TenantErrorCode::DUPLICATE_CODE, TenantErrorCode::DUPLICATE_DOMAIN,
            TenantErrorCode::CONFLICT, TenantErrorCode::VERSION_CONFLICT => 409,
            TenantErrorCode::FILE_OPERATION_FAILED => 500,
            default => 422,
        };
    }

    private static function type(int $status): string
    {
        return match ($status) {
            404 => 'not_found',
            409 => 'conflict',
            422 => 'domain',
            default => $status >= 500 ? 'infrastructure' : 'http',
        };
    }

    private function __construct() {}
}
