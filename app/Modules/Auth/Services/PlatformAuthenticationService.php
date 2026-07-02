<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\DTOs\ClientContext;
use Modules\Auth\Enums\SessionStatus;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Auth\Models\AuthPlatformSessionModel;
use Modules\Auth\Services\Credentials\PasswordCredentialService;
use Modules\Auth\Services\Security\AccountLoginThrottle;
use Modules\Auth\Services\Security\AuthSecurityConfig;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Contracts\PlatformOperatorAuthenticationDirectoryInterface;
use Throwable;

final readonly class PlatformAuthenticationService
{
    private const REALM = 'platform';

    public function __construct(
        private DatabaseManager $database,
        private ClockInterface $clock,
        private TenantExecutionContextInterface $executionContext,
        private PlatformOperatorAuthenticationDirectoryInterface $operators,
        private PasswordCredentialService $credentials,
        private AccountLoginThrottle $throttle,
        private PlatformTokenService $tokens,
        private PlatformAuthProfileBuilder $profiles,
        private LoginAttemptRecorder $attempts,
        private AuthSecurityConfig $config,
    ) {}

    /** @return array<string,mixed> */
    public function login(
        string $email,
        string $password,
        ClientContext $client,
    ): array {
        $email = mb_strtolower(trim($email));

        if ($email === '' || $this->throttle->isBlocked(self::REALM, $email, $client->ipAddress)) {
            $this->credentials->verifyDummy($password);
            $this->attempts->recordPlatformFailureBestEffort(
                null,
                $email,
                AuthErrorCode::RATE_LIMITED,
                $client,
            );
            throw $this->invalidCredentials();
        }

        $operator = $this->operators->findPlatformForLogin($email);
        $validOperator = $operator !== null
            && (string) ($operator['status'] ?? '') === 'active'
            && (bool) ($operator['credentials_ready'] ?? false);
        $passwordValid = $validOperator
            ? $this->credentials->verifyPlatformOperator((int) $operator['id'], $password)
            : false;

        if (! $validOperator || ! $passwordValid) {
            if (! $validOperator) {
                $this->credentials->verifyDummy($password);
            }
            $this->throttle->recordFailure(self::REALM, $email, $client->ipAddress);
            $this->attempts->recordPlatformFailureBestEffort(
                $operator === null ? null : (int) $operator['id'],
                $email,
                AuthErrorCode::INVALID_CREDENTIALS,
                $client,
            );
            throw $this->invalidCredentials();
        }

        $operatorId = (int) $operator['id'];

        try {
            $payload = $this->executionContext->runAsControlPlane(fn (): array => $this->database->transaction(
                function () use ($operatorId, $email, $client): array {
                    $now = $this->clock->now();
                    $session = AuthPlatformSessionModel::query()->create([
                        'public_id' => (string) Str::uuid(),
                        'platform_operator_id' => $operatorId,
                        'status' => SessionStatus::ACTIVE->value,
                        'ip_address' => $client->ipAddress,
                        'user_agent' => $client->userAgent,
                        'device_name' => $client->deviceName,
                        'authenticated_at' => $now,
                        'last_activity_at' => $now,
                        'expires_at' => $now->modify('+'.$this->config->platformSessionTtlSeconds.' seconds'),
                        'row_version' => 1,
                    ]);

                    $tokens = $this->tokens->issueSessionTokens((int) $session->getKey(), $operatorId);
                    $profile = $this->profiles->build(['platform_operator_id' => $operatorId]);
                    $this->attempts->recordPlatform(
                        $operatorId,
                        $email,
                        true,
                        null,
                        $client,
                    );

                    return array_merge([
                        'tokens' => $tokens,
                        'session' => [
                            'id' => (string) $session->getAttribute('public_id'),
                            'expires_at' => $session->getAttribute('expires_at')?->format(DATE_ATOM),
                        ],
                    ], $profile);
                },
                3,
            ));
        } catch (AuthFailure $exception) {
            $this->attempts->recordPlatformFailureBestEffort(
                $operatorId,
                $email,
                $exception->errorCode,
                $client,
            );
            throw $exception;
        } catch (Throwable $exception) {
            $this->attempts->recordPlatformFailureBestEffort(
                $operatorId,
                $email,
                AuthErrorCode::INFRASTRUCTURE_FAILURE,
                $client,
            );
            throw $exception;
        }

        $this->throttle->clearSuccessful(self::REALM, $email, $client->ipAddress);

        return $payload;
    }

    private function invalidCredentials(): AuthFailure
    {
        return new AuthFailure(
            AuthErrorCode::INVALID_CREDENTIALS,
            'The credentials are invalid.',
            401,
            ['stage' => 'credentials'],
        );
    }
}
