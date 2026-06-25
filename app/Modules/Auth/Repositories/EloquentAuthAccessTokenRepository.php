<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Auth\Models\AuthAccessTokenModel;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;

final class EloquentAuthAccessTokenRepository extends EloquentRepository implements AuthAccessTokenRepositoryInterface
{
    public function __construct(AuthAccessTokenModel $model)
    {
        parent::__construct($model);
    }

    public function findActiveByTokenKey(string $tokenKey): ?DataRecord
    {
        $query = $this->query()
            ->where('token_key', trim($tokenKey))
            ->where('status', 'active');

        $model = $query->first();

        return $model === null ? null : $this->toRecord($model);
    }

    public function revokeBySessionId(int $sessionId, int $tenantId): void
    {
        $query = $this->query()
            ->where('session_id', $sessionId)
            ->whereNull('revoked_at');

        $query->where('tenant_id', $tenantId);

        $query->update([
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);
    }
}
