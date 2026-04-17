<?php

declare(strict_types=1);

namespace Modules\User\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\User\Domain\Entities\UserAttachment;

/**
 * Contract for querying user attachment records.
 *
 * Exposes read operations through the service layer to avoid direct
 * repository injection in controllers (DIP compliance).
 */
interface FindUserAttachmentsServiceInterface
{
    public function find(int $id): ?UserAttachment;

    public function findByUuid(string $uuid): ?UserAttachment;

    public function getByUser(int $userId, ?string $type = null): Collection;

    public function paginateByUser(int $userId, ?string $type, int $perPage, int $page): LengthAwarePaginator;
}
