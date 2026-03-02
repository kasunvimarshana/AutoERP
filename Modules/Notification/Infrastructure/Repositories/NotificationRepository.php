<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Notification\Domain\Contracts\NotificationRepositoryContract;
use Modules\Notification\Domain\Entities\NotificationLog;
use Modules\Notification\Domain\Entities\NotificationTemplate;

/**
 * Notification repository implementation.
 *
 * Extends the tenant-aware AbstractRepository.
 * All queries are automatically scoped to the current tenant via HasTenant global scope.
 */
class NotificationRepository extends AbstractRepository implements NotificationRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = NotificationTemplate::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findByChannel(string $channel): Collection
    {
        return $this->query()
            ->where('channel', $channel)
            ->where('is_active', true)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findBySlug(string $slug): ?NotificationTemplate
    {
        /** @var NotificationTemplate|null */
        return $this->query()
            ->where('slug', $slug)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function paginateLogs(int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return NotificationLog::query()->paginate($perPage);
    }
}
