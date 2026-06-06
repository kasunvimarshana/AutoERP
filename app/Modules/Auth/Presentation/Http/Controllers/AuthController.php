<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Application\DTOs\AuthorizeClientData;
use Modules\Auth\Application\DTOs\ExchangeAuthorizationCodeData;
use Modules\Auth\Application\DTOs\LinkExternalIdentityData;
use Modules\Auth\Application\DTOs\LoginData;
use Modules\Auth\Application\DTOs\LogoutData;
use Modules\Auth\Application\DTOs\RegistrationData;
use Modules\Auth\Application\DTOs\TokenIssueData;
use Modules\Auth\Application\DTOs\TokenRefreshData;
use Modules\Auth\Application\DTOs\UnlinkExternalIdentityData;
use Modules\Auth\Application\DTOs\VerificationChallengeRequestData;
use Modules\Auth\Application\DTOs\VerificationChallengeVerifyData;
use Modules\Auth\Application\UseCases\AuthorizeClientService;
use Modules\Auth\Application\UseCases\ExchangeAuthorizationCodeService;
use Modules\Auth\Application\UseCases\GetCurrentAuthProfileService;
use Modules\Auth\Application\UseCases\IssueTokenService;
use Modules\Auth\Application\UseCases\LinkExternalIdentityService;
use Modules\Auth\Application\UseCases\ListSessionsService;
use Modules\Auth\Application\UseCases\LoginService;
use Modules\Auth\Application\UseCases\LogoutService;
use Modules\Auth\Application\UseCases\RefreshTokenService;
use Modules\Auth\Application\UseCases\RegisterService;
use Modules\Auth\Application\UseCases\RequestVerificationChallengeService;
use Modules\Auth\Application\UseCases\RevokeSessionService;
use Modules\Auth\Application\UseCases\UnlinkExternalIdentityService;
use Modules\Auth\Application\UseCases\ValidateTokenService;
use Modules\Auth\Application\UseCases\VerifyChallengeService;
use Modules\Auth\Presentation\Http\Requests\AuthorizeClientRequest;
use Modules\Auth\Presentation\Http\Requests\ExchangeAuthorizationCodeRequest;
use Modules\Auth\Presentation\Http\Requests\IssueTokenRequest;
use Modules\Auth\Presentation\Http\Requests\LinkExternalIdentityRequest;
use Modules\Auth\Presentation\Http\Requests\ListSessionsRequest;
use Modules\Auth\Presentation\Http\Requests\LoginRequest;
use Modules\Auth\Presentation\Http\Requests\LogoutRequest;
use Modules\Auth\Presentation\Http\Requests\RefreshTokenRequest;
use Modules\Auth\Presentation\Http\Requests\RegisterRequest;
use Modules\Auth\Presentation\Http\Requests\RequestVerificationChallengeRequest;
use Modules\Auth\Presentation\Http\Requests\RevokeSessionRequest;
use Modules\Auth\Presentation\Http\Requests\UnlinkExternalIdentityRequest;
use Modules\Auth\Presentation\Http\Requests\ValidateTokenRequest;
use Modules\Auth\Presentation\Http\Requests\VerifyChallengeRequest;
use Modules\Auth\Presentation\Http\Resources\AuthPayloadResource;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Application\Results\Result;

final class AuthController extends Controller
{
    public function __construct(
        private readonly LoginService $loginService,
        private readonly LogoutService $logoutService,
        private readonly RegisterService $registerService,
        private readonly IssueTokenService $issueTokenService,
        private readonly LinkExternalIdentityService $linkExternalIdentityService,
        private readonly UnlinkExternalIdentityService $unlinkExternalIdentityService,
        private readonly RefreshTokenService $refreshTokenService,
        private readonly RevokeSessionService $revokeSessionService,
        private readonly ListSessionsService $listSessionsService,
        private readonly ValidateTokenService $validateTokenService,
        private readonly RequestVerificationChallengeService $requestVerificationService,
        private readonly VerifyChallengeService $verifyChallengeService,
        private readonly AuthorizeClientService $authorizeClientService,
        private readonly ExchangeAuthorizationCodeService $exchangeAuthorizationCodeService,
        private readonly GetCurrentAuthProfileService $currentAuthProfileService,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
    ) {}

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

    public function linkExternalIdentity(LinkExternalIdentityRequest $request): JsonResponse|AuthPayloadResource
    {
        $result = $this->linkExternalIdentityService->linkExternalIdentity(
            LinkExternalIdentityData::fromArray($this->mergeProtectedContext($request->validated())),
        );

        return $this->respond($result, 201);
    }

    public function unlinkExternalIdentity(UnlinkExternalIdentityRequest $request): JsonResponse|AuthPayloadResource
    {
        $result = $this->unlinkExternalIdentityService->unlinkExternalIdentity(
            UnlinkExternalIdentityData::fromArray($this->mergeProtectedContext($request->validated())),
        );

        return $this->respond($result);
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

    public function me(): JsonResponse|AuthPayloadResource
    {
        $context = $this->currentUser->current();
        if ($context === null) {
            return response()->json([
                'message' => 'Authenticated user context is not available.',
                'code' => 'AUTH_UNAUTHORIZED_ACCESS',
            ], 401);
        }

        $result = $this->currentAuthProfileService->getProfile(
            $context->userIdAsInt(),
            $this->currentTenant->currentTenantId() ?? $context->tenantId(),
            $this->currentOrganizationUnit->currentOrganizationUnitId() ?? $context->organizationUnitId(),
            $context->guard(),
            $context->provider(),
            $context->applicationId(),
            $context->tokenPayload(),
        );

        return $this->respond($result);
    }

    public function ssoCallback(
        ExchangeAuthorizationCodeRequest $request,
    ): JsonResponse|AuthPayloadResource {
        return $this->exchangeAuthorizationCode($request);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mergeProtectedContext(array $payload): array
    {
        $context = $this->currentUser->requireCurrent();

        $payload['user_id'] = $context->userIdAsInt();

        $tenantId = $this->currentTenant->currentTenantId() ?? $context->tenantId();
        if ($tenantId !== null) {
            $payload['tenant_id'] = $tenantId;
        }

        $organizationUnitId = $this->currentOrganizationUnit->currentOrganizationUnitId()
            ?? $context->organizationUnitId();
        if ($organizationUnitId !== null) {
            $payload['organization_unit_id'] = $organizationUnitId;
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
