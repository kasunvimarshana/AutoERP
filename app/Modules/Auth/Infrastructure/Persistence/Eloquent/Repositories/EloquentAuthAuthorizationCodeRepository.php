<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Auth\Application\Repositories\AuthAuthorizationCodeRepositoryInterface as AuthCodeRepo;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\AuthAuthorizationCodeModel;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;

final class EloquentAuthAuthorizationCodeRepository extends EloquentRepository implements AuthCodeRepo
{
    public function __construct(AuthAuthorizationCodeModel $model)
    {
        parent::__construct($model);
    }

    public function findActiveByCodeKey(?int $tenantId, string $codeKey): ?DataRecord
    {
        $query = $this->query()
            ->where('code_key', trim($codeKey))
            ->where('status', 'pending')
            ->whereNull('consumed_at')
            ->whereNull('revoked_at');

        if ($tenantId === null) {
            $query->whereNull('tenant_id');
        } else {
            $query->where('tenant_id', $tenantId);
        }

        $model = $query->first();

        return $model === null ? null : $this->toRecord($model);
    }

    public function consume(int $codeId, int $expectedRowVersion): bool
    {
        $updated = $this->query()
            ->where('id', $codeId)
            ->where('row_version', $expectedRowVersion)
            ->where('status', 'pending')
            ->whereNull('consumed_at')
            ->whereNull('revoked_at')
            ->update([
                'status' => 'verified',
                'consumed_at' => now(),
                'row_version' => $expectedRowVersion + 1,
            ]);

        return $updated > 0;
    }
}
