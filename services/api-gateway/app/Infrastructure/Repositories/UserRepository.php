<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Domain\User\Models\User;

/**
 * User Repository
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    protected array $searchableColumns = ['name', 'email'];
    protected array $sortableColumns = ['name', 'email', 'created_at'];
    protected array $filterableColumns = ['tenant_id', 'status'];

    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByEmail(string $email, string $tenantId): ?User
    {
        return User::where('email', $email)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findBySsoId(string $ssoId, string $provider): ?User
    {
        return User::where('sso_id', $ssoId)
            ->where('sso_provider', $provider)
            ->first();
    }
}
