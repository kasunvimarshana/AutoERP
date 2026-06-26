<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Auth\Http\Requests\ConfirmPlatformMfaEnrollmentRequest;
use Modules\Auth\Http\Responses\AuthResponseFactory;
use Modules\Auth\Services\Mfa\PlatformMfaService;

final class PlatformMfaController extends Controller
{
    public function __construct(
        private readonly PlatformMfaService $mfa,
        private readonly AuthResponseFactory $responses,
    ) {}

    public function confirm(ConfirmPlatformMfaEnrollmentRequest $request): JsonResponse
    {
        try {
            return $this->responses->success([
                'data' => $this->mfa->confirmEnrollment(
                    (string) $request->validated('enrollment_proof'),
                    (string) $request->validated('code'),
                ),
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->responses->failure(new AuthFailure(
                AuthErrorCode::MFA_ENROLLMENT_FAILED,
                $exception->getMessage(),
                422,
                ['stage' => 'mfa_enrollment'],
            ));
        }
    }
}
