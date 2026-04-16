<?php

declare(strict_types=1);

namespace Modules\User\Application\Contracts;

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
    public function findByUuid(string $uuid): ?UserAttachment;

    public function getByUser(int $userId, ?string $type = null): Collection;
}
