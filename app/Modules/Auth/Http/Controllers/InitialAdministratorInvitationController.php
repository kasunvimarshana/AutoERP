<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Http\Requests\AcceptInitialAdministratorInvitationRequest;
use Modules\Auth\Http\Requests\InspectInitialAdministratorInvitationRequest;
use Modules\Auth\Services\Registration\InitialAdministratorRegistrationService;
use Modules\Core\Http\Responses\ApiErrorResponseFactory;
use Modules\Core\Results\Result;

final class InitialAdministratorInvitationController extends Controller
{
    public function __construct(
        private readonly InitialAdministratorRegistrationService $registration,
        private readonly ApiErrorResponseFactory $errors,
    ) {}

    public function inspect(InspectInitialAdministratorInvitationRequest $request): JsonResponse
    {
        return $this->respond($this->registration->inspect((string) $request->validated('token')));
    }

    public function accept(AcceptInitialAdministratorInvitationRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $result = $this->registration->register(
            (string) $payload['token'],
            (string) $payload['first_name'],
            isset($payload['last_name']) ? (string) $payload['last_name'] : null,
            (string) $payload['password'],
        );

        return $this->respond($result, 201);
    }

    private function respond(Result $result, int $successStatus = 200): JsonResponse
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();

            return $this->noStore($this->errors->make(
                $error->code,
                $error->message,
                $error->code === AuthErrorCode::INVITATION_INVALID ? 404 : 422,
                $error->code === AuthErrorCode::INVITATION_INVALID ? 'not_found' : 'domain',
            ));
        }

        return $this->noStore(response()->json(['data' => $result->valueOrFail()], $successStatus));
    }

    private function noStore(JsonResponse $response): JsonResponse
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}

