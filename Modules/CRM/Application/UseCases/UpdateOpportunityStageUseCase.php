<?php
namespace Modules\CRM\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\CRM\Domain\Contracts\OpportunityRepositoryInterface;
use Modules\CRM\Domain\Events\OpportunityStageChanged;
class UpdateOpportunityStageUseCase
{
    public function __construct(private OpportunityRepositoryInterface $repo) {}
    public function execute(string $opportunityId, string $newStage): object
    {
        return DB::transaction(function () use ($opportunityId, $newStage) {
            $opportunity = $this->repo->findById($opportunityId);
            if (!$opportunity) throw new \RuntimeException('Opportunity not found.');
            $fromStage = $opportunity->stage;
            $updated = $this->repo->update($opportunityId, ['stage' => $newStage]);
            Event::dispatch(new OpportunityStageChanged($opportunityId, $fromStage, $newStage));
            return $updated;
        });
    }
}
