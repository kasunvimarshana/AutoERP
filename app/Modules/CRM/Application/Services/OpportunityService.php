<?php
declare(strict_types=1);
namespace Modules\CRM\Application\Services;

use Modules\CRM\Domain\Entities\Opportunity;
use Modules\CRM\Domain\Exceptions\OpportunityNotFoundException;
use Modules\CRM\Domain\RepositoryInterfaces\OpportunityRepositoryInterface;

class OpportunityService
{
    public function __construct(private readonly OpportunityRepositoryInterface $repository) {}

    public function findById(int $id): Opportunity
    {
        $opp = $this->repository->findById($id);
        if ($opp === null) throw new OpportunityNotFoundException($id);
        return $opp;
    }

    public function findAllByTenant(int $tenantId, array $filters = []): array
    {
        return $this->repository->findAllByTenant($tenantId, $filters);
    }

    public function create(array $data): Opportunity
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): Opportunity
    {
        $this->findById($id);
        return $this->repository->update($id, $data) ?? $this->findById($id);
    }

    public function advanceTo(int $id, string $stage): Opportunity
    {
        $opp = $this->findById($id);
        $opp->advanceTo($stage);
        return $this->repository->update($id, ['stage' => $stage]) ?? $opp;
    }

    public function closeWon(int $id): Opportunity
    {
        $opp = $this->findById($id);
        $opp->closeWon();
        return $this->repository->update($id, [
            'stage'       => Opportunity::STAGE_CLOSED_WON,
            'probability' => 100.0,
            'closed_at'   => new \DateTimeImmutable(),
        ]) ?? $opp;
    }

    public function closeLost(int $id, ?string $reason = null): Opportunity
    {
        $opp = $this->findById($id);
        $opp->closeLost($reason);
        return $this->repository->update($id, [
            'stage'       => Opportunity::STAGE_CLOSED_LOST,
            'probability' => 0.0,
            'closed_at'   => new \DateTimeImmutable(),
        ]) ?? $opp;
    }

    public function getPipelineSummary(int $tenantId): array
    {
        $opportunities = $this->repository->findAllByTenant($tenantId);
        $summary = [];
        foreach ($opportunities as $opp) {
            $stage = $opp->getStage();
            if (!isset($summary[$stage])) {
                $summary[$stage] = ['count' => 0, 'total_amount' => 0.0, 'weighted_amount' => 0.0];
            }
            $summary[$stage]['count']++;
            $summary[$stage]['total_amount']    += $opp->getAmount();
            $summary[$stage]['weighted_amount'] += $opp->getWeightedAmount();
        }
        return $summary;
    }

    public function delete(int $id): void
    {
        $this->findById($id);
        $this->repository->delete($id);
    }
}
