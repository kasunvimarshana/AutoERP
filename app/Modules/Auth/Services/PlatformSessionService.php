<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Auth\Enums\SessionStatus;
use Modules\Auth\Models\AuthPlatformSessionModel;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Contracts\PlatformOperatorAuthenticationDirectoryInterface;
use Modules\User\Contracts\PlatformOperatorSessionRevokerInterface;

final readonly class PlatformSessionService implements PlatformOperatorSessionRevokerInterface
{
    public function __construct(
        private TenantExecutionContextInterface $executionContext,
        private PlatformOperatorAuthenticationDirectoryInterface $operators,
        private TokenService $tokens,
        private ClockInterface $clock,
    ) {}

    /** @return array{data:list<array<string,mixed>>,meta:array<string,int|null>} */
    public function page(?int $operatorId, int $page, int $perPage): array
    {
        return $this->executionContext->runAsControlPlane(function () use ($operatorId, $page, $perPage): array {
            $paginator = AuthPlatformSessionModel::query()
                ->when($operatorId !== null, static fn ($query) => $query->where('platform_operator_id', $operatorId))
                ->orderByDesc('last_activity_at')
                ->paginate(max(1, min(100, $perPage)), ['*'], 'page', max(1, $page));

            $operatorIds = [];
            foreach ($paginator->items() as $session) {
                $operatorIds[] = (int) $session->getAttribute('platform_operator_id');
            }
            $operatorSummaries = $this->operators->summariesByIds($operatorIds);

            $data = [];
            foreach ($paginator->items() as $session) {
                $data[] = $this->present(
                    $session,
                    $operatorSummaries[(int) $session->getAttribute('platform_operator_id')] ?? null,
                );
            }

            return [
                'data' => $data,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => max(1, $paginator->lastPage()),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ];
        });
    }

    public function revokeCurrent(int $sessionId, int $operatorId, string $reason): void
    {
        $this->tokens->revokePlatformSession($sessionId, $operatorId, $reason);
    }

    /** @return array<string,mixed> */
    public function revoke(string $publicId, string $reason): array
    {
        return $this->executionContext->runAsControlPlane(function () use ($publicId, $reason): array {
            $session = AuthPlatformSessionModel::query()->where('public_id', trim($publicId))->first();
            if (! $session instanceof AuthPlatformSessionModel) {
                throw (new ModelNotFoundException())->setModel(AuthPlatformSessionModel::class, [$publicId]);
            }
            $this->tokens->revokePlatformSession(
                (int) $session->getKey(),
                (int) $session->getAttribute('platform_operator_id'),
                $reason,
            );
            $fresh = $session->fresh() ?? $session;
            $summary = $this->operators->summariesByIds([(int) $session->getAttribute('platform_operator_id')]);
            return $this->present($fresh, $summary[(int) $session->getAttribute('platform_operator_id')] ?? null);
        });
    }

    public function revokeAllForOperator(int $operatorId, string $reason): int
    {
        if ($this->operators->summariesByIds([$operatorId]) === []) {
            throw (new ModelNotFoundException())->setModel('platform_operator', [$operatorId]);
        }

        return $this->executionContext->runAsControlPlane(function () use ($operatorId, $reason): int {
            $sessionIds = AuthPlatformSessionModel::query()
                ->where('platform_operator_id', $operatorId)
                ->where('status', SessionStatus::ACTIVE->value)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
            foreach ($sessionIds as $sessionId) {
                $this->tokens->revokePlatformSession($sessionId, $operatorId, $reason);
            }
            return count($sessionIds);
        });
    }

    /** @param array<string,mixed>|null $operator @return array<string,mixed> */
    private function present(AuthPlatformSessionModel $session, ?array $operator): array
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
            'operator' => $operator === null ? null : [
                'id' => $operator['id'],
                'name' => trim($operator['first_name'].' '.($operator['last_name'] ?? '')),
                'email' => $operator['email'],
                'status' => $operator['status'],
            ],
            'status' => $status,
            'ip_address' => $session->getAttribute('ip_address'),
            'device_name' => $session->getAttribute('device_name'),
            'user_agent' => $session->getAttribute('user_agent'),
            'authenticated_at' => $session->getAttribute('authenticated_at')?->format(DATE_ATOM),
            'mfa_verified_at' => $session->getAttribute('mfa_verified_at')?->format(DATE_ATOM),
            'last_activity_at' => $session->getAttribute('last_activity_at')?->format(DATE_ATOM),
            'expires_at' => $expiresAt?->format(DATE_ATOM),
            'revoked_at' => $session->getAttribute('revoked_at')?->format(DATE_ATOM),
        ];
    }
}
