<?php

declare(strict_types=1);

namespace Modules\Notification\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Notification\Exceptions\NotificationChannelNotFoundException;
use Modules\Notification\Models\NotificationChannel;

/**
 * Notification Channel Repository
 *
 * Handles data access for notification channels
 */
class NotificationChannelRepository extends BaseRepository
{
    public function __construct(NotificationChannel $model)
    {
        parent::__construct($model);
    }

    /**
     * Find channel by ID
     *
     * @throws NotificationChannelNotFoundException
     */
    public function findById(int $id): NotificationChannel
    {
        $channel = $this->model->find($id);

        if (! $channel) {
            throw new NotificationChannelNotFoundException("Channel with ID {$id} not found");
        }

        return $channel;
    }

    /**
     * Find channel by code
     *
     * @throws NotificationChannelNotFoundException
     */
    public function findByCode(string $code): NotificationChannel
    {
        $channel = $this->model->where('code', $code)->first();

        if (! $channel) {
            throw new NotificationChannelNotFoundException("Channel with code '{$code}' not found");
        }

        return $channel;
    }

    /**
     * Get active channels
     */
    public function getActive(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();
    }

    /**
     * Get active channels by type
     */
    public function getActiveByType(string $type): Collection
    {
        return $this->model
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();
    }

    /**
     * Get default channel for type
     */
    public function getDefaultByType(string $type): ?NotificationChannel
    {
        return $this->model
            ->where('type', $type)
            ->where('is_active', true)
            ->where('is_default', true)
            ->orderBy('priority')
            ->first();
    }
}
