<?php

declare(strict_types=1);

namespace Modules\Pos\Domain\Contracts;

use Modules\Pos\Domain\Entities\PosSession;

interface PosSessionRepositoryInterface
{
    public function save(PosSession $session): PosSession;

    public function findById(int $id, int $tenantId): ?PosSession;

    public function findAll(int $tenantId, int $page, int $perPage): array;

    public function findActiveByUser(int $tenantId, int $userId): ?PosSession;

    public function delete(int $id, int $tenantId): void;
}
