<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Auth\Application\Repositories\AuthRefreshTokenRepositoryInterface;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\AuthRefreshTokenModel;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;

final class EloquentAuthRefreshTokenRepository extends EloquentRepository implements AuthRefreshTokenRepositoryInterface
{
    public function __construct(AuthRefreshTokenModel $model)
    {
        parent::__construct($model);
    }

    public function findActiveByRefreshKey(?int $tenantId, string $refreshKey): ?DataRecord
    {
        $query = $this->query()
            ->where('refresh_key', trim($refreshKey))
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
