<?php

declare(strict_types=1);

namespace Modules\CRM\Infrastructure\Repositories;

use Modules\CRM\Domain\Contracts\OpportunityRepositoryInterface;
use Modules\CRM\Domain\Entities\Opportunity as OpportunityEntity;
use Modules\CRM\Infrastructure\Models\Opportunity as OpportunityModel;

class OpportunityRepository implements OpportunityRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?OpportunityEntity
    {
        $m = OpportunityModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $m ? $this->toDomain($m) : null;
    }

    public function findAll(int $tenantId, ?string $stage, int $page, int $perPage): array
    {
        $query = OpportunityModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id');

        if ($stage !== null) {
            $query->where('stage', $stage);
        }

        return $query->forPage($page, $perPage)
            ->get()
            ->map(fn (OpportunityModel $m): OpportunityEntity => $this->toDomain($m))
            ->all();
    }

    public function findOpen(int $tenantId): array
    {
        return OpportunityModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->orderByDesc('value')
            ->get()
            ->map(fn (OpportunityModel $m): OpportunityEntity => $this->toDomain($m))
            ->all();
    }

    public function save(OpportunityEntity $opportunity): OpportunityEntity
    {
        $data = [
            'tenant_id'           => $opportunity->getTenantId(),
            'lead_id'             => $opportunity->getLeadId(),
            'contact_id'          => $opportunity->getContactId(),
            'title'               => $opportunity->getTitle(),
            'stage'               => $opportunity->getStage(),
            'value'               => $opportunity->getValue(),
            'probability'         => $opportunity->getProbability(),
            'expected_close_date' => $opportunity->getExpectedCloseDate(),
            'assigned_to'         => $opportunity->getAssignedTo(),
            'notes'               => $opportunity->getNotes(),
        ];

        if ($opportunity->getId() > 0) {
            $m = OpportunityModel::withoutGlobalScope('tenant')->findOrFail($opportunity->getId());
            $m->update($data);
        } else {
            $m = OpportunityModel::create($data);
        }

        return $this->toDomain($m->fresh());
    }

    public function delete(int $id, int $tenantId): void
    {
        OpportunityModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first()
            ?->delete();
    }

    private function toDomain(OpportunityModel $m): OpportunityEntity
    {
        return new OpportunityEntity(
            id: (int) $m->id,
            tenantId: (int) $m->tenant_id,
            leadId: $m->lead_id ? (int) $m->lead_id : null,
            contactId: $m->contact_id ? (int) $m->contact_id : null,
            title: (string) $m->title,
            stage: (string) $m->stage,
            value: (string) $m->value,
            probability: (string) $m->probability,
            expectedCloseDate: $m->expected_close_date?->toDateString(),
            assignedTo: $m->assigned_to ? (int) $m->assigned_to : null,
            notes: $m->notes,
        );
    }
}
