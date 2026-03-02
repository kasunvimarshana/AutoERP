<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Domain\Contracts\RepositoryContract;
use Modules\Notification\Domain\Entities\NotificationTemplate;

/**
 * Notification repository contract.
 *
 * Extends the base repository contract with notification-specific query methods.
 */
interface NotificationRepositoryContract extends RepositoryContract
{
    /**
     * Find active templates by channel (tenant-scoped).
     */
    public function findByChannel(string $channel): Collection;

    /**
     * Find a template by its slug (tenant-scoped).
     */
    public function findBySlug(string $slug): ?NotificationTemplate;

    /**
     * Return a paginated list of notification logs.
     */
    public function paginateLogs(int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
}
