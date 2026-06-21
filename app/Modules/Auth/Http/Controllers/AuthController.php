<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\DTOs\AuthorizeClientData;
use Modules\Auth\DTOs\ExchangeAuthorizationCodeData;
use Modules\Auth\DTOs\LinkExternalIdentityData;
use Modules\Auth\DTOs\LoginData;
use Modules\Auth\DTOs\LogoutData;
use Modules\Auth\DTOs\RegistrationData;
use Modules\Auth\DTOs\TokenIssueData;
use Modules\Auth\DTOs\TokenRefreshData;
use Modules\Auth\DTOs\UnlinkExternalIdentityData;
use Modules\Auth\DTOs\VerificationChallengeRequestData;
use Modules\Auth\DTOs\VerificationChallengeVerifyData;
use Modules\Auth\Http\Requests\AuthorizeClientRequest;
use Modules\Auth\Http\Requests\ExchangeAuthorizationCodeRequest;
use Modules\Auth\Http\Requests\IssueTokenRequest;
use Modules\Auth\Http\Requests\LinkExternalIdentityRequest;
use Modules\Auth\Http\Requests\ListSessionsRequest;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\LogoutRequest;
use Modules\Auth\Http\Requests\RefreshTokenRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Requests\RequestVerificationChallengeRequest;
use Modules\Auth\Http\Requests\RevokeSessionRequest;
use Modules\Auth\Http\Requests\UnlinkExternalIdentityRequest;
use Modules\Auth\Http\Requests\ValidateTokenRequest;
use Modules\Auth\Http\Requests\VerifyChallengeRequest;
use Modules\Auth\Http\Resources\AuthPayloadResource;
use Modules\Auth\Services\AuthorizeClientService;
use Modules\Auth\Services\ExchangeAuthorizationCodeService;
use Modules\Auth\Services\GetCurrentAuthProfileService;
use Modules\Auth\Services\IssueTokenService;
use Modules\Auth\Services\LinkExternalIdentityService;
use Modules\Auth\Services\ListSessionsService;
use Modules\Auth\Services\LoginService;
use Modules\Auth\Services\LogoutService;
use Modules\Auth\Services\RefreshTokenService;
use Modules\Auth\Services\RegisterService;
use Modules\Auth\Services\RequestVerificationChallengeService;
use Modules\Auth\Services\RevokeSessionService;
use Modules\Auth\Services\UnlinkExternalIdentityService;
use Modules\Auth\Services\ValidateTokenService;
use Modules\Auth\Services\VerifyChallengeService;
use Modules\Core\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentTenantContextResolverInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Exceptions\CurrentTenantContextResolutionException;
use Modules\Core\Http\Responses\ApiErrorResponseFactory;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\OrganizationUnit\Repositories\OrganizationUnitRepositoryInterface;

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
        private readonly ApiErrorResponseFactory $errorResponses,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly CurrentTenantContextResolverInterface $tenantResolver,
        private readonly OrganizationUnitRepositoryInterface $organizationUnits,
    ) {}

    public function login(LoginRequest $request): JsonResponse|AuthPayloadResource
    {
        $payload = $this->resolveLoginContext($request);
        if ($payload instanceof Result) {
            return $this->respond($payload);
        }

        $result = $this->loginService->login(LoginData::fromArray($payload));

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
        $payload = $this->mergeProtectedContext($request->validated());
        $context = $this->currentUser->requireCurrent();
        $tokenPayload = $context->tokenPayload();
        $payload['access_token'] ??= $request->bearerToken();
        if (! isset($payload['session_id']) && isset($tokenPayload['session_id'])) {
            $payload['session_id'] = $tokenPayload['session_id'];
        }

        $result = $this->logoutService->logout(
            LogoutData::fromArray($payload),
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
            return $this->errorResponses->make(
                AuthErrorCode::UNAUTHORIZED_ACCESS,
                'Authenticated user context is not available.',
                401,
                'authentication',
            );
        }

        $result = $this->currentAuthProfileService->getProfile(
            $context->userId(),
            $this->currentTenant->currentTenantId(),
            $this->currentOrganizationUnit->currentOrganizationUnitId(),
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

        $payload['user_id'] = $context->userId();

        $tenantId = $this->currentTenant->currentTenantId();
        if ($tenantId !== null) {
            $payload['tenant_id'] = $tenantId;
        }

        $organizationUnitId = $this->currentOrganizationUnit->currentOrganizationUnitId();
        if ($organizationUnitId !== null) {
            $payload['organization_unit_id'] = $organizationUnitId;
        }

        return $payload;
    }

    private function respond(Result $result, int $successStatus = 200): JsonResponse|AuthPayloadResource
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $this->statusForErrorCode($error->code);
            $type = $this->typeForStatus($status);

            return $this->errorResponses->make(
                $error->code,
                $error->message,
                $status,
                $type,
            );
        }

        return response()->json(
            (new AuthPayloadResource($result->valueOrFail()))->resolve(request()),
            $successStatus,
        );
    }

    private function statusForErrorCode(string $code): int
    {
        return match ($code) {
            AuthErrorCode::TENANT_RESOLUTION_FAILED,
            AuthErrorCode::ORGANIZATION_UNIT_RESOLUTION_FAILED => 400,
            AuthErrorCode::INVALID_CREDENTIALS,
            AuthErrorCode::PROVIDER_NOT_FOUND,
            AuthErrorCode::TOKEN_INVALID,
            AuthErrorCode::TOKEN_EXPIRED,
            AuthErrorCode::TOKEN_REVOKED,
            AuthErrorCode::UNAUTHORIZED_ACCESS,
            AuthErrorCode::AUTHORIZATION_CODE_INVALID => 401,
            AuthErrorCode::USER_INACTIVE,
            AuthErrorCode::PROVIDER_DISABLED,
            AuthErrorCode::TENANT_MISMATCH,
            AuthErrorCode::CLIENT_NOT_ALLOWED => 403,
            default => 422,
        };
    }

    private function typeForStatus(int $status): string
    {
        return match ($status) {
            401 => 'authentication',
            403 => 'authorization',
            404 => 'not_found',
            409 => 'conflict',
            default => $status >= 500 ? 'infrastructure' : 'domain',
        };
    }

    /**
     * @return array<string,mixed>|Result
     */
    private function resolveLoginContext(LoginRequest $request): array|Result
    {
        $payload = $request->validated();

        try {
            $tenantContext = $this->tenantResolver->resolve($request);
        } catch (CurrentTenantContextResolutionException) {
            return $this->tenantResolutionFailure();
        }

        if ($tenantContext === null) {
            return $this->tenantResolutionFailure();
        }

        $tenantId = $tenantContext->tenantId();
        $payload['tenant_id'] = $tenantId;

        $organizationUnitId = $this->resolveLoginOrganizationUnitId(
            $payload['organization_unit_id'] ?? null,
            $tenantId,
        );

        if ($organizationUnitId instanceof Result) {
            return $organizationUnitId;
        }

        if ($organizationUnitId !== null) {
            $payload['organization_unit_id'] = $organizationUnitId;
        }

        return $payload;
    }

    private function resolveLoginOrganizationUnitId(mixed $requestedOrganizationUnitId, int $tenantId): int|Result|null
    {
        $organizationUnitId = $this->toNullableInt($requestedOrganizationUnitId);
        if ($organizationUnitId !== null) {
            $organizationUnit = $this->organizationUnits->findById($organizationUnitId);
            if (! $organizationUnit instanceof DataRecord
                || $this->toNullableInt($organizationUnit->get('tenant_id')) !== $tenantId
            ) {
                return Result::failure(new Error(
                    AuthErrorCode::ORGANIZATION_UNIT_RESOLUTION_FAILED,
                    'Organization unit could not be resolved for this tenant.',
                ));
            }

            return $organizationUnitId;
        }

        foreach ($this->organizationUnits->listByTenant($tenantId) as $organizationUnit) {
            if ($this->truthy($organizationUnit->get('is_default'))) {
                return (int) $organizationUnit->id();
            }
        }

        foreach ($this->organizationUnits->listByTenant($tenantId) as $organizationUnit) {
            if ($this->truthy($organizationUnit->get('is_active'))) {
                return (int) $organizationUnit->id();
            }
        }

        return null;
    }

    private function tenantResolutionFailure(): Result
    {
        return Result::failure(new Error(
            AuthErrorCode::TENANT_RESOLUTION_FAILED,
            'Tenant could not be resolved for this domain.',
        ));
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private function truthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}
