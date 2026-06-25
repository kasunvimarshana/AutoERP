<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Auth\Models\AuthPlatformRefreshTokenModel;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;

final class EloquentAuthPlatformRefreshTokenRepository extends EloquentRepository implements AuthPlatformRefreshTokenRepositoryInterface
{
    public function __construct(AuthPlatformRefreshTokenModel $model)
    {
        parent::__construct($model);
    }

    public function findActiveByRefreshKey(string $refreshKey): ?DataRecord
    {
        $model = $this->query()
            ->where('refresh_key', trim($refreshKey))
            ->where('status', 'active')
            ->first();

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

    public function revokeByPlatformSessionId(int $platformSessionId): void
    {
        $this->query()
            ->where('platform_session_id', $platformSessionId)
            ->whereNull('revoked_at')
            ->update([
                'status' => 'revoked',
                'revoked_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
