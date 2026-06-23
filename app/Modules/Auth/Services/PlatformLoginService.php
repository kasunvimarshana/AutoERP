<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Cache\RateLimiter;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Constants\AuthTokenScope;
use Modules\Auth\Contracts\Providers\TokenProviderInterface;
use Modules\Auth\DTOs\TokenIssueData;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\User\Repositories\UserRepositoryInterface;
use Throwable;

final class PlatformLoginService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordHasherInterface $passwords,
        private readonly TokenProviderInterface $tokens,
        private readonly RateLimiter $limiter,
        private readonly ErrorNormalizerInterface $errors,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    public function login(string $email, string $password, string $ipAddress): Result
    {
        return $this->executionContext->runAsControlPlane(
            fn (): Result => $this->loginAsPlatformOperator($email, $password, $ipAddress),
        );
    }

    private function loginAsPlatformOperator(string $email, string $password, string $ipAddress): Result
    {
        $email = strtolower(trim($email));
        $rateKey = 'platform-login:'.hash('sha256', $email.'|'.$ipAddress);
        $maxAttempts = max(1, (int) config('module-auth.max_login_attempts', 5));
        $decaySeconds = max(60, (int) config('module-auth.login_attempt_window_seconds', 900));

        if ($this->limiter->tooManyAttempts($rateKey, $maxAttempts)) {
            return Result::failure(new Error(
                AuthErrorCode::INVALID_CREDENTIALS,
                'Platform sign-in is temporarily unavailable. Try again later.',
            ));
        }

        try {
            $operator = $this->users->findActivePlatformOperatorCredentials($email);
            if (
                $operator === null
                || ! $this->passwords->verify($password, (string) $operator->get('password_hash', ''))
            ) {
                $this->limiter->hit($rateKey, $decaySeconds);

                return Result::failure(new Error(
                    AuthErrorCode::INVALID_CREDENTIALS,
                    'The platform credentials are invalid.',
                ));
            }

            $this->limiter->clear($rateKey);
            $tokenPair = $this->tokens->issue(TokenIssueData::fromArray([
                'tenant_id' => null,
                'organization_unit_id' => null,
                'user_id' => (int) $operator->id(),
                'token_scope' => AuthTokenScope::PLATFORM,
                'grant_type' => 'platform_password',
                'scopes' => ['platform'],
                'access_token_ttl_seconds' => (int) config('module-auth.access_token_ttl_seconds', 3600),
                'refresh_token_ttl_seconds' => (int) config('module-auth.refresh_token_ttl_seconds', 2592000),
                'metadata' => ['application_id' => 'platform'],
            ]));

            return Result::success([
                'tokens' => $tokenPair,
                'user' => [
                    'id' => (int) $operator->id(),
                    'first_name' => $operator->get('first_name'),
                    'last_name' => $operator->get('last_name'),
                    'email' => $operator->get('email'),
                    'is_platform_operator' => true,
                ],
                'tenant' => null,
                'organization_unit' => null,
                'roles' => ['Platform Operator'],
                'permissions' => [],
                'enabled_modules' => null,
                'is_platform_operator' => true,
            ]);
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize(
                $exception,
                AuthErrorCode::UNAUTHORIZED_ACCESS,
                ['operation' => 'platform.login'],
            ));
        }
    }
}
