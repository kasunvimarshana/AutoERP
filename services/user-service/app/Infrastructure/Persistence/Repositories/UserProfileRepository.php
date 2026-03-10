<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Infrastructure\Persistence\Models\UserProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * UserProfileRepository
 */
class UserProfileRepository extends BaseRepository
{
    public function __construct(UserProfile $model)
    {
        parent::__construct($model);
    }

    public function findByAuthUserId(string $authUserId, string $tenantId): ?UserProfile
    {
        /** @var UserProfile|null */
        return $this->findBy(['auth_user_id' => $authUserId, 'tenant_id' => $tenantId]);
    }

    public function findByEmail(string $email, string $tenantId): ?UserProfile
    {
        /** @var UserProfile|null */
        return $this->findBy(['email' => $email, 'tenant_id' => $tenantId]);
    }

    public function listForTenant(
        string $tenantId,
        array  $filters = [],
        int    $perPage = 15
    ): LengthAwarePaginator {
        $filters['tenant_id'] = $tenantId;
        return $this->paginate($filters, $perPage, ['*'], 'page', null, [], ['created_at' => 'desc']);
    }
}
