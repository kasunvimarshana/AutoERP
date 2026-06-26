<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\DTOs\ClientContext;
use Modules\Auth\Models\AuthLoginAttemptModel;
use Modules\Auth\Models\AuthPlatformLoginAttemptModel;
use Modules\Auth\Services\Security\OpaqueTokenCodec;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class LoginAttemptRecorder
{
    public function __construct(
        private ClockInterface $clock,
        private TenantExecutionContextInterface $executionContext,
        private OpaqueTokenCodec $tokens,
        private LoggerInterface $logger,
    ) {}

    public function recordTenant(
        int $tenantId,
        ?int $userId,
        string $identifier,
        bool $successful,
        ?string $failureCode,
        ClientContext $client,
    ): void {
        $this->executionContext->runForTenant($tenantId, fn () => AuthLoginAttemptModel::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'login_identifier_hash' => $this->identifierDigest($identifier),
            'was_successful' => $successful,
            'failure_code' => $failureCode,
            'ip_address' => $client->ipAddress,
            'user_agent' => $client->userAgent,
            'attempted_at' => $this->clock->now(),
        ]));
    }

    public function recordPlatform(
        ?int $operatorId,
        string $identifier,
        bool $successful,
        ?string $failureCode,
        ClientContext $client,
    ): void {
        $this->executionContext->runAsControlPlane(fn () => AuthPlatformLoginAttemptModel::query()->create([
            'platform_operator_id' => $operatorId,
            'login_identifier_hash' => $this->identifierDigest($identifier),
            'was_successful' => $successful,
            'failure_code' => $failureCode,
            'ip_address' => $client->ipAddress,
            'user_agent' => $client->userAgent,
            'attempted_at' => $this->clock->now(),
        ]));
    }

    public function recordTenantFailureBestEffort(
        int $tenantId,
        ?int $userId,
        string $identifier,
        string $failureCode,
        ClientContext $client,
    ): void {
        try {
            $this->recordTenant($tenantId, $userId, $identifier, false, $failureCode, $client);
        } catch (Throwable $exception) {
            $this->logDegradedAudit('tenant', $failureCode, $exception);
        }
    }

    public function recordPlatformFailureBestEffort(
        ?int $operatorId,
        string $identifier,
        string $failureCode,
        ClientContext $client,
    ): void {
        try {
            $this->recordPlatform($operatorId, $identifier, false, $failureCode, $client);
        } catch (Throwable $exception) {
            $this->logDegradedAudit('platform', $failureCode, $exception);
        }
    }

    private function identifierDigest(string $identifier): string
    {
        return $this->tokens->digestArbitrary(mb_strtolower(trim($identifier)), 'auth-login-identifier');
    }

    private function logDegradedAudit(string $realm, string $failureCode, Throwable $exception): void
    {
        $correlationId = app()->bound('request')
            ? request()->attributes->get('correlation_id')
            : null;

        $this->logger->critical('Auth login-attempt audit persistence failed.', [
            'realm' => $realm,
            'failure_code' => $failureCode,
            'security_audit_degraded' => true,
            'correlation_id' => is_string($correlationId) ? $correlationId : null,
            'exception' => $exception,
        ]);
    }
}
