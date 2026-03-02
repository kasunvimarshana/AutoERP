<?php
declare(strict_types=1);

namespace Modules\CRM\Repositories;
use Modules\CRM\Domain\Contracts\LeadRepositoryInterface;
use Modules\CRM\Domain\Entities\Lead as LeadEntity;
use Modules\CRM\Domain\Enums\LeadStatus;
use Modules\CRM\Infrastructure\Models\Lead as LeadModel;
class LeadRepository implements LeadRepositoryInterface {
    public function findById(int $id, int $tenantId): ?LeadEntity {
        $m = LeadModel::withoutGlobalScope('tenant')->where('id',$id)->where('tenant_id',$tenantId)->first();
        return $m ? $this->toDomain($m) : null;
    }
    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array {
        return LeadModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn(LeadModel $m): LeadEntity => $this->toDomain($m))
            ->all();
    }
    public function save(LeadEntity $lead): LeadEntity {
        $data = [
            'tenant_id'           => $lead->getTenantId(),
            'contact_id'          => $lead->getContactId(),
            'title'               => $lead->getTitle(),
            'status'              => $lead->getStatus()->value,
            'source'              => $lead->getSource(),
            'value'               => $lead->getValue(),
            'expected_close_date' => $lead->getExpectedCloseDate(),
            'assigned_to'         => $lead->getAssignedTo(),
            'notes'               => $lead->getNotes(),
        ];
        if ($lead->getId() > 0) {
            $m = LeadModel::withoutGlobalScope('tenant')->findOrFail($lead->getId());
            $m->update($data);
        } else {
            $m = LeadModel::create($data);
        }
        return $this->toDomain($m->fresh());
    }
    public function delete(int $id, int $tenantId): void {
        LeadModel::withoutGlobalScope('tenant')->where('id',$id)->where('tenant_id',$tenantId)->first()?->delete();
    }
    private function toDomain(LeadModel $m): LeadEntity {
        return new LeadEntity(
            id: (int)$m->id,
            tenantId: (int)$m->tenant_id,
            contactId: $m->contact_id ? (int)$m->contact_id : null,
            title: (string)$m->title,
            status: $m->status instanceof LeadStatus ? $m->status : LeadStatus::from((string)$m->status),
            source: $m->source,
            value: (string)$m->value,
            expectedCloseDate: $m->expected_close_date?->toDateString(),
            assignedTo: $m->assigned_to ? (int)$m->assigned_to : null,
            notes: $m->notes,
        );
    }
}
