<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Http\Requests\ConfirmPlatformMfaEnrollmentRequest;
use Modules\Auth\Http\Requests\PlatformMfaEnrollmentRequest;
use Modules\Auth\Services\Mfa\PlatformMfaService;
use Modules\Core\Http\Responses\ApiErrorResponseFactory;
use Modules\Core\Results\Result;

final class PlatformMfaController extends Controller
{
    public function __construct(
        private readonly PlatformMfaService $mfa,
        private readonly ApiErrorResponseFactory $errors,
    ) {}

    public function start(PlatformMfaEnrollmentRequest $request): JsonResponse
    {
        return $this->respond($this->mfa->startEnrollment(
            (string) $request->validated('email'),
            (string) $request->validated('password'),
            (string) $request->ip(),
        ));
    }

    public function confirm(ConfirmPlatformMfaEnrollmentRequest $request): JsonResponse
    {
        return $this->respond($this->mfa->confirmEnrollment(
            (string) $request->validated('email'),
            (string) $request->validated('password'),
            (string) $request->validated('code'),
            (string) $request->ip(),
        ));
    }

    private function respond(Result $result): JsonResponse
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();

            return $this->errors->make($error->code, $error->message, 401, 'authentication', $error->context);
        }

        return response()->json($result->valueOrFail());
    }
}
