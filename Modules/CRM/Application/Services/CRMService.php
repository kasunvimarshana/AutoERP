<?php

declare(strict_types=1);

namespace Modules\CRM\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\Contracts\ServiceContract;
use Modules\CRM\Application\DTOs\CreateLeadDTO;
use Modules\CRM\Application\DTOs\CreateOpportunityDTO;
use Modules\CRM\Domain\Contracts\CRMRepositoryContract;
use Modules\CRM\Domain\Contracts\CrmLeadRepositoryContract;
use Modules\CRM\Domain\Entities\CrmLead;
use Modules\CRM\Domain\Entities\CrmOpportunity;

/**
 * CRM service.
 *
 * Orchestrates all CRM use cases: lead creation, lead-to-opportunity conversion,
 * stage updates, and pipeline close operations.
 */
class CRMService implements ServiceContract
{
    public function __construct(
        private readonly CRMRepositoryContract $crmRepository,
        private readonly CrmLeadRepositoryContract $leadRepository,
    ) {}

    /**
     * Create a new lead.
     */
    public function createLead(CreateLeadDTO $dto): CrmLead
    {
        return DB::transaction(function () use ($dto): CrmLead {
            /** @var CrmLead $lead */
            $lead = CrmLead::create([
                'first_name'  => $dto->firstName,
                'last_name'   => $dto->lastName,
                'email'       => $dto->email,
                'phone'       => $dto->phone,
                'company'     => $dto->company,
                'source'      => $dto->source,
                'status'      => 'new',
                'assigned_to' => $dto->assignedTo,
                'campaign_id' => $dto->campaignId,
                'notes'       => $dto->notes,
            ]);

            return $lead;
        });
    }

    /**
     * List leads with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function listLeads(array $filters = []): Collection
    {
        if (isset($filters['status'])) {
            return $this->leadRepository->findByStatus((string) $filters['status']);
        }

        if (isset($filters['assigned_to'])) {
            return $this->leadRepository->findByAssignee((int) $filters['assigned_to']);
        }

        return $this->leadRepository->all();
    }

    /**
     * Show a single lead by ID.
     */
    public function showLead(int $id): \Illuminate\Database\Eloquent\Model
    {
        return $this->leadRepository->findOrFail($id);
    }

    /**
     * Convert a lead to an opportunity.
     *
     * Marks the lead as 'qualified' and creates the linked opportunity.
     */
    public function convertLeadToOpportunity(int $leadId, CreateOpportunityDTO $dto): CrmOpportunity
    {
        return DB::transaction(function () use ($leadId, $dto): CrmOpportunity {
            /** @var CrmLead $lead */
            $lead = CrmLead::findOrFail($leadId);
            $lead->update(['status' => 'qualified']);

            /** @var CrmOpportunity $opportunity */
            $opportunity = $this->crmRepository->create([
                'lead_id'           => $leadId,
                'pipeline_stage_id' => $dto->pipelineStageId,
                'title'             => $dto->title,
                'expected_revenue'  => $dto->expectedRevenue,
                'close_date'        => $dto->closeDate,
                'status'            => 'open',
                'assigned_to'       => $dto->assignedTo,
                'probability'       => $dto->probability,
                'notes'             => $dto->notes,
            ]);

            return $opportunity;
        });
    }

    /**
     * Move an opportunity to a new pipeline stage.
     */
    public function updateOpportunityStage(int $opportunityId, int $stageId): CrmOpportunity
    {
        return DB::transaction(function () use ($opportunityId, $stageId): CrmOpportunity {
            /** @var CrmOpportunity $opportunity */
            $opportunity = $this->crmRepository->update($opportunityId, [
                'pipeline_stage_id' => $stageId,
            ]);

            return $opportunity;
        });
    }

    /**
     * Close an opportunity as won.
     */
    public function closeWon(int $opportunityId): CrmOpportunity
    {
        return DB::transaction(function () use ($opportunityId): CrmOpportunity {
            /** @var CrmOpportunity $opportunity */
            $opportunity = $this->crmRepository->update($opportunityId, [
                'status' => 'won',
            ]);

            return $opportunity;
        });
    }

    /**
     * Close an opportunity as lost.
     */
    public function closeLost(int $opportunityId): CrmOpportunity
    {
        return DB::transaction(function () use ($opportunityId): CrmOpportunity {
            /** @var CrmOpportunity $opportunity */
            $opportunity = $this->crmRepository->update($opportunityId, [
                'status' => 'lost',
            ]);

            return $opportunity;
        });
    }

    /**
     * List opportunities with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function listOpportunities(array $filters = []): Collection
    {
        if (isset($filters['status'])) {
            return $this->crmRepository->findByStatus((string) $filters['status']);
        }

        if (isset($filters['assigned_to'])) {
            return $this->crmRepository->findByAssignee((int) $filters['assigned_to']);
        }

        return $this->crmRepository->all();
    }

    /**
     * Show a single opportunity by ID.
     */
    public function showOpportunity(int|string $id): \Illuminate\Database\Eloquent\Model
    {
        return $this->crmRepository->findOrFail($id);
    }

    /**
     * Delete a lead.
     */
    public function deleteLead(int|string $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            return $this->leadRepository->delete($id);
        });
    }

    /**
     * List all customers.
     *
     * @param array<string, mixed> $filters
     */
    public function listCustomers(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        return $this->crmRepository->allCustomers();
    }
}
