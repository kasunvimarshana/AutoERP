<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Responses;

use Illuminate\Http\JsonResponse;
use Modules\Core\Results\Error;
use Modules\OrganizationUnit\Constants\OrganizationUnitErrorCode;

final class OrganizationUnitApiResponder
{
    public static function error(Error $error): JsonResponse
    {
        $status = match ($error->code) {
            OrganizationUnitErrorCode::NOT_FOUND,
            OrganizationUnitErrorCode::TENANT_NOT_FOUND => 404,
            OrganizationUnitErrorCode::CONFLICT,
            OrganizationUnitErrorCode::VERSION_CONFLICT => 409,
            OrganizationUnitErrorCode::PLAN_LIMIT_REACHED => 422,
            OrganizationUnitErrorCode::LIFECYCLE_BLOCKED,
            OrganizationUnitErrorCode::TENANT_MISMATCH,
            OrganizationUnitErrorCode::INVALID_VALUE => 422,
            default => 422,
        };

        return response()->json([
            'message' => $error->message,
            'code' => $error->code,
            'context' => $error->context,
        ], $status);
    }

    private function __construct() {}
}
