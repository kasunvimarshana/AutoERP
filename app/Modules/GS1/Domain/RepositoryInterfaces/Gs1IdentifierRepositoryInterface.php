<?php

declare(strict_types=1);

namespace Modules\GS1\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\GS1\Domain\Entities\Gs1Identifier;

interface Gs1IdentifierRepositoryInterface extends RepositoryInterface
{
    public function save(Gs1Identifier $identifier): Gs1Identifier;

    public function findByValue(int $tenantId, string $type, string $value): ?Gs1Identifier;

    public function findByEntity(int $tenantId, string $entityType, int $entityId): Collection;
}
