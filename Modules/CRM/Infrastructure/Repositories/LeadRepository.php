<?php

declare(strict_types=1);

namespace Modules\Crm\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Crm\Domain\Contracts\LeadRepositoryInterface;
use Modules\Crm\Domain\Entities\Lead;
use Modules\Crm\Domain\Enums\LeadStatus;
use Modules\Crm\Infrastructure\Models\LeadModel;

class LeadRepository extends BaseRepository implements LeadRepositoryInterface
{
    protected function model(): string
    {
        return LeadModel::class;
    }

    public function findById(int $id, int $tenantId): ?Lead
    {
        $model = $this->newQuery()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array
    {
        $paginator = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()->map(fn (LeadModel $m) => $this->toDomain($m))->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function findByContact(int $contactId, int $tenantId): array
    {
        return $this->newQuery()
            ->where('contact_id', $contactId)
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->get()
            ->map(fn (LeadModel $m) => $this->toDomain($m))
            ->all();
    }

    public function save(Lead $lead): Lead
    {
        if ($lead->id !== null) {
            $model = $this->newQuery()
                ->where('id', $lead->id)
                ->where('tenant_id', $lead->tenantId)
                ->firstOrFail();
        } else {
            $model = new LeadModel;
            $model->tenant_id = $lead->tenantId;
        }

        $model->contact_id = $lead->contactId;
        $model->title = $lead->title;
        $model->description = $lead->description;
        $model->status = $lead->status->value;
        $model->estimated_value = $lead->estimatedValue;
        $model->currency = $lead->currency;
        $model->expected_close_date = $lead->expectedCloseDate;
        $model->notes = $lead->notes;
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id, int $tenantId): void
    {
        $model = $this->newQuery()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($model === null) {
            throw new \DomainException("Lead with ID {$id} not found.");
        }

        $model->delete();
    }

    private function toDomain(LeadModel $model): Lead
    {
        return new Lead(
            id: $model->id,
            tenantId: $model->tenant_id,
            contactId: $model->contact_id,
            title: $model->title,
            description: $model->description,
            status: LeadStatus::from($model->status),
            estimatedValue: bcadd((string) ($model->estimated_value ?? '0'), '0', 4),
            currency: $model->currency,
            expectedCloseDate: $model->expected_close_date?->toDateString(),
            notes: $model->notes,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
