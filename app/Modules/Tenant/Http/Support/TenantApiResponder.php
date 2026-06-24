<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Support;

use Illuminate\Http\JsonResponse;
use Modules\Core\Results\Error;
use Modules\Tenant\Constants\TenantErrorCode;

final class TenantApiResponder
{
    public static function error(Error $error): JsonResponse
    {
        return response()->json([
            'message' => $error->message,
            'error' => [
                'code' => $error->code,
                'context' => $error->context === [] ? null : $error->context,
            ],
        ], self::status($error->code));
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

    private function __construct() {}
}
