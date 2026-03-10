<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * UserRepository
 *
 * Concrete implementation of UserRepositoryInterface.
 * Inherits all dynamic CRUD, pagination, and search capabilities
 * from BaseRepository.
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Domain-specific methods
    // ─────────────────────────────────────────────────────────────────────────

    /** {@inheritDoc} */
    public function findByEmailAndTenant(string $email, string $tenantId): ?User
    {
        /** @var User|null */
        return $this->findBy(['email' => $email, 'tenant_id' => $tenantId]);
    }

    /** {@inheritDoc} */
    public function listByTenant(
        string $tenantId,
        array  $filters  = [],
        int    $perPage  = 15
    ): LengthAwarePaginator {
        $filters['tenant_id'] = $tenantId;

        return $this->paginate($filters, $perPage);
    }
}
