<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeInterface;
use Modules\Auth\Application\Repositories\AuthLoginAttemptRepositoryInterface;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\AuthLoginAttemptModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;

final class EloquentAuthLoginAttemptRepository extends EloquentRepository implements AuthLoginAttemptRepositoryInterface
{
    public function __construct(AuthLoginAttemptModel $model)
    {
        parent::__construct($model);
    }

    public function countRecentFailures(
        ?int $tenantId,
        string $loginIdentifier,
        ?string $ipAddress,
        DateTimeInterface $since,
    ): int {
        $query = $this->query()
            ->where('login_identifier', trim(strtolower($loginIdentifier)))
            ->where('was_successful', false)
            ->where('attempted_at', '>=', $since);

        if ($tenantId === null) {
            $query->whereNull('tenant_id');
        } else {
            $query->where('tenant_id', $tenantId);
        }

        if ($ipAddress === null || trim($ipAddress) === '') {
            $query->whereNull('ip_address');
        } else {
            $query->where('ip_address', trim($ipAddress));
        }

        return (int) $query->count();
    }

    public function clearRecentFailures(
        ?int $tenantId,
        string $loginIdentifier,
        ?string $ipAddress,
        DateTimeInterface $since,
    ): void {
        $query = $this->query()
            ->where('login_identifier', trim(strtolower($loginIdentifier)))
            ->where('was_successful', false)
            ->where('attempted_at', '>=', $since);

        if ($tenantId === null) {
            $query->whereNull('tenant_id');
        } else {
            $query->where('tenant_id', $tenantId);
        }

        if ($ipAddress === null || trim($ipAddress) === '') {
            $query->whereNull('ip_address');
        } else {
            $query->where('ip_address', trim($ipAddress));
        }

        $query->delete();
    }
}
