<?php

declare(strict_types=1);

namespace App\Modules\CRM\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\CRM\Models\Lead;
use Illuminate\Database\Eloquent\Collection;

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
     * Find lead by code
     */
    public function findByCode(string $code): ?Lead
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * Get leads by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get leads by source
     */
    public function getBySource(string $source): Collection
    {
        return $this->model->where('source', $source)->get();
    }

    /**
     * Get leads assigned to user
     */
    public function getByAssignedUser(int $userId): Collection
    {
        return $this->model->where('assigned_to', $userId)->get();
    }

    /**
     * Get converted leads
     */
    public function getConvertedLeads(): Collection
    {
        return $this->model->where('is_converted', true)->get();
    }

    /**
     * Get qualified leads
     */
    public function getQualifiedLeads(): Collection
    {
        return $this->model->whereIn('status', ['qualified', 'proposal', 'negotiation'])->get();
    }

    /**
     * Get won leads
     */
    public function getWonLeads(): Collection
    {
        return $this->model->where('status', 'won')->get();
    }

    /**
     * Get lost leads
     */
    public function getLostLeads(): Collection
    {
        return $this->model->where('status', 'lost')->get();
    }

    /**
     * Search leads
     */
    public function search(string $query, array $filters = []): Collection
    {
        $queryBuilder = $this->model->query();

        // Search by name, code, email, phone, company
        if (!empty($query)) {
            $queryBuilder->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%")
                  ->orWhere('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('mobile', 'like', "%{$query}%")
                  ->orWhere('company_name', 'like', "%{$query}%");
            });
        }

        // Apply filters
        if (isset($filters['status'])) {
            $queryBuilder->where('status', $filters['status']);
        }

        if (isset($filters['source'])) {
            $queryBuilder->where('source', $filters['source']);
        }

        if (isset($filters['type'])) {
            $queryBuilder->where('type', $filters['type']);
        }

        if (isset($filters['priority'])) {
            $queryBuilder->where('priority', $filters['priority']);
        }

        if (isset($filters['assigned_to'])) {
            $queryBuilder->where('assigned_to', $filters['assigned_to']);
        }

        if (isset($filters['is_converted'])) {
            $queryBuilder->where('is_converted', $filters['is_converted']);
        }

        if (isset($filters['organization_id'])) {
            $queryBuilder->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['branch_id'])) {
            $queryBuilder->where('branch_id', $filters['branch_id']);
        }

        if (isset($filters['min_value'])) {
            $queryBuilder->where('estimated_value', '>=', $filters['min_value']);
        }

        if (isset($filters['max_value'])) {
            $queryBuilder->where('estimated_value', '<=', $filters['max_value']);
        }

        return $queryBuilder->get();
    }

    /**
     * Get leads closing soon
     */
    public function getLeadsClosingSoon(int $days = 7): Collection
    {
        return $this->model
            ->where('is_converted', false)
            ->whereIn('status', ['qualified', 'proposal', 'negotiation'])
            ->whereNotNull('expected_close_date')
            ->whereBetween('expected_close_date', [now(), now()->addDays($days)])
            ->orderBy('expected_close_date', 'asc')
            ->get();
    }
}
