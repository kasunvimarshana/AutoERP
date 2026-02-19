<?php

declare(strict_types=1);

namespace Modules\CRM\Services;

use Modules\Core\Helpers\MathHelper;
use Modules\Core\Helpers\TransactionHelper;
use Modules\Core\Services\CodeGeneratorService;
use Modules\CRM\Enums\OpportunityStage;
use Modules\CRM\Models\Opportunity;
use Modules\CRM\Repositories\OpportunityRepository;

class OpportunityService
{
    public function __construct(
        private OpportunityRepository $opportunityRepository,
        private CodeGeneratorService $codeGenerator
    ) {}

    /**
     * Create a new opportunity.
     */
    public function createOpportunity(array $data): Opportunity
    {
        return TransactionHelper::execute(function () use ($data) {
            // Generate opportunity code if not provided
            if (empty($data['opportunity_code'])) {
                $data['opportunity_code'] = $this->generateOpportunityCode();
            }

            // Set default stage if not provided
            $data['stage'] = $data['stage'] ?? OpportunityStage::PROSPECTING;

            // Set probability from stage if not provided
            if (empty($data['probability'])) {
                $stage = OpportunityStage::from($data['stage']);
                $data['probability'] = $stage->probability();
            }

            return $this->opportunityRepository->create($data);
        });
    }

    /**
     * Calculate total pipeline value
     */
    public function calculatePipelineValue(array $filters = []): string
    {
        $opportunities = $this->getFilteredOpportunities($filters);
        $total = '0';

        foreach ($opportunities as $opportunity) {
            if (! $opportunity->isClosed()) {
                $total = MathHelper::add($total, $opportunity->amount);
            }
        }

        return $total;
    }

    /**
     * Calculate weighted pipeline value (amount × probability)
     */
    public function calculateWeightedPipelineValue(array $filters = []): string
    {
        $opportunities = $this->getFilteredOpportunities($filters);
        $total = '0';

        foreach ($opportunities as $opportunity) {
            if (! $opportunity->isClosed()) {
                $total = MathHelper::add($total, $opportunity->expected_revenue);
            }
        }

        return $total;
    }

    /**
     * Calculate win rate percentage
     */
    public function calculateWinRate(array $filters = []): int
    {
        $opportunities = $this->getFilteredOpportunities($filters);
        $closed = array_filter($opportunities, fn ($opp) => $opp->isClosed());
        $won = array_filter($opportunities, fn ($opp) => $opp->isWon());

        if (count($closed) === 0) {
            return 0;
        }

        return (int) round((count($won) / count($closed)) * 100);
    }

    /**
     * Move opportunity to next stage
     */
    public function advanceStage(int $opportunityId): Opportunity
    {
        $opportunity = $this->opportunityRepository->findOrFail($opportunityId);

        $nextStage = match ($opportunity->stage) {
            OpportunityStage::PROSPECTING => OpportunityStage::QUALIFICATION,
            OpportunityStage::QUALIFICATION => OpportunityStage::NEEDS_ANALYSIS,
            OpportunityStage::NEEDS_ANALYSIS => OpportunityStage::PROPOSAL,
            OpportunityStage::PROPOSAL => OpportunityStage::NEGOTIATION,
            OpportunityStage::NEGOTIATION => OpportunityStage::CLOSED_WON,
            default => $opportunity->stage,
        };

        $this->opportunityRepository->update($opportunityId, [
            'stage' => $nextStage,
            'probability' => $nextStage->probability(),
        ]);

        return $this->opportunityRepository->findOrFail($opportunityId);
    }

    /**
     * Mark opportunity as won
     */
    public function markAsWon(int $opportunityId): Opportunity
    {
        return $this->opportunityRepository->update($opportunityId, [
            'stage' => OpportunityStage::CLOSED_WON,
            'probability' => 100,
            'actual_close_date' => now(),
        ]);
    }

    /**
     * Mark opportunity as lost
     */
    public function markAsLost(int $opportunityId, ?string $reason = null): Opportunity
    {
        $opportunity = $this->opportunityRepository->findOrFail($opportunityId);

        $data = [
            'stage' => OpportunityStage::CLOSED_LOST,
            'probability' => 0,
            'actual_close_date' => now(),
        ];

        if ($reason) {
            $existingNotes = $opportunity->notes ?? '';
            $data['notes'] = $existingNotes."\nLost Reason: ".$reason;
        }

        return $this->opportunityRepository->update($opportunityId, $data);
    }

    /**
     * Get filtered opportunities
     */
    private function getFilteredOpportunities(array $filters): array
    {
        if (isset($filters['assigned_to'])) {
            return $this->opportunityRepository->findByAssignedUser($filters['assigned_to']);
        }

        if (isset($filters['customer_id'])) {
            return $this->opportunityRepository->findByCustomerId($filters['customer_id']);
        }

        return $this->opportunityRepository->findAll();
    }

    /**
     * Generate unique opportunity code
     */
    public function generateOpportunityCode(): string
    {
        $prefix = config('crm.opportunity.code_prefix', 'OPP-');

        return $this->codeGenerator->generateDateBased(
            $prefix,
            'Ymd',
            null,
            6,
            fn (string $code) => $this->opportunityRepository->findByCode($code) !== null
        );
    }
}
