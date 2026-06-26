<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Enums\SessionStatus;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Auth\Models\AuthSessionModel;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;

final readonly class TenantSessionService
{
    public function __construct(
        private TenantExecutionContextInterface $executionContext,
        private ClockInterface $clock,
        private TokenService $tokens,
    ) {}

    /** @return list<array<string,mixed>> */
    public function listForUser(int $tenantId, int $userId): array
    {
        return $this->executionContext->runForTenant($tenantId, function () use ($tenantId, $userId): array {
            return AuthSessionModel::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->orderByDesc('last_activity_at')
                ->limit(100)
                ->get()
                ->map(fn (AuthSessionModel $session): array => $this->present($session))
                ->all();
        });
    }

    public function revokeByPublicId(int $tenantId, int $userId, string $publicId, string $reason): void
    {
        $sessionId = $this->executionContext->runForTenant($tenantId, function () use ($tenantId, $userId, $publicId): ?int {
            $session = AuthSessionModel::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('public_id', $publicId)
                ->first();

            return $session instanceof AuthSessionModel ? (int) $session->getKey() : null;
        });

        if ($sessionId === null) {
            throw new AuthFailure(AuthErrorCode::SESSION_NOT_FOUND, 'The session was not found.', 404);
        }

        $this->tokens->revokeTenantSession($tenantId, $sessionId, $userId, $reason);
    }

    /** @return array<string,mixed> */
    private function present(AuthSessionModel $session): array
    {
        $expiresAt = $session->getAttribute('expires_at');
        $status = (string) $session->getAttribute('status');
        if ($status === SessionStatus::ACTIVE->value
            && $expiresAt !== null
            && $this->clock->now()->getTimestamp() >= $expiresAt->getTimestamp()) {
            $status = SessionStatus::EXPIRED->value;
        }

        return [
            'id' => (string) $session->getAttribute('public_id'),
            'status' => $status,
            'organization_unit_id' => $session->getAttribute('organization_unit_id'),
            'device_name' => $session->getAttribute('device_name'),
            'ip_address' => $session->getAttribute('ip_address'),
            'user_agent' => $session->getAttribute('user_agent'),
            'authenticated_at' => $session->getAttribute('authenticated_at')?->format(DATE_ATOM),
            'last_activity_at' => $session->getAttribute('last_activity_at')?->format(DATE_ATOM),
            'expires_at' => $expiresAt?->format(DATE_ATOM),
            'revoked_at' => $session->getAttribute('revoked_at')?->format(DATE_ATOM),
        ];
    }
}
