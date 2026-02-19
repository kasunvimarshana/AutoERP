<?php

declare(strict_types=1);

namespace Modules\Notification\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Notification\Exceptions\NotificationTemplateNotFoundException;
use Modules\Notification\Models\NotificationTemplate;

/**
 * Notification Template Repository
 *
 * Handles data access for notification templates
 */
class NotificationTemplateRepository extends BaseRepository
{
    public function __construct(NotificationTemplate $model)
    {
        parent::__construct($model);
    }

    /**
     * Find template by ID
     *
     * @throws NotificationTemplateNotFoundException
     */
    public function findById(int $id): NotificationTemplate
    {
        $template = $this->model->find($id);

        if (! $template) {
            throw new NotificationTemplateNotFoundException("Template with ID {$id} not found");
        }

        return $template;
    }

    /**
     * Find template by code
     *
     * @throws NotificationTemplateNotFoundException
     */
    public function findByCode(string $code): NotificationTemplate
    {
        $template = $this->model->where('code', $code)->first();

        if (! $template) {
            throw new NotificationTemplateNotFoundException("Template with code '{$code}' not found");
        }

        return $template;
    }

    /**
     * Get active templates
     */
    public function getActive(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get templates by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Check if template code exists
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = $this->model->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
