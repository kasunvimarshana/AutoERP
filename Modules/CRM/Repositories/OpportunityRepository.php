<?php

declare(strict_types=1);

namespace Modules\CRM\Repositories;

use Modules\Core\Repositories\BaseRepository;
use Modules\CRM\Exceptions\OpportunityNotFoundException;
use Modules\CRM\Models\Opportunity;

class OpportunityRepository extends BaseRepository
{
    public function __construct(Opportunity $model)
    {
        parent::__construct($model);
    }

    protected function getModelClass(): string
    {
        return Opportunity::class;
    }

    protected function getNotFoundExceptionClass(): string
    {
        return OpportunityNotFoundException::class;
    }

    public function findByCode(string $code): ?Opportunity
    {
        return $this->model->where('opportunity_code', $code)->first();
    }

    public function findByCodeOrFail(string $code): Opportunity
    {
        $opportunity = $this->findByCode($code);

        if (! $opportunity) {
            throw new OpportunityNotFoundException("Opportunity with code {$code} not found");
        }

        return $opportunity;
    }

    public function findByCustomerId(int $customerId): array
    {
        return $this->model->where('customer_id', $customerId)->get()->all();
    }

    public function findByAssignedUser(int $userId): array
    {
        return $this->model->where('assigned_to', $userId)->get()->all();
    }

    public function findOpen(): array
    {
        return $this->model->whereNotIn('stage', ['closed_won', 'closed_lost'])->get()->all();
    }

    public function findClosed(): array
    {
        return $this->model->whereIn('stage', ['closed_won', 'closed_lost'])->get()->all();
    }

    public function findWon(): array
    {
        return $this->model->where('stage', 'closed_won')->get()->all();
    }
}
