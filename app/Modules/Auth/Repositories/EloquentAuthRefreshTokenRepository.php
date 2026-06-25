<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Auth\Models\AuthRefreshTokenModel;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;

final class EloquentAuthRefreshTokenRepository extends EloquentRepository implements AuthRefreshTokenRepositoryInterface
{
    public function __construct(AuthRefreshTokenModel $model)
    {
        parent::__construct($model);
    }

    public function findActiveByRefreshKey(string $refreshKey): ?DataRecord
    {
        $query = $this->query()
            ->where('refresh_key', trim($refreshKey))
            ->where('status', 'active');

        $model = $query->first();

        return $model === null ? null : $this->toRecord($model);
    }

    public function rotateIfActive(int $id, int $rowVersion): bool
    {
        return $this->query()
            ->whereKey($id)
            ->where('row_version', $rowVersion)
            ->where('status', 'active')
            ->where('rotated', false)
            ->whereNull('revoked_at')
            ->update([
                'status' => 'revoked',
                'rotated' => true,
                'rotated_at' => now(),
                'revoked_at' => now(),
                'row_version' => $rowVersion + 1,
                'updated_at' => now(),
            ]) === 1;
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
