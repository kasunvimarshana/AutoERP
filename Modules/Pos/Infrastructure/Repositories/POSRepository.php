<?php

declare(strict_types=1);

namespace Modules\POS\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\POS\Domain\Contracts\POSRepositoryContract;
use Modules\POS\Domain\Entities\PosSession;
use Modules\POS\Domain\Entities\PosTransaction;

/**
 * POS repository implementation.
 *
 * Extends the tenant-aware AbstractRepository.
 * All queries are automatically scoped to the current tenant via HasTenant global scope.
 */
class POSRepository extends AbstractRepository implements POSRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = PosTransaction::class;
    }

    /**
     * {@inheritdoc}
     */
    public function createSession(array $data): Model
    {
        return PosSession::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function allSessions(): Collection
    {
        return PosSession::query()->get();
    }
}
