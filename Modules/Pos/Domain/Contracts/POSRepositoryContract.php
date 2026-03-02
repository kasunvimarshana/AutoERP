<?php

declare(strict_types=1);

namespace Modules\POS\Domain\Contracts;

use Modules\Core\Domain\Contracts\RepositoryContract;

/**
 * POS repository contract.
 */
interface POSRepositoryContract extends RepositoryContract
{
    /**
     * Create a new POS session.
     *
     * @param array<string, mixed> $data
     */
    public function createSession(array $data): \Illuminate\Database\Eloquent\Model;

    /**
     * Return all POS sessions (tenant-scoped).
     */
    public function allSessions(): \Illuminate\Database\Eloquent\Collection;
}
