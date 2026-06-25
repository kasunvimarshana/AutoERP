<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Auth\Models\AuthPlatformAccessTokenModel;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;

final class EloquentAuthPlatformAccessTokenRepository extends EloquentRepository implements AuthPlatformAccessTokenRepositoryInterface
{
    public function __construct(AuthPlatformAccessTokenModel $model)
    {
        parent::__construct($model);
    }

    public function findActiveByTokenKey(string $tokenKey): ?DataRecord
    {
        $model = $this->query()
            ->where('token_key', trim($tokenKey))
            ->where('status', 'active')
            ->first();

        return $model === null ? null : $this->toRecord($model);
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
