<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Auth\Application\Contracts\UseCases\AuthorizeClientServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\ExchangeAuthorizationCodeServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\IssueTokenServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\ListSessionsServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\LoginServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\LogoutServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\RefreshTokenServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\RegisterServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\RequestVerificationChallengeServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\RevokeSessionServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\ValidateTokenServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\VerifyChallengeServiceInterface;
use Modules\Auth\Application\DTOs\AuthorizeClientData;
use Modules\Auth\Application\DTOs\ExchangeAuthorizationCodeData;
use Modules\Auth\Application\DTOs\LoginData;
use Modules\Auth\Application\DTOs\LogoutData;
use Modules\Auth\Application\DTOs\RegistrationData;
use Modules\Auth\Application\DTOs\TokenIssueData;
use Modules\Auth\Application\DTOs\TokenRefreshData;
use Modules\Auth\Application\DTOs\VerificationChallengeRequestData;
use Modules\Auth\Application\DTOs\VerificationChallengeVerifyData;
use Modules\Auth\Presentation\Http\Requests\AuthorizeClientRequest;
use Modules\Auth\Presentation\Http\Requests\ExchangeAuthorizationCodeRequest;
use Modules\Auth\Presentation\Http\Requests\IssueTokenRequest;
use Modules\Auth\Presentation\Http\Requests\ListSessionsRequest;
use Modules\Auth\Presentation\Http\Requests\LoginRequest;
use Modules\Auth\Presentation\Http\Requests\LogoutRequest;
use Modules\Auth\Presentation\Http\Requests\RefreshTokenRequest;
use Modules\Auth\Presentation\Http\Requests\RegisterRequest;
use Modules\Auth\Presentation\Http\Requests\RequestVerificationChallengeRequest;
use Modules\Auth\Presentation\Http\Requests\RevokeSessionRequest;
use Modules\Auth\Presentation\Http\Requests\ValidateTokenRequest;
use Modules\Auth\Presentation\Http\Requests\VerifyChallengeRequest;
use Modules\Auth\Presentation\Http\Resources\AuthPayloadResource;
use Modules\Core\Application\Results\Result;

final class AuthController extends Controller
{
    public function __construct(
        private readonly LoginServiceInterface $loginService,
        private readonly LogoutServiceInterface $logoutService,
        private readonly RegisterServiceInterface $registerService,
        private readonly IssueTokenServiceInterface $issueTokenService,
        private readonly RefreshTokenServiceInterface $refreshTokenService,
        private readonly RevokeSessionServiceInterface $revokeSessionService,
        private readonly ListSessionsServiceInterface $listSessionsService,
        private readonly ValidateTokenServiceInterface $validateTokenService,
        private readonly RequestVerificationChallengeServiceInterface $requestVerificationService,
        private readonly VerifyChallengeServiceInterface $verifyChallengeService,
        private readonly AuthorizeClientServiceInterface $authorizeClientService,
        private readonly ExchangeAuthorizationCodeServiceInterface $exchangeAuthorizationCodeService,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {
    }

    public function login(LoginRequest $request): JsonResponse|AuthPayloadResource
    {
        $result = $this->loginService->login(LoginData::fromArray($request->validated()));

        return $this->respond($result);
    }

    public function register(RegisterRequest $request): JsonResponse|AuthPayloadResource
    {
        $result = $this->registerService->register(RegistrationData::fromArray($request->validated()));

        return $this->respond($result, 201);
    }

    public function issueToken(IssueTokenRequest $request): JsonResponse|AuthPayloadResource
    {
        $result = $this->issueTokenService->issueToken(
            TokenIssueData::fromArray($this->mergeProtectedContext($request->validated())),
        );

        return $this->respond($result, 201);
    }

    public function refreshToken(RefreshTokenRequest $request): JsonResponse|AuthPayloadResource
    {
        $result = $this->refreshTokenService->refreshToken(TokenRefreshData::fromArray($request->validated()));

        return $this->respond($result);
    }

    public function logout(LogoutRequest $request): JsonResponse|AuthPayloadResource
    {
        $result = $this->logoutService->logout(
            LogoutData::fromArray($this->mergeProtectedContext($request->validated())),
        );

        return $this->respond($result);
    }

    public function revokeSession(RevokeSessionRequest $request, int|string $session): JsonResponse|AuthPayloadResource
    {
        $payload = $this->mergeProtectedContext($request->validated());
        $result = $this->revokeSessionService->revokeSession(
            $session,
            isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null,
        );

        return $this->respond($result);
    }

    public function listSessions(ListSessionsRequest $request): JsonResponse|AuthPayloadResource
    {
        $validated = $this->mergeProtectedContext($request->validated());
        $result = $this->listSessionsService->listSessions(
            (int) $validated['user_id'],
            isset($validated['tenant_id']) ? (int) $validated['tenant_id'] : null,
        );

        return $this->respond($result);
    }

    public function validateToken(ValidateTokenRequest $request): JsonResponse|AuthPayloadResource
    {
        $validated = $request->validated();
        $result = $this->validateTokenService->validateToken(
            (string) $validated['access_token'],
            isset($validated['tenant_id']) ? (int) $validated['tenant_id'] : null,
        );

        return $this->respond($result);
    }

    public function requestVerificationChallenge(
        RequestVerificationChallengeRequest $request,
    ): JsonResponse|AuthPayloadResource {
        $result = $this->requestVerificationService
            ->requestVerificationChallenge(VerificationChallengeRequestData::fromArray($request->validated()));

        return $this->respond($result, 201);
    }

    public function verifyChallenge(VerifyChallengeRequest $request): JsonResponse|AuthPayloadResource
    {
        $result = $this->verifyChallengeService
            ->verifyChallenge(VerificationChallengeVerifyData::fromArray($request->validated()));

        return $this->respond($result);
    }

    public function authorizeClient(AuthorizeClientRequest $request): JsonResponse|AuthPayloadResource
    {
        $result = $this->authorizeClientService->authorizeClient(
            AuthorizeClientData::fromArray($this->mergeProtectedContext($request->validated())),
        );

        return $this->respond($result, 201);
    }

    public function exchangeAuthorizationCode(
        ExchangeAuthorizationCodeRequest $request,
    ): JsonResponse|AuthPayloadResource {
        $result = $this->exchangeAuthorizationCodeService->exchangeAuthorizationCode(
            ExchangeAuthorizationCodeData::fromArray($request->validated()),
        );

        return $this->respond($result, 201);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function mergeProtectedContext(array $payload): array
    {
        $context = $this->currentUser->requireCurrent();

        $payload['user_id'] = $context->userIdAsInt();

        if ($context->tenantId() !== null) {
            $payload['tenant_id'] = $context->tenantId();
        }

        if ($context->organizationUnitId() !== null) {
            $payload['organization_unit_id'] = $context->organizationUnitId();
        }

        return $payload;
    }

    private function respond(Result $result, int $successStatus = 200): JsonResponse|AuthPayloadResource
    {
        if ($result->isFailure()) {
            return response()->json([
                'message' => $result->errorOrFail()->message,
                'code' => $result->errorOrFail()->code,
            ], 422);
        }

        return (new AuthPayloadResource($result->valueOrFail()))->response()->setStatusCode($successStatus);
    }
}
