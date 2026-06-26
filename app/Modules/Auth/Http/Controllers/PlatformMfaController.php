<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Http\Requests\ConfirmPlatformMfaEnrollmentRequest;
use Modules\Auth\Services\Mfa\PlatformMfaService;

final class PlatformMfaController extends Controller
{
    public function __construct(private readonly PlatformMfaService $mfa) {}

    public function confirm(ConfirmPlatformMfaEnrollmentRequest $request): JsonResponse
    {
        try {
            return $this->noStore(response()->json([
                'data' => $this->mfa->confirmEnrollment(
                    (string) $request->validated('enrollment_proof'),
                    (string) $request->validated('code'),
                ),
            ]));
        } catch (InvalidArgumentException $exception) {
            return $this->noStore(response()->json([
                'message' => $exception->getMessage(),
                'code' => AuthErrorCode::MFA_ENROLLMENT_FAILED,
            ], 422));
        }
    }

    private function noStore(JsonResponse $response): JsonResponse
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
