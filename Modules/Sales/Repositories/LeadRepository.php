<?php

declare(strict_types=1);

namespace Modules\Sales\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Sales\Entities\Lead;
use Modules\Sales\Repositories\Contracts\LeadRepositoryInterface;

/**
 * Lead Repository Implementation
 *
 * Handles all lead data access operations.
 */
class LeadRepository extends BaseRepository implements LeadRepositoryInterface
{
    /**
     * LeadRepository constructor.
     */
    public function __construct(Lead $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByLeadNumber(string $leadNumber): ?Lead
    {
        return $this->model->where('lead_number', $leadNumber)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getLeadsByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getLeadsByStage(string $stage): Collection
    {
        return $this->model->where('stage', $stage)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getLeadsAssignedTo(int $userId): Collection
    {
        return $this->model->where('assigned_to', $userId)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query): Collection
    {
        return $this->model
            ->where('contact_name', 'LIKE', "%{$query}%")
            ->orWhere('contact_email', 'LIKE', "%{$query}%")
            ->orWhere('lead_number', 'LIKE', "%{$query}%")
            ->orWhere('company', 'LIKE', "%{$query}%")
            ->get();
    }
}
