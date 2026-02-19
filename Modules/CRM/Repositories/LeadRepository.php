<?php

declare(strict_types=1);

namespace Modules\CRM\Repositories;

use Modules\Core\Repositories\BaseRepository;
use Modules\CRM\Exceptions\LeadNotFoundException;
use Modules\CRM\Models\Lead;

class LeadRepository extends BaseRepository
{
    public function __construct(Lead $model)
    {
        parent::__construct($model);
    }

    protected function getModelClass(): string
    {
        return Lead::class;
    }

    protected function getNotFoundExceptionClass(): string
    {
        return LeadNotFoundException::class;
    }

    public function findByEmail(string $email): ?Lead
    {
        return $this->model->where('email', $email)->first();
    }

    public function findConverted(): array
    {
        return $this->model->whereNotNull('converted_at')->get()->all();
    }

    public function findUnconverted(): array
    {
        return $this->model->whereNull('converted_at')->get()->all();
    }

    public function findByAssignedUser(int $userId): array
    {
        return $this->model->where('assigned_to', $userId)->get()->all();
    }

    /**
     * Find leads with filters and pagination.
     */
    public function findWithFilters(array $filters, int $perPage = 15)
    {
        $query = $this->model->newQuery()->with(['organization']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (isset($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (isset($filters['converted'])) {
            if ($filters['converted']) {
                $query->whereNotNull('converted_to_customer_id');
            } else {
                $query->whereNull('converted_to_customer_id');
            }
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }
}
