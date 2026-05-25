<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Auth\Application\Repositories\AuthAccessTokenRepositoryInterface;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\AuthAccessTokenModel;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;

final class EloquentAuthAccessTokenRepository extends EloquentRepository implements AuthAccessTokenRepositoryInterface
{
    public function __construct(AuthAccessTokenModel $model)
    {
        parent::__construct($model);
    }

    public function findActiveByTokenKey(?int $tenantId, string $tokenKey): ?DataRecord
    {
        $query = $this->query()
            ->where('token_key', trim($tokenKey))
            ->where('status', 'active');

        if ($tenantId === null) {
            $query->whereNull('tenant_id');
        } else {
            $query->where('tenant_id', $tenantId);
        }

        $model = $query->first();

        return $model === null ? null : $this->toRecord($model);
    }

    public function revokeBySessionId(int $sessionId, ?int $tenantId = null): void
    {
        $query = $this->query()
            ->where('session_id', $sessionId)
            ->whereNull('revoked_at');

        if ($tenantId === null) {
            $query->whereNull('tenant_id');
        } else {
            $query->where('tenant_id', $tenantId);
        }

        $query->update([
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);
    }
}
