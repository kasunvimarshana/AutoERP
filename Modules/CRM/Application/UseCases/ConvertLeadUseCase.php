<?php
namespace Modules\CRM\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\CRM\Domain\Contracts\LeadRepositoryInterface;
use Modules\CRM\Domain\Contracts\OpportunityRepositoryInterface;
use Modules\CRM\Domain\Enums\LeadStatus;
use Modules\CRM\Domain\Events\LeadConverted;
class ConvertLeadUseCase
{
    public function __construct(
        private LeadRepositoryInterface $leadRepo,
        private OpportunityRepositoryInterface $opportunityRepo,
    ) {}
    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $lead = $this->leadRepo->findById($data['lead_id']);
            if (!$lead) throw new \RuntimeException('Lead not found.');
            if ($lead->status === LeadStatus::Converted->value) throw new \RuntimeException('Lead already converted.');
            $opportunity = $this->opportunityRepo->create([
                'tenant_id' => $lead->tenant_id,
                'title' => $data['title'] ?? $lead->name,
                'lead_id' => $lead->id,
                'stage' => 'prospecting',
                'expected_revenue' => $data['expected_revenue'] ?? 0,
                'probability' => $data['probability'] ?? 0,
                'assigned_to' => $lead->assigned_to,
            ]);
            $this->leadRepo->update($lead->id, [
                'status' => LeadStatus::Converted->value,
                'converted_at' => now(),
                'converted_opportunity_id' => $opportunity->id,
            ]);
            Event::dispatch(new LeadConverted(
                leadId: $lead->id,
                opportunityId: $opportunity->id,
                tenantId: (string) ($lead->tenant_id ?? ''),
                contactName: (string) ($lead->name ?? ''),
                contactEmail: (string) ($lead->email ?? ''),
                expectedRevenue: (string) ($data['expected_revenue'] ?? $opportunity->expected_revenue ?? '0'),
            ));
            return $opportunity;
        });
    }
}
